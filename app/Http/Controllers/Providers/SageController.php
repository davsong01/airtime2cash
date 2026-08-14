<?php

namespace App\Http\Controllers\Providers;

use App\Models\API;
use Illuminate\Http\Request;

class SageController extends BankTransferProviderController
{
    public string $base_url;
    public string $email;
    public string $secret_key;
    public string $public_key;
    public $control;

    protected function providerSlug(): string
    {
        return 'sagecloud';
    }

    public function __construct(){
        $provider = $this->api();

        $this->base_url = providerBaseUrl($provider) ?? env('SAGE_BASE_URL');
        $this->secret_key = $provider->secret_key ?? getSettings()->sage_secret_key;
        $this->public_key = $provider->public_key ?? getSettings()->sage_public_key;
        $this->control = $this;
    }

    public function login(){
        $url = rtrim($this->base_url, '/') . '/merchant/authorization';
        $headers = [
            "Authorization: Basic " . base64_encode($this->public_key . ':' . $this->secret_key)
        ];

        $response = $this->basicApiCall($url, [], $headers, 'POST');

        return $response;
    }


    private function fetchWalletBalance(?string $token = null)
    {
        if (blank($token)) {
            $login = $this->login();

            if (empty($login) || ($login['success'] ?? false) !== true) {
                return [
                    'status' => 'failed',
                    'message' => 'Could not authenticate with SageCloud.',
                    'api_response' => $login ?? null,
                ];
            }

            $token = data_get($login, 'data.token.access_token');

            if (blank($token)) {
                return [
                    'status' => 'failed',
                    'message' => 'SageCloud did not return a valid access token.',
                    'api_response' => $login,
                ];
            }
        }

        $url = rtrim($this->base_url, '/') . '/wallet/balance';

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token,
        ];

