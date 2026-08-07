<?php

namespace App\Http\Controllers\PaymentProcessors;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\ReservedAccountNumber;

class MonnifyController extends Controller
{
    private $api;

    public function __construct()
    {
        $this->api = PaymentGateway::where('id', 1)->first();
    }

    public function login()
    {
        $headers = [
            "Authorization: Basic " . base64_encode($this->api->api_key . ':' . $this->api->secret_key)
        ];

        $url = 'https://api.monnify.com/api/v1/auth/login';
        $response = $this->basicApiCall($url, [], $headers, 'POST');

        $accessToken = $response['responseBody']['accessToken'] ?? null;

        return $accessToken;
    }

    public function verifyTransaction($reference)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        } else {
            $url = $this->api->base_url . 'v2/transactions/' . urlencode($reference);
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token . "",
            ];

            $response = $this->basicApiCall($url, [], $headers, 'GET');

            if (
                isset($response['responseCode']) && isset($response['responseBody']) && $response['responseCode'] == 0 && $response['responseBody']['paymentStatus'] == 'PAID' &&
                $response['responseMessage'] == 'success'
            ) {
                $real = [
                    'status' => 'success',
                    'data' => $response['responseBody'],
                ];
            } else {
                $real = [
                    'status' => 'failed',
                    'data' => $response['responseMessage'],
                ];
            }

            return $real;
        }
    }

    public function verifyBVN(array $data)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        } else {
            $url = $this->api->base_url . 'v2/vas/bvn-details-match';
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token . "",
            ];

            $response = $this->basicApiCall($url, $data, $headers, 'POST');

            // if (
            //     $response && $response['responseCode'] == 0 && $response['responseBody']['paymentStatus'] == 'PAID' &&
            //     $response['responseMessage'] == 'success'
            // ) {
            //     $real = [
            //         'status' => 'success',
            //         'data' => $response['responseBody'],
            //     ];
            // } else {
            //     $real = [
            //         'status' => 'failed',
            //         'data' => $response['responseBody'],
            //     ];
            // }

            // return $real;
        }
    }

    public function createReservedAccount(array $data, int $admin_id = null)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        } else {
            $url = $this->api->base_url . 'v2/bank-transfer/reserved-accounts';
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token . "",
            ];

            $data["preferredBanks"] = $data["preferredBanks"] ?? null;
            $getAllAvailableBanks = empty($data["preferredBanks"]) ? true : false;
            $payload = json_encode([
                "customer_id" => $data["customer_id"],
                "bvn" => $data["BVN"],
                "customerEmail" => $data["customerEmail"],
                "accountName" => $data["accountName"] ?? $data["customerName"],
                "currencyCode" => "NGN",
                "contractCode" => $this->api->contract_id,
                "getAllAvailableBanks" => $data["getAllAvailableBanks"],
                "accountReference" => $this->generateRequestId(),
                'getAllAvailableBanks' => $getAllAvailableBanks,
                "preferredBanks" => $data["preferredBanks"],
            ]);

            $response = $this->basicApiCall($url, $payload, $headers, 'POST');

            if (
                $response && $response['responseCode'] == 0 &&
                $response['responseMessage'] == 'success'
            ) {
                if (isset($response['responseBody']['accounts']) && isset($response['responseBody']['bvn'])) {
                    foreach ($response['responseBody']['accounts'] as $account) {
                        ReservedAccountNumber::updateOrCreate([
                            'customer_id' => $data["customer_id"] ?? null,
                            'account_number' => $account['accountNumber'] ?? null,
                            'account_name' => $account['accountName'] ?? null,
                            'bank_name' => $account['bankName'] ?? null,
                            'bank_code' => $account['bankCode'] ?? null,
                        ], [
                            'customer_id' => $data["customer_id"] ?? null,
                            'admin_id' => $admin_id ?? null,
                            'account_reference' => $response['responseBody']['accountReference'] ?? null,
                            'account_number' => $account['accountNumber'] ?? null,
                            'account_name' => $account['accountName'] ?? null,
                            'bank_name' => $account['bankName'] ?? null,
                            'bank_code' => $account['bankCode'] ?? null,
                            'paymentgateway_id' => 1,
                            'status' => $response['responseBody']['status'] ?? null,
                            'purpose' => 'WALLET-FUNDING',
                            'bvn' => $response['responseBody']['bvn'],
                            'response' => json_encode($response)
                        ]);
                    }

                    $data = [
                        'status' => 'success',
                        'data' => '',
                    ];
                }else{
                    $data = [
                        'status' => 'failed',
                        'data' => $response['responseMessage'] ?? 'No Accounts set by Provider',
                    ];
                }

            } else {
                $data = [
                    'status' => 'failed',
                    'data' => $response['responseMessage'] ?? 'no-response',
                ];
            }

            return $data;
        }
    }

    public function deleteReservedAccount(string $account_reference)
    {
        $token = $this->login();

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        } else {
            $url = $this->api->base_url . 'v1/bank-transfer/reserved-accounts/reference/' . $account_reference;
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token . "",
            ];

            $response = $this->basicApiCall($url, [], $headers, 'DELETE');

            if (
                $response && $response['responseCode'] == 0 &&
                $response['responseMessage'] == 'success'
            ) {
                ReservedAccountNumber::where('account_reference', $account_reference)->delete();
                $data = [
                    'status' => 'success',
                    'data' => '',
                ];
            } else {
                $data = [
                    'status' => 'failed',
                    'data' => $response['responseBody'] ?? $response['responseMessage'],
                ];
            }

            return $data;
        }
    }



    public function redirectToGateway(Request $request, $transaction)
    {
        $token = $this->login();
        // $token = base64_encode($this->api->api_key . ":" . $this->api->secret_key);

        if (empty($token)) {
            return [
                'status' => 'failed',
                'status_code' => 0,
            ];
        } else {
            $url = $this->api->base_url . 'v1/merchant/transactions/init-transaction';

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token,
            ];
            $payload =  json_encode([
                "amount" => $request->amount,
                "customerName" => auth()->user()->firstname . ' ' . auth()->user()->lastname,
                "customerEmail" => auth()->user()->email,
                "paymentReference" => $request['reference'],
                "paymentDescription" => "WALLET-FUNDING",
                "currencyCode" => "NGN",
                "contractCode" => $this->api->contract_id,
                "redirectUrl" => route('payment-callback', $this->api->id),
                "paymentMethods" => ["CARD", "ACCOUNT_TRANSFER"]
            ]);

            $response = $this->basicApiCall($url, $payload, $headers, 'POST');

            $transaction->update([
                'request_data' => $payload,
                'api_response' => json_encode($response),
            ]);

            if ($response && $response['responseCode'] == 0 && $response['responseMessage'] == 'success') {
                $url = $response['responseBody']['checkoutUrl'];
                $transaction->update(['transaction_id' => $response['responseBody']['transactionReference']]);
                $data = [
                    'status' => 'success',
                    'status_code' => 1,
                    'url' => $url,
                ];
            } else {
                $transaction->update(['balance_after' => $transaction->balance_before]);
                $url = null;

                $data =  [
                    'status' => 'failed',
                    'status_code' => 0,
                ];
            }

            return $data;
        }
    }
}
