<?php

namespace App\Http\Controllers\Providers;

use App\Models\API;
use App\Http\Requests;
use App\Models\Variation;
use App\Http\Controllers\Controller;
use App\Models\Category;

class KingsVtuController extends Controller
{
    public $api;
    public function __construct(){
        $this->api = API::first();
    }

    public function getCategories(){
        $url = $this->api->live_base_url;
        $url = $url . "category";
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' =>  'application/json',
        ];

        return $this->basicApiCall($url, [], $headers, 'GET');
        
    }

    public function getProducts($category_slug)
    {
        $url = $this->api->live_base_url;
        $url = $url . "products/".$category_slug;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' =>  'application/json',
        ];

        return $this->basicApiCall($url, [], $headers, 'GET');
    }

    public function getVariations($product_slug)
    {
        $url = $this->api->live_base_url;
        $url = $url . "variations/" . $product_slug;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' =>  'application/json',
        ];

        return $this->basicApiCall($url, [], $headers, 'GET');
    }

    public function query($request, $api, $variation, $product)
    {
        // Post data
        try {
            $url = env('ENV') == 'local' ? $this->api->sandbox_base_url : $this->api->live_base_url;
            $url = $url . "query";

            $headers = [
                'api-key: ' . $this->api->api_key,
                'public-key: ' . $this->api->public_key,
                'secret-key: ' . $this->api->secret_key,
            ];

            $payload = [
                'subscription_type' => $request['subscription_type'],
                'variation_slug' => $request['variation_slug'] ?? 'nill',
                'product_slug' => $request['product_slug'],
                'request_id' => $request['request_id'],
                'type' => $request['type'] ?? null,
                'billersCode' => $request['unique_element'],
                'phone' => $request['phone'],
                'amount' => $request['amount'],
                'url' => $url
            ];

            
            $response = $this->basicApiCall($url, $payload, $headers, 'POST');

            $extra_info = array_filter([
                "Token Amount" => $response['content']["tokenAmount"] ?? null,
                "Exchange Reference" => $response['content']["exchangeReference"] ?? null,
                "Reset Token" => $response['content']["resetToken"] ?? null,
                "Configure Token" => $response['content']["configureToken"] ?? null,
                "Units" => $response['content']["units"] ?? null,
                "Fix Charge Amount" => $response['content']["fixChargeAmount"] ?? null,
                "Tariff" => $response['content']["tariff"] ?? null,
                "Tax Amount" => $response['content']["taxAmount"] ?? null,
                "KCT 1" => $response['content']["KCT 1"] ?? null,
                "KCT 2" => $response['content']["KCT 2"] ?? null
            ]);

            if (isset($response['status']) && $response['status'] == 'success') {
                // success
                $format = [
                    'status' => 'success',
                    'user_status' => 'delivered',
                    'api_response' => $response,
                    'description' => 'Transaction successful',
                    'message' => $response['response_description'] ?? null,
                    'payload' => $payload,
                    'status_code' => 1,
                    'extras' => $response['purchased_code'] ?? null,
                    'extra_info' => !empty($extra_info) ? $extra_info : [],
                ];
            } else {
                // attention required
                $format = [
                    'status' => 'attention-required',
                    'user_status' => 'completed',
                    'description' => 'Transaction completed',
                    'api_response' => $response,
                    'message' => $response['response_description'] ?? null,
                    'payload' => $payload,
                    'status_code' => 2,
                    'extra_info' => !empty($extra_info) ? $extra_info : [],
                ];
            }
        } catch (\Throwable $th) {
            $format = [
                'status' => 'attention-required',
                'response' => '',
                'description' => 'Transaction completed',
                'api_response' => $response ?? null,
                'payload' => $payload ?? null,
                'message' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
            ];
        }

        try {
            //code...
            $this->balance($this->api);
            // $this->fetchAndUpdateBalance($this->api);
            $this->sendWarningEmail($this->api);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $format;
    }

    public function requery($transaction)
    {
        $this->api = $transaction->api;
        $request_id = $transaction->reference_id;

        try {
            $url = env('ENV') == 'local' ? $this->api->sandbox_base_url : $this->api->live_base_url;
            $url = $url . "re-query";

            $headers = [
                'api-key: ' . $this->api->api_key,
                'public-key: ' . $this->api->public_key,
                'secret-key: ' . $this->api->secret_key,
            ];

            $payload = [
                'request_id' => $request_id,
                'url' => $url
            ];

            $response = $this->basicApiCall($url, $payload, $headers, 'POST');
            
            $successCodes = ['000'];
            $failCodes = ['016'];
            
            if (isset($response['status']) && $response['status'] == 'success') {
                // success
                $format = [
                    'status' => 'success',
                    'api_status' => $response['status'],
                    'user_status' => 'delivered',
                    'api_response' => $response,
                    'message' => $response['response_description'] ?? null,
                    'payload' => $payload,
                    'status_code' => 1,
                    'purchase_code' => $response['purchase_code'] ?? null
                ];
            } elseif (isset($response['code']) && in_array($response['code'], $failCodes)) {
                // fail
                $format = [
                    'status' => 'failed',
                    'api_status' => $response['content']['status'],
                    'user_status' => 'failed',
                    'api_response' => $response,
                    'message' => $response['response_description'] ?? null,
                    'payload' => $payload,
                    'status_code' => 0,
                    'purchase_code' => $response['purchase_code'] ?? null
                ];
            } else{
                $format = [
                    'status' => 'failed',
                    'api_status' => 'no-response',
                    'user_status' => 'failed',
                    'response' => '',
                    'api_response' => $response,
                    'payload' => $payload,
                    'message' => 'NO RESPONSE',
                ];
            }
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
        
        return $format;
    }

    public function balance($no_format = null)
    {
        $url = $this->api->live_base_url;
        $url = $url . "category";
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' =>  'application/json',
        ];


        try {
            $url = env('ENV') == 'local' ? $this->api->sandbox_base_url : $this->api->live_base_url;
            $url = $url . "get-balance";

            $headers = [
                'api-key: ' . $this->api->api_key,
                'public-key: ' . $this->api->public_key,
                'secret-key: ' . $this->api->secret_key,
            ];

            $response = $this->basicApiCall($url, [], $headers, 'GET');
        
            if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data'])) {
                $result = $response;
                $balance = '#' . number_format($response['data']['wallet_balance'], 2);
                $status = 'success';
                $status_code = 1;

                $this->api->update([
                    'balance' => $response['data']['wallet_balance'],
                ]);
            } else {
                $status = 'failed';
                $status_code = 0;
                $balance = null;
            }

            $format = [
                'status' => $status,
                'balance' => $balance,
                'status_code' => $status_code,
            ];
        } catch (\Throwable $th) {
            $format = [
                'status' => 'failed',
                'status_code' => 0,
                'balance' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
            ];
        }

        if (isset($no_format)) {
            $format = [
                'status' => $status,
                'balance' => $response['contents']['balance'] ?? null,
                'status_code' => $status_code,
            ];
        }

        return $format;
    }

    public function verify($data)
    {
        // Post data
        try {
            $url = env('ENV') == 'local' ? $data['api']->sandbox_base_url : $data['api']->live_base_url;

            $url = $url . "verify-biller";

            $headers = [
                'api-key: ' . $data['api']->api_key,
                'public-key: ' . $data['api']->public_key,
                'secret-key: ' . $data['api']->secret_key,
            ];

            $payload = [
                'product_slug' => $data['request']['product_slug'],
                'variation_slug' => $data['request']['variation_name'],
                'billersCode' => $data['request']['unique_element'],
                'url' => $url
            ];

            $response = $this->basicApiCall($url, $payload, $headers, 'POST');
            
            if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']) && !empty($response['data']['Customer_Name'])) {
                $message = '';
                $message .= isset($response['data']['Customer Name']) ? 'Account Name: ' . $response['data']['Customer_Name'] : '';
                $message .= isset($response['data']['Address']) ? '<br/>Address: ' . $response['data']['Address'] : '';
                $message .= isset($response['data']['Status']) ? '<br/>Status: ' . $response['data']['Status'] : '';
                $message .= isset($response['data']['Meter Number']) ? '<br/>Meter Number: ' . $response['data']['Meter_Number'] : '';
                $message .= isset($response['data']['Meter Type']) ? '<br/>Meter Type: ' . $response['data']['Meter_Type'] : '';
                $message .= isset($response['data']['Customer_Arrears']) ? '<br/>Customer Arrears: ' . $response['data']['Customer_Arrears'] : '';
                $message .= isset($response['data']['Customer_Account_Type']) ? '<br/>Customer Account Type: ' . $response['data']['Customer_Account_Type'] : '';
                $message .= isset($response['data']['Min_Purchase_Amount']) ? '<br/>Minimum Purchase Amount: ' . $response['data']['Min_Purchase_Amount'] : '';
                $message .= isset($response['data']['Customer_Number']) ? '<br/>Customer Number: ' . $response['data']['Customer_Number'] : '';
                $message .= isset($response['data']['Current_Bouquet']) ? '<br/>Current Bouquet: ' . $response['data']['Current_Bouquet'] : '';
                $message .= isset($response['data']['Renewal_Amount']) ? '<br/>Renewal Amount: ' . $response['data']['Renewal_Amount'] : '';
                $message .= isset($response['data']['Due_Date']) ? '<br/>Due Date: ' . $response['data']['Due_Date'] : '';

                $final_response = [
                    'status' => 'success',
                    'provider' => 'VTPASS',
                    'status_code' => '1',
                    'customerName' => $response['data']['Customer Name'] ?? '',
                    'customerAddress' => $response['content']['Address'] ?? '',
                    'message' => $message . ' <br/><br/>',
                    'title' => '<strong>Please confirm that the details are correct before you make payment</strong>',
                    'renewal_amount' => $response['content']['Renewal_Amount'] ?? '',
                    'raw_response' => $response,
                ];
            } else {
                $fail_response =  $fail_response = 'Validation Error: ' . ($response['content']['error'] ?? 'Unable to verify at the moment, please try again');

                $final_response = [
                    'status' => 'failed',
                    'status_code' => '0',
                    'customerName' => '',
                    'customerAddress' => '',
                    'message' => $fail_response,
                    'title' => 'Verification Failed',
                    'raw_response' => $response,
                ];
            }
        } catch (\Throwable $th) {
            $fail_response = 'An error occured while trying to verify, please try again';
            $final_response = [
                'status' => 'failed',
                'status_code' => '500',
                'customerName' => '',
                'customerAddress' => '',
                'message' => $fail_response,
                'title' => 'Verification Failed',
                'raw_response' => $th->getMessage().' '.$th->getFile(). ' Line: '.$th->getLine(),
            ];
        }

        return $final_response;
    }
}