        return $this->basicApiCall($url, [], $headers, 'GET');
    }



    public function transfer(array $data): array
    {
        $login = $this->login();

        if (
            empty($login) ||
            ($login['success'] ?? false) !== true
        ) {
            return [
                'status' => 'failed',
                'error' => 'Could not authenticate with SageCloud.',
                'request_data' => $data,
                'api_response' => $login ?? 'NO RESPONSE WHEN LOGGING IN',
            ];
        }

        $token = data_get($login, 'data.token.access_token');

        if (blank($token)) {
            return [
                'status' => 'failed',
                'error' => 'SageCloud did not return a valid access token.',
                'request_data' => $data,
                'api_response' => $login,
            ];
        }

        $url = rtrim($this->base_url, '/').'/transfer/fund-transfer';
        // $url = rtrim($this->base_url, '/').'/transfer';


        $payload = [
            'bank_code' => $data['provider_bank_code'] ?? $data['bank_code'],
            'account_number' => $data['account_number'],
            'account_name' => $data['account_name'],
            'amount' => $data['amount'],
            'reference' => $data['transaction_id'] ?? $this->generateRequestId(),
            'narration' => 'Transfer from '.config('app.name'),
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '.$token,
        ];

        $response = $this->control->basicApiCall(
            $url,
            json_encode($payload),
            $headers
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

        $providerStatus = strtolower((string) data_get($response, 'data.transaction.status', data_get($response, 'status', 'failed')));
        $successful = in_array($providerStatus, ['successful', 'success', 'completed'], true)
            || (($response['success'] ?? false) === true && ($response['status'] ?? null) === 'success');
        $pending = in_array($providerStatus, ['pending', 'processing', 'initiated'], true);

        return [
            'status' => $successful ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $providerStatus,
            'error' => $successful
                ? ''
                : ($pending ? '' : ($response['message'] ?? 'Bank transfer failed.')),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function requery($transaction)
    {
        $login = $this->login();
        $status = 'failed';

        if (!empty($login) && $login['success'] == true) {
            $token = $login['data']['token']['access_token'] ?? null;

            try {
                $url = $this->base_url . "transaction/requery";
                $payload = [
                    "reference" => $transaction->transaction_id,
                ];

                $headers = [
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $token . "",
                ];

                $requery = $this->control->basicApiCall($url, json_encode($payload), $headers);

                if (! is_array($requery) || empty($requery)) {
                    return [
                        'status' => 'pending',
                        'api_status' => false,
                        'provider_status' => 'pending',
                        'api_response' => $requery,
                        'payload' => $payload,
                        'message' => 'Provider did not return a response. The transaction remains pending.',
                    ];
                }

                $format = [
                    'status' => $requery['success'] ?? null,
                    'api_status' => $requery['success'],
                    'api_response' => $requery ?? null,
                    'message' => $requery['response_description'] ?? null,
                    'payload' => $payload,
                ];

                return $format;
            } catch (\Throwable $th) {
                $format = [
                    'status' => 'attention-required',
                    'user_status' => 'success',
                    'response' => '',
                    'api_response' => $requery ?? null,
                    'payload' => $payload,
                    'message' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
                ];
            }
        }else{
            return null;
        }

    }

    public function balance(): array
    {
        $response = $this->fetchWalletBalance();
        $walletBalance = data_get($response, 'api_response.general_wallet.balance')
            ?? data_get($response, 'data.api_response.general_wallet.balance')
            ?? data_get($response, 'data.general_wallet.balance')
            ?? data_get($response, 'general_wallet.balance')
            ?? 0;

        return [
            'status' => ((bool) data_get($response, 'success', false)) ? 'success' : 'failed',
            'balance' => (float) $walletBalance,
            'currency' => data_get($response, 'currency', data_get($response, 'data.currency', 'NGN')),
            'api_response' => $response,
        ];
    }

    public function pullBanks(): array
    {
        $login = $this->login();

        if (empty($login) || ($login['success'] ?? false) !== true) {
            return [
                'status' => 'failed',
                'message' => 'Could not authenticate with SageCloud.',
                'api_response' => $login ?? null,
            ];
        }

        $token = data_get($login, 'data.token.access_token');

        if (blank($token)) {
            return [
                'status' => 'failed',
                'message' => 'SageCloud did not return a valid access token.',
                'api_response' => $login,
            ];
        }

        $response = $this->basicApiCall(
            rtrim($this->base_url, '/') . '/transfer/get-transfer-data',
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            'GET'
        );

        $rawBanks = data_get($response, 'banks')
            ?? data_get($response, 'data.banks')
            ?? data_get($response, 'data', []);

        $banks = collect($rawBanks)
            ->filter(fn ($bank) => is_array($bank))
            ->map(fn ($bank) => [
                'bank_name' => $bank['bank_name'] ?? $bank['name'] ?? null,
                'cbn_code' => $bank['cbn_code'] ?? $bank['bank_code'] ?? $bank['code'] ?? null,
                'provider_codes' => ['sagecloud' => $bank['cbn_code'] ?? $bank['bank_code'] ?? $bank['code'] ?? null],
                'provider_meta' => $bank,
            ])
            ->values()
            ->all();

        return [
            'status' => 'success',
            'banks' => $banks,
            'api_response' => $response,
        ];
    }

    public function verifyWebhookSignature(Request $request): array
    {
        $payload = normalizeWebhookPayload($request);
        $authorization = (string) $request->header('authorization');
        $expectedAuthorization = 'Bearer ' . (string) ($this->secret_key ?? '');

        if (blank($authorization) || ! hash_equals($expectedAuthorization, $authorization)) {
            return [
                'status' => false,
                'reference' => data_get($payload, 'transaction.reference')
                    ?? data_get($payload, 'data.transaction.reference')
                    ?? data_get($payload, 'reference'),
                'message' => 'Invalid SageCloud webhook authorization.',
            ];
        }

        $reference = data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'data.transaction.reference')
            ?? data_get($payload, 'reference');

        return [
            'status' => filled($reference),
            'reference' => $reference,
            'message' => filled($reference)
                ? 'Webhook signature verified.'
                : 'Webhook reference could not be resolved.',
        ];
    }

    public function analyzeWebhookResponse($webhook): array
    {
        return parent::analyzeWebhookResponse($webhook);
    }

    public function verifyBankDetails(array $data)
    {
        $login = $this->login();

        if (empty($login) || ($login['success'] ?? false) !== true) {
            return response()->json([
                'status' => false,
                'message' => 'Could not verify account details at the moment, please try again later',
                'data' => $login ?? null,
                'raw_response' => $login ?? null,
            ]);
        }

        $token = data_get($login, 'data.token.access_token');

        if (blank($token)) {
            return response()->json([
                'status' => false,
                'message' => 'SageCloud did not return a valid access token.',
                'data' => $login,
                'raw_response' => $login,
            ]);
        }

        $url = rtrim($this->base_url, '/') . '/transfer/verify-bank-account';

        $payload = [
            'bank_code' => $data['provider_bank_code'] ?? $data['bank_code'] ?? null,
            'account_number' => $data['account_number'] ?? null,
        ];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ];

        $verify = $this->basicApiCall($url, json_encode($payload), $headers);
        $success = (bool) data_get($verify, 'success', false);
        $message = data_get($verify, 'message')
            ?? data_get($verify, 'response_description')
            ?? ($success ? 'Bank details verified successfully.' : 'Unable to verify account details at the moment, please try again later');

        return response()->json([
            'status' => $success,
            'message' => $message,
            'data' => $verify,
            'raw_response' => $verify,
        ]);
    }

}
