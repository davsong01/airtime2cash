<?php

namespace App\Http\Controllers\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PaystackController extends BankTransferProviderController
{
    protected function providerSlug(): string
    {
        return 'paystack';
    }

    protected function headers(): array
    {
        return [
            'Authorization: Bearer ' . ($this->api()?->secret_key ?? ''),
            'Content-Type: application/json',
        ];
    }

    public function verifyWebhookSignature(Request $request): array
    {
        $payload = normalizeWebhookPayload($request);
        $rawBody = trim((string) $request->getContent());
        $signature = (string) $request->header('x-paystack-signature');
        $expectedSignature = blank($rawBody)
            ? null
            : hash_hmac('sha512', $rawBody, (string) ($this->api()?->secret_key ?? ''));

        if (blank($signature) || blank($expectedSignature) || ! hash_equals($expectedSignature, $signature)) {
            return [
                'status' => false,
                'reference' => data_get($payload, 'data.reference')
                    ?? data_get($payload, 'data.transaction.reference')
                    ?? data_get($payload, 'transaction.reference')
                    ?? data_get($payload, 'reference')
                    ?? data_get($payload, 'event.data.reference'),
                'message' => 'Invalid Paystack webhook signature.',
            ];
        }

        $reference = data_get($payload, 'data.reference')
            ?? data_get($payload, 'data.transaction.reference')
            ?? data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'reference')
            ?? data_get($payload, 'event.data.reference');

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
        $bankCode = $data['provider_bank_code'] ?? $data['bank_code'] ?? '';

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/bank/resolve?account_number=' . urlencode((string) ($data['account_number'] ?? '')) . '&bank_code=' . urlencode((string) $bankCode),
            [],
            $this->headers(),
            'GET'
        );

        $success = (bool) data_get($response, 'status', false);

        return response()->json([
            'status' => $success,
            'message' => data_get($response, 'message', $success ? 'Account number resolved' : 'Unable to resolve bank account.'),
            'data' => data_get($response, 'data', []),
        ], $success ? 200 : 422);
    }

    public function transfer(array $data): array
    {
        $recipientPayload = [
            'type' => 'nuban',
            'name' => $data['account_name'] ?? 'Transfer Recipient',
            'account_number' => $data['account_number'] ?? null,
            'bank_code' => $data['provider_bank_code'] ?? $data['bank_code'] ?? null,
            'currency' => 'NGN',
        ];

        $recipient = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transferrecipient',
            json_encode($recipientPayload),
            $this->headers(),
            'POST'
        );

        if (! is_array($recipient) || empty($recipient)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'error' => null,
                'request_data' => $recipientPayload,
                'api_response' => $recipient,
            ];
        }

        $recipientCode = data_get($recipient, 'data.recipient_code');

        if (blank($recipientCode)) {
            return [
                'status' => 'failed',
                'error' => data_get($recipient, 'message', 'Unable to create transfer recipient.'),
                'request_data' => $recipientPayload,
                'api_response' => $recipient,
            ];
        }

        $payload = [
            'source' => 'balance',
            'amount' => (int) round(((float) ($data['amount'] ?? 0)) * 100),
            'recipient' => $recipientCode,
            'reason' => $data['narration'] ?? ('Transfer from ' . config('app.name')),
            'reference' => $data['transaction_id'] ?? (string) Str::uuid(),
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transfer',
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
            'error' => $success ? null : ($pending ? null : (data_get($response, 'message') ?? 'Paystack transfer failed.')),
            'request_data' => array_merge($recipientPayload, $payload),
            'api_response' => ['recipient' => $recipient, 'transfer' => $response],
        ];
    }

    public function requery($transaction)
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transfer/verify/' . urlencode((string) $transaction->transaction_id),
            [],
            $this->headers(),
            'GET'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'api_status' => false,
                'provider_status' => 'pending',
                'api_response' => $response,
                'payload' => ['reference' => $transaction->transaction_id],
                'message' => 'Provider did not return a response. The transaction remains pending.',
            ];
        }

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

    public function verifyTransaction(string $reference): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transaction/verify/' . urlencode($reference),
            [],
            $this->headers(),
            'GET'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'amount' => 0,
                'api_status' => false,
                'api_response' => $response,
                'message' => 'Provider did not return a response. The transaction remains pending.',
            ];
        }

        $status = strtolower((string) data_get($response, 'data.status', data_get($response, 'status', 'failed')));
        $amount = (float) data_get($response, 'data.amount', 0);
        $success = in_array($status, $this->successStatuses(), true);

        return [
            'status' => $success ? 'success' : (in_array($status, $this->pendingStatuses(), true) ? 'pending' : 'failed'),
            'provider_status' => $status,
            'amount' => $amount / 100,
            'api_status' => (bool) data_get($response, 'status', false),
            'api_response' => $response,
            'message' => data_get($response, 'message', $success ? 'Transaction verified successfully.' : 'Transaction verification failed.'),
        ];
    }

    public function balance(): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/balance',
            [],
            $this->headers(),
            'GET'
        );

        $balanceEntries = collect(data_get($response, 'data', []))->filter(fn ($entry) => is_array($entry));
        $balanceEntry = $balanceEntries->firstWhere('currency', 'NGN') ?? $balanceEntries->first();
        $rawBalance = (float) data_get($balanceEntry, 'balance', data_get($response, 'data.balance'));

        return [
            'status' => ((bool) data_get($response, 'status', false)) ? 'success' : 'failed',
            'balance' => $rawBalance / 100,
            'currency' => data_get($balanceEntry, 'currency', data_get($response, 'data.0.currency', 'NGN')),
            'api_response' => $response,
        ];
    }

    public function redirectToGateway($request, $transaction): array
    {
        $payload = [
            'email' => auth()->user()->email,
            'amount' => (int) round(((float) $request->amount) * 100),
            'reference' => $request['reference'],
            'callback_url' => route('payment-callback', $this->api()?->id),
            'metadata' => [
                'transaction_id' => $transaction->transaction_id,
                'customer_id' => auth()->user()->customer->id,
                'reason' => 'WALLET-FUNDING',
            ],
            'channels' => ['card', 'bank', 'bank_transfer'],
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/transaction/initialize',
            json_encode($payload),
            $this->headers(),
            'POST'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'url' => null,
                'api_response' => $response,
            ];
        }

        return [
            'status' => (($response['status'] ?? false) === true && !empty($response['data']['authorization_url'] ?? null)) ? 'success' : 'failed',
            'url' => $response['data']['authorization_url'] ?? null,
            'api_response' => $response,
        ];
    }

    public function pullBanks(): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/bank',
            [],
            $this->headers(),
            'GET'
        );

        $banks = collect(data_get($response, 'data', []))
            ->filter(fn ($bank) => is_array($bank))
            ->filter(fn ($bank) => filter_var(data_get($bank, 'supports_transfer', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false)
            ->map(fn ($bank) => [
                'bank_name' => $bank['name'] ?? null,
                'cbn_code' => $bank['code'] ?? null,
                'provider_codes' => ['paystack' => $bank['code'] ?? null],
                'provider_meta' => $bank,
            ])
            ->values()
            ->all();

        return ['status' => 'success', 'data' => $banks, 'api_response' => $response];
    }
}
