<?php

namespace App\Http\Controllers\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class KoraController extends BankTransferProviderController
{
    protected function providerSlug(): string
    {
        return 'kora';
    }

    protected function headers(): array
    {
        return [
            'Authorization: Bearer ' . ($this->api()?->secret_key ?? ''),
            'Content-Type: application/json',
        ];
    }

    protected function publicHeaders(): array
    {
        return [
            'Authorization: Bearer ' . ($this->api()?->public_key ?? ''),
            'Content-Type: application/json',
        ];
    }

    public function verifyWebhookSignature(Request $request): array
    {
        $payload = normalizeWebhookPayload($request);
        $rawBody = trim((string) $request->getContent());
        $signature = (string) $request->header('x-korapay-signature');
        $dataToSign = data_get($payload, 'data', []);
        $expectedSignature = is_array($dataToSign) && ! empty($rawBody)
            ? hash_hmac('sha256', json_encode($dataToSign, JSON_UNESCAPED_SLASHES), (string) ($this->api()?->secret_key ?? ''))
            : null;

        if (blank($signature) || blank($expectedSignature) || ! hash_equals($expectedSignature, $signature)) {
            return [
                'status' => false,
                'reference' => data_get($payload, 'transaction.reference')
                    ?? data_get($payload, 'data.transaction.reference')
                    ?? data_get($payload, 'reference')
                    ?? data_get($payload, 'destination.reference')
                    ?? data_get($payload, 'event.reference'),
                'message' => 'Invalid Kora webhook signature.',
            ];
        }

        $reference = data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'data.transaction.reference')
            ?? data_get($payload, 'reference')
            ?? data_get($payload, 'destination.reference')
            ?? data_get($payload, 'event.reference');

        return [
            'status' => filled($reference),
            'reference' => $reference,
            'message' => filled($reference)
                ? 'Webhook signature verified.'
                : 'Webhook reference could not be resolved.',
        ];
    }

    public function verifyBankDetails(array $data)
    {
        $bankCode = $data['provider_bank_code'] ?? $data['bank_code'] ?? null;

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/misc/banks/resolve',
            json_encode([
                'bank' => $bankCode,
                'account' => $data['account_number'] ?? null,
                'currency' => 'NGN',
            ]),
            $this->headers(),
            'POST'
        );

        $success = (bool) data_get($response, 'status', false);
        $message = data_get($response, 'message')
            ?? data_get($response, 'response_description')
            ?? ($success ? 'Account resolved.' : 'Unable to resolve account.');

        return response()->json([
            'status' => $success,
            'message' => $message,
            'data' => $response,
            'raw_response' => $response,
        ], $success ? 200 : 422);
    }

    public function transfer(array $data): array
    {
        $destinationType = strtolower((string) ($data['destination_type'] ?? 'bank_account'));
        $currency = strtoupper((string) ($data['currency'] ?? 'NGN'));
        $reference = $data['transaction_id'] ?? (string) Str::uuid();
        $customerName = $data['customer_name'] ?? $data['account_name'] ?? $data['name'] ?? 'Transfer Recipient';
        $customerEmail = $data['customer_email'] ?? $data['email'] ?? null;

        if (blank($customerEmail)) {
            return [
                'status' => 'failed',
                'error' => 'Kora transfer requires a destination customer email.',
                'request_data' => $data,
            ];
        }

        $payload = [
            'reference' => $reference,
            'destination' => [
                'type' => in_array($destinationType, ['mobile_money', 'bank_account'], true) ? $destinationType : 'bank_account',
                'amount' => round((float) ($data['amount'] ?? 0), 2),
                'currency' => $currency,
                'narration' => $data['narration'] ?? ('Transfer from ' . config('app.name')),
                'customer' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                ],
            ],
        ];

        if ($payload['destination']['type'] === 'bank_account') {
            if (blank($data['bank_code'] ?? null) || blank($data['account_number'] ?? null)) {
                return [
                    'status' => 'failed',
                    'error' => 'Kora bank_account transfers require bank_code and account_number.',
                    'request_data' => $data,
                ];
            }

            $payload['destination']['bank_account'] = [
                'bank_code' => $data['provider_bank_code'] ?? $data['bank_code'] ?? null,
                'account_number' => $data['account_number'] ?? null,
            ];

            if (filled($data['account_name'] ?? null)) {
                $payload['destination']['bank_account']['account_name'] = $data['account_name'];
            }
        }

        if ($payload['destination']['type'] === 'mobile_money') {
            if (blank($data['mobile_money_number'] ?? $data['phone_number'] ?? $data['mobile_number'] ?? null)) {
                return [
                    'status' => 'failed',
                    'error' => 'Kora mobile_money transfers require a destination mobile number.',
                    'request_data' => $data,
                ];
            }

            $payload['destination']['mobile_money'] = [
                'number' => $data['mobile_money_number'] ?? $data['phone_number'] ?? $data['mobile_number'] ?? null,
            ];
        }

        if ($currency === 'USD' && !empty($data['supporting_documents']) && is_array($data['supporting_documents'])) {
            $payload['destination']['supporting_documents'] = array_values($data['supporting_documents']);
        }

        if (in_array($currency, ['USD', 'GBP'], true) || ! in_array($currency, ['NGN', 'KES', 'GHS', 'XOF', 'XAF', 'EGP', 'ZAR'], true)) {
            if (filled($data['purpose_of_payment'] ?? null)) {
                $payload['destination']['purpose_of_payment'] = $data['purpose_of_payment'];
            }
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transactions/disburse/remittance',
            json_encode($payload),
            $this->headers(),
            'POST'
        );

        $status = strtolower((string) data_get($response, 'data.status', data_get($response, 'status', 'failed')));
        $success = in_array($status, $this->successStatuses(), true);
        $pending = in_array($status, $this->pendingStatuses(), true);

        return [
            'status' => $success ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $status,
            'error' => $success ? null : ($pending ? null : (data_get($response, 'message') ?? 'Kora transfer failed.')),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function requery($transaction)
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transactions/' . urlencode((string) $transaction->transaction_id),
            [],
            $this->headers(),
            'GET'
        );

        $status = strtolower((string) data_get($response, 'data.status', data_get($response, 'status', 'pending')));

        return [
            'status' => in_array($status, $this->successStatuses(), true),
            'api_status' => (bool) data_get($response, 'status', false),
            'provider_status' => $status,
            'api_response' => $response,
            'payload' => ['reference' => $transaction->transaction_id],
            'message' => data_get($response, 'message'),
        ];
    }

    public function balance(): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/balances',
            [],
            $this->headers(),
            'GET'
        );

        $ngn = data_get($response, 'data.NGN', []);

        return [
            'status' => ((bool) data_get($response, 'status', false)) ? 'success' : 'failed',
            'balance' => data_get($ngn, 'available_balance', data_get($ngn, 'availableBalance')),
            'currency' => 'NGN',
            'api_response' => $response,
        ];
    }

    public function pullBanks(): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/misc/banks?countryCode=NG',
            [],
            $this->publicHeaders(),
            'GET'
        );

        $banks = collect(data_get($response, 'data', []))
            ->filter(fn ($bank) => is_array($bank))
            ->map(fn ($bank) => [
                'bank_name' => $bank['bank_name'] ?? $bank['name'] ?? null,
                'cbn_code' => $bank['bank_code'] ?? $bank['code'] ?? null,
                'provider_codes' => ['kora' => $bank['bank_code'] ?? $bank['code'] ?? null],
                'provider_meta' => $bank,
            ])
            ->values()
            ->all();
        return ['status' => 'success', 'data' => $banks, 'api_response' => $response];
    }
}
