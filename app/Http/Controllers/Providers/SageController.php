<?php

namespace App\Http\Controllers\Providers;

use App\Models\API;
use App\Http\Controllers\Controller;

class SageController extends Controller
{
    public string $base_url;
    public string $email;
    public string $secret_key;
    public string $public_key;
    public $control;

    public function __construct(){
        $provider = API::where('slug', 'sagecloud')->first();

        $this->base_url = $provider->live_base_url ?? env('SAGE_BASE_URL');
        $this->secret_key = $provider->secret_key ?? getSettings()->sage_secret_key;
        $this->public_key = $provider->public_key ?? getSettings()->sage_public_key;
        $this->control = new Controller();
    }

    public function login(){
        $url = rtrim($this->base_url, '/') . '/merchant/authorization';
        $headers = [
            "Authorization: Basic " . base64_encode($this->public_key . ':' . $this->secret_key)
        ];

        $response = $this->basicApiCall($url, [], $headers, 'POST');

        return $response;
    }


    public function walletBalance($token)
    {
        $url = rtrim($this->base_url, '/') . '/wallet/balance';
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token,
        ];

        return $this->basicApiCall($url, [], $headers, 'GET');
    }

    public function transfer($token, $bank_code, $account_number,$account_name, $amount, $reference){
        $url = $this->base_url . "transfer/fund-transfer";

        $payload = [
            "bank_code" => $bank_code,
            "account_number" => $account_number,
            "reference" => $reference ?? $this->generateRequestId(),
            "account_name" => $account_name,
            "amount" => $amount,
            "narration" => 'Transfer from ' . config('app.name'),
        ];

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token . "",
        ];

        if (env('ENT') == 'local') {
            $response = [
                'success' => true,
                'status' => 'success',
                'message' => 'all done'
            ];
        } else {
            $response = $this->control->basicApiCall($url, json_encode($payload), $headers);
        }

        return $response;
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

                $format = [
                    'status' => $requery['success'] ?? null,
                    'api_status' => $requery['success'],
                    'api_response' => $requery ?? null,
                    'message' => $response['response_description'] ?? null,
                    'payload' => $payload,
                ];

                return $format;
            } catch (\Throwable $th) {
                $format = [
                    'status' => 'attention-required',
                    'user_status' => 'success',
                    'response' => '',
                    'api_response' => $response,
                    'payload' => $payload,
                    'message' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
                ];
            }
        }else{
            return null;
        }

    }

    public function verifyBankDetails(array $data)
    {
        $login = $this->login();

        if (!empty($login) && $login['success'] == true) {
            $token = $login['data']['token']['access_token'] ?? null;
        } else {
            return response()->json([
                'message' => 'Could not verify account details at the moment, please try again later',
            ]);
        }

        if (!empty($token)) {
            $url = rtrim($this->base_url, '/') . '/transfer/verify-bank-account';

            $payload = [
                "bank_code" => $data['bank_code'] ?? null,
                "account_number" => $data['account_number'] ?? null
            ];
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token . "",
            ];

            $verify = $this->basicApiCall($url, json_encode($payload), $headers);

            return response()->json([
                'message' => $verify,
            ]);
        } else {
            return response()->json([
                'message' => 'Could not verify account details at the moment, please try again later',
            ]);
        }
    }

}
