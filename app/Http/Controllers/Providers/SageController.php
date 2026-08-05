<?php

namespace App\Http\Controllers\Providers;

use App\Models\API;
use App\Http\Controllers\Controller;

class SageController extends Controller
{
    public $base_url;
    public $email;
    public $secret_key;
    public $public_key;
    public $control;

    public function __construct(){
        $this->base_url = env('SAGE_BASE_URL');
        $this->secret_key = getSettings()->sage_secret_key;
        $this->public_key = getSettings()->sage_public_key;
        $this->control = new Controller();
    }

    public function login(){
        $url = $this->base_url. "merchant/authorization";
        $headers = [
            "Authorization: Basic " . base64_encode($this->public_key . ':' . $this->secret_key)
        ];

        $response = $this->basicApiCall($url, [], $headers, 'POST');

        return $response;
    }


    public function walletBalance($token)
    {
        $url = $this->base_url . "wallet/balance";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token,
        ];
        
        return $this->control->basicApiCall($url, [], $headers, 'GET');
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

                // if(env('ENT') == 'local'){
                //     $payload['reference'] = 'A2C-2024041222599193140';
                // }

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

    public function verify($token, $bank_code, $account_number)
    {
        $url = $this->base_url . "transfer/verify-bank-account";
        $payload = [
            "bank_code" => $bank_code,
            "account_number" => $account_number
        ];
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Basic " . base64_encode($this->public_key . ":" . $this->secret_key),
        ];

        $response = $this->control->basicApiCall($url, json_encode($payload), $headers);

        return $response;

    }
}
