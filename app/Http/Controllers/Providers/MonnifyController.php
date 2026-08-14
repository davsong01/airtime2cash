<?php

namespace App\Http\Controllers\Providers;

use App\Models\ReservedAccountNumber;
use Illuminate\Http\Request;

class MonnifyController extends BankTransferProviderController
{
    protected function providerSlug(): string
    {
        return 'monnify';
    }

    public function login()
    {
        $provider = $this->api();

        if (! $provider) {
            return null;
        }

        $headers = [
            'Authorization: Basic ' . base64_encode(($provider->api_key ?? '') . ':' . ($provider->secret_key ?? '')),
            'Content-Type: application/json',
        ];

        $url = rtrim((string) $this->baseUrl(), '/') . '/api/v1/auth/login';
        $response = $this->basicApiCall($url, [], $headers, 'POST');

        return $response['responseBody']['accessToken'] ?? null;
    }

    protected function headers(): array
    {
        $token = $this->login();

        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . ($token ?: ''),
        ];
    }

    private function sourceAccountNumber(): ?string
    {
        return data_get($this->api(), 'account_number')
            ?: data_get($this->api(), 'contract_id');
    }

    public function verifyWebhookSignature(Request $request): array
    {
        $payload = normalizeWebhookPayload($request);
        $rawBody = trim((string) $request->getContent());
        $signature = (string) $request->header('monnify-signature');
        $expectedSignature = blank($rawBody)
            ? null
            : hash_hmac('sha512', $rawBody, (string) ($this->api()?->secret_key ?? ''));

        if (blank($signature) || blank($expectedSignature) || ! hash_equals($expectedSignature, $signature)) {
            return [
                'status' => false,
                'reference' => data_get($payload, 'eventData.paymentReference')
                    ?? data_get($payload, 'eventData.transactionReference')
                    ?? data_get($payload, 'transaction.reference')
                    ?? data_get($payload, 'reference')
                    ?? data_get($payload, 'data.reference'),
                'message' => 'Invalid Monnify webhook signature.',
            ];
        }

        $reference = data_get($payload, 'eventData.paymentReference')
            ?? data_get($payload, 'eventData.transactionReference')
            ?? data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'reference')
            ?? data_get($payload, 'data.reference');

        return [
            'status' => filled($reference),
            'reference' => $reference,
            'message' => filled($reference)
                ? 'Webhook signature verified.'
                : 'Webhook reference could not be resolved.',
        ];
    }

    public function balance(): array
    {
        $token = $this->login();
        if (empty($token)) {
            return ['status' => 'failed', 'message' => 'Could not authenticate with Monnify.'];
        }

        $accountNumber = $this->sourceAccountNumber();

        if (blank($accountNumber)) {
            return ['status' => 'failed', 'message' => 'Monnify wallet account number is not configured on this provider.'];
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/wallet-balance?accountNumber=' . urlencode((string) $accountNumber),
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        return [
            'status' => (($response['requestSuccessful'] ?? false) === true && ($response['responseCode'] ?? null) === '0') ? 'success' : 'failed',
            'balance' => data_get($response, 'responseBody.availableBalance', data_get($response, 'responseBody.ledgerBalance')),
            'currency' => data_get($response, 'responseBody.currency', 'NGN'),
            'api_response' => $response,
        ];
    }

    public function verifyBankDetails(array $data)
    {
        $token = $this->login();
        if (empty($token)) {
            return response()->json([
                'status' => false,
                'message' => 'Could not verify account details at the moment, please try again later',
            ], 422);
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/account/validate?accountNumber=' . urlencode((string) ($data['account_number'] ?? '')) . '&bankCode=' . urlencode((string) ($data['provider_bank_code'] ?? $data['bank_code'] ?? '')),
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        $success = (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0');

        return response()->json([
            'status' => $success,
            'message' => $success ? 'Bank details verified successfully.' : ($response['responseMessage'] ?? 'Unable to verify account details at the moment, please try again later'),
            'data' => $response['responseBody'] ?? $response,
        ], $success ? 200 : 422);
    }

    public function verifyBvn(string $bvn): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => false,
                'message' => 'Could not authenticate with Monnify.',
            ];
        }

        $customer = auth()->user()?->customer;
        $firstName = trim((string) data_get(kycStatus('FIRST_NAME', $customer?->id ?? 0), 'value', ''));
        $middleName = trim((string) data_get(kycStatus('MIDDLE_NAME', $customer?->id ?? 0), 'value', ''));
        $lastName = trim((string) data_get(kycStatus('LAST_NAME', $customer?->id ?? 0), 'value', ''));
        $phoneNumber = trim((string) data_get(kycStatus('PHONE_NUMBER', $customer?->id ?? 0), 'value', ''));
        $dateOfBirth = trim((string) (data_get(kycStatus('DOB', $customer?->id ?? 0), 'value') ?: data_get(kycStatus('DATE_OF_BIRTH', $customer?->id ?? 0), 'value') ?: ''));
        $name = trim(collect([$firstName, $middleName, $lastName])->filter()->implode(' '));

        if (blank($name) || blank($dateOfBirth) || blank($phoneNumber)) {
            return [
                'status' => false,
                'message' => 'BVN verification requires the customer name, date of birth, and mobile number to be available.',
                'missing_fields' => array_values(array_filter([
                    blank($name) ? 'name' : null,
                    blank($dateOfBirth) ? 'dateOfBirth' : null,
                    blank($phoneNumber) ? 'mobileNo' : null,
                ])),
            ];
        }

        $payload = [
            'bvn' => $bvn,
            'name' => $name,
            'dateOfBirth' => $dateOfBirth,
            'mobileNo' => $phoneNumber,
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v1/vas/bvn-details-match',
            json_encode($payload),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'POST'
        );

        $success = (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0')
            || filter_var(data_get($response, 'responseBody.bvnInformationMatch', false), FILTER_VALIDATE_BOOL);

        return [
            'status' => $success ? 'success' : 'failed',
            'provider_status' => $success ? 'success' : (data_get($response, 'responseMessage') ?? 'failed'),
            'message' => $success ? 'BVN verified successfully.' : (data_get($response, 'responseMessage') ?? 'BVN verification failed.'),
            'api_response' => $response,
            'payload' => $payload,
        ];
    }

    public function transfer(array $data): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'error' => 'Could not authenticate with Monnify.',
                'request_data' => $data,
            ];
        }

        $sourceAccountNumber = $this->sourceAccountNumber();

        if (blank($sourceAccountNumber)) {
            return [
                'status' => 'failed',
                'error' => 'Monnify sourceAccountNumber is not configured on this provider.',
                'request_data' => $data,
            ];
        }

        $payload = [
            'amount' => (float) $data['amount'],
            'reference' => $data['transaction_id'] ?? $this->generateRequestId(),
            'narration' => $data['narration'] ?? ('Transfer from ' . config('app.name')),
            'sourceAccountNumber' => $sourceAccountNumber,
            'destinationBankCode' => $data['provider_bank_code'] ?? $data['bank_code'] ?? null,
            'destinationAccountNumber' => $data['account_number'] ?? null,
            'destinationAccountName' => $data['account_name'] ?? null,
            'currency' => 'NGN',
            'async' => true,
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/single',
            json_encode($payload),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'POST'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'error' => null,
                'request_data' => $payload,
                'api_response' => $response,
            ];
        }

        $providerStatus = strtolower((string) data_get($response, 'responseBody.status', data_get($response, 'data.status', data_get($response, 'status', 'failed'))));
        $success = in_array($providerStatus, ['success', 'successful', 'completed'], true)
            || (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0' && in_array($providerStatus, ['success', 'completed'], true));
        $pending = in_array($providerStatus, ['pending', 'processing', 'initiated', 'awaiting_processing', 'in_progress', 'pending_authorization'], true);
        $requiresAuthorization = $providerStatus === 'pending_authorization';

        return [
            'status' => $success ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $providerStatus ?: ($success ? 'success' : 'failed'),
            'requires_authorization' => $requiresAuthorization,
            'error' => $success ? null : ($pending ? null : (data_get($response, 'responseMessage') ?? 'Monnify transfer failed.')),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function authorizeTransfer(string $reference, string $authorizationCode): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'error' => 'Could not authenticate with Monnify.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        if (blank($reference) || blank($authorizationCode)) {
            return [
                'status' => 'failed',
                'error' => 'Reference and authorization code are required.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        $payload = [
            'reference' => $reference,
            'authorizationCode' => $authorizationCode,
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/single/validate-otp',
            json_encode($payload),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'POST'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'error' => null,
                'request_data' => $payload,
                'api_response' => $response,
            ];
        }

        $providerStatus = strtolower((string) data_get($response, 'responseBody.status', data_get($response, 'status', 'failed')));
        $success = (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0')
            || in_array($providerStatus, ['success', 'successful', 'completed', 'authorized'], true);
        $pending = in_array($providerStatus, ['pending', 'pending_authorization', 'awaiting_processing', 'in_progress'], true);

        return [
            'status' => $success ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $providerStatus ?: ($success ? 'success' : 'failed'),
            'error' => $success ? null : ($pending ? null : (data_get($response, 'responseMessage') ?? 'Monnify OTP authorization failed.')),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function singleTransferStatus(string $reference): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'error' => 'Could not authenticate with Monnify.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        if (blank($reference)) {
            return [
                'status' => 'failed',
                'error' => 'Reference is required.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/single/summary?reference=' . urlencode($reference),
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        if (! is_array($response) || empty($response)) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'error' => null,
                'request_data' => [
                    'reference' => $reference,
                ],
                'api_response' => $response,
            ];
        }

        $providerStatus = strtolower((string) data_get($response, 'responseBody.status', data_get($response, 'status', 'failed')));
        $success = (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0')
            || in_array($providerStatus, ['success', 'successful', 'completed'], true);
        $pending = in_array($providerStatus, ['pending', 'pending_authorization', 'awaiting_processing', 'in_progress', 'processing'], true);

        return [
            'status' => $success ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $providerStatus ?: ($success ? 'success' : 'failed'),
            'error' => $success ? null : ($pending ? null : (data_get($response, 'responseMessage') ?? 'Monnify transfer status check failed.')),
            'request_data' => [
                'reference' => $reference,
            ],
            'api_response' => $response,
        ];
    }

    public function resendOtp(string $reference): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'error' => 'Could not authenticate with Monnify.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        if (blank($reference)) {
            return [
                'status' => 'failed',
                'error' => 'Reference is required.',
                'request_data' => [
                    'reference' => $reference,
                ],
            ];
        }

        $payload = [
            'reference' => $reference,
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/disbursements/single/resend-otp',
            json_encode($payload),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'POST'
        );

        $providerStatus = strtolower((string) data_get($response, 'responseBody.status', data_get($response, 'status', 'failed')));
        $success = (($response['requestSuccessful'] ?? false) === true && (string) ($response['responseCode'] ?? '') === '0')
            || in_array($providerStatus, ['success', 'successful', 'resent'], true);

        return [
            'status' => $success ? 'success' : 'failed',
            'provider_status' => $providerStatus ?: ($success ? 'success' : 'failed'),
            'error' => $success ? null : (data_get($response, 'responseMessage') ?? 'Monnify OTP resend failed.'),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function requery($transaction)
    {
        $reference = $transaction->transaction_id
            ?: data_get($transaction, 'request_data.reference')
            ?: data_get($transaction, 'api_response.responseBody.reference')
            ?: data_get($transaction, 'reference_id');

        $response = $this->singleTransferStatus((string) $reference);
        $providerStatus = strtolower((string) data_get($response, 'provider_status', data_get($response, 'status', 'pending')));

        return [
            'status' => in_array($providerStatus, ['success', 'successful', 'completed'], true),
            'api_status' => (bool) data_get($response, 'api_response.requestSuccessful', false),
            'provider_status' => $providerStatus,
            'api_response' => $response,
            'payload' => ['reference' => $reference],
            'message' => data_get($response, 'api_response.responseMessage', data_get($response, 'error', data_get($response, 'message'))),
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'message' => 'Could not authenticate with Monnify.',
            ];
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/merchant/transactions/query?paymentReference=' . urlencode($reference),
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        $paymentStatus = strtoupper((string) data_get($response, 'responseBody.paymentStatus', 'FAILED'));
        $amountPaid = (float) data_get($response, 'responseBody.amountPaid', 0);
        $success = in_array($paymentStatus, ['PAID', 'OVERPAID'], true);

        return [
            'status' => $success ? 'success' : (in_array($paymentStatus, ['PENDING'], true) ? 'pending' : 'failed'),
            'provider_status' => $paymentStatus,
            'amount' => $amountPaid,
            'api_status' => (bool) data_get($response, 'requestSuccessful', false),
            'api_response' => $response,
            'message' => data_get($response, 'responseMessage', $success ? 'Transaction verified successfully.' : 'Transaction verification failed.'),
        ];
    }

    public function pullBanks(): array
    {
        $token = $this->login();
        if (empty($token)) {
            return ['status' => 'failed', 'message' => 'Could not authenticate with Monnify.'];
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v1/banks',
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        $banks = collect(data_get($response, 'responseBody', []))
            ->filter(fn ($bank) => is_array($bank))
            ->map(fn ($bank) => [
                'bank_name' => $bank['name'] ?? $bank['bankName'] ?? null,
                'cbn_code' => $bank['code'] ?? $bank['bankCode'] ?? null,
                'provider_codes' => ['monnify' => $bank['code'] ?? $bank['bankCode'] ?? null],
                'provider_meta' => $bank,
            ])
            ->values()
            ->all();

        return [
            'status' => 'success',
            'data' => $banks,
            'api_response' => $response,
        ];
    }

    public function createReservedAccount(array $data, int $admin_id = null)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        }

        $provider = $this->api();
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v2/bank-transfer/reserved-accounts',
            json_encode([
                'customer_id' => $data['customer_id'] ?? null,
                'bvn' => $data['BVN'] ?? null,
                'customerEmail' => $data['customerEmail'] ?? null,
                'accountName' => $data['accountName'] ?? $data['customerName'] ?? null,
                'currencyCode' => 'NGN',
                'contractCode' => $provider?->contract_id,
                'getAllAvailableBanks' => ! empty($data['preferredBanks']),
                'accountReference' => $this->generateRequestId(),
                'preferredBanks' => $data['preferredBanks'] ?? null,
            ]),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'POST'
        );

        if (
            ($response['responseCode'] ?? null) === 0 &&
            ($response['responseMessage'] ?? null) === 'success'
        ) {
            foreach (($response['responseBody']['accounts'] ?? []) as $account) {
                ReservedAccountNumber::updateOrCreate([
                    'customer_id' => $data['customer_id'] ?? null,
                    'account_number' => $account['accountNumber'] ?? null,
                    'account_name' => $account['accountName'] ?? null,
                    'bank_name' => $account['bankName'] ?? null,
                    'bank_code' => $account['bankCode'] ?? null,
                ], [
                    'customer_id' => $data['customer_id'] ?? null,
                    'admin_id' => $admin_id ?? null,
                    'account_reference' => $response['responseBody']['accountReference'] ?? null,
                    'account_number' => $account['accountNumber'] ?? null,
                    'account_name' => $account['accountName'] ?? null,
                    'bank_name' => $account['bankName'] ?? null,
                    'bank_code' => $account['bankCode'] ?? null,
                    'api_id' => $provider?->id,
                    'status' => $response['responseBody']['status'] ?? null,
                    'purpose' => 'WALLET-FUNDING',
                    'bvn' => $response['responseBody']['bvn'] ?? null,
                    'response' => json_encode($response),
                ]);
            }

            return ['status' => 'success', 'data' => ''];
        }

        return [
            'status' => 'failed',
            'data' => $response['responseMessage'] ?? 'no-response',
        ];
    }

    public function deleteReservedAccount(string $account_reference)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        }

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v1/bank-transfer/reserved-accounts/reference/' . $account_reference,
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'DELETE'
        );

        if (($response['responseCode'] ?? null) === 0 && ($response['responseMessage'] ?? null) === 'success') {
            ReservedAccountNumber::where('account_reference', $account_reference)->delete();
            return ['status' => 'success', 'data' => ''];
        }

        return [
            'status' => 'failed',
            'data' => $response['responseBody'] ?? $response['responseMessage'] ?? $response,
        ];
    }

    public function redirectToGateway(Request $request, $transaction)
    {
        $token = $this->login();
        $paymentReference = (string) $request['reference'];
        $redirectUrl = route('payment-callback', $this->api()?->id);

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        }

        $payload = json_encode([
            'amount' => $request->amount,
            'customerName' => auth()->user()->firstname . ' ' . auth()->user()->lastname,
            'customerEmail' => auth()->user()->email,
            'paymentReference' => $paymentReference,
            'paymentDescription' => 'WALLET-FUNDING',
            'currencyCode' => 'NGN',
            'contractCode' => $this->api()?->contract_id,
            'redirectUrl' => $redirectUrl,
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
        ]);


        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/api/v1/merchant/transactions/init-transaction',
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
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

        $responsePaymentReference = (string) data_get($response, 'responseBody.paymentReference');
        $responseRedirectUrl = (string) data_get($response, 'responseBody.redirectUrl');
        $checkoutUrl = data_get($response, 'responseBody.checkoutUrl');
        $monnifyTransactionReference = (string) data_get($response, 'responseBody.transactionReference');

        if (filled($monnifyTransactionReference)) {
            $requestData = json_decode((string) ($transaction->request_data ?? '{}'), true);
            $requestData = is_array($requestData) ? $requestData : [];
            $requestData['monnify_transaction_reference'] = $monnifyTransactionReference;

            $transaction->update([
                'request_data' => json_encode($requestData),
                'api_response' => json_encode($response),
            ]);
        }

        return [
            'status' => (($response['requestSuccessful'] ?? false) === true
                && $responsePaymentReference === $paymentReference
                && filled($checkoutUrl)) ? 'success' : 'failed',
            'url' => $checkoutUrl,
            'api_response' => $response,
        ];
    }
}
