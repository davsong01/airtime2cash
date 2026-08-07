<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\Product;
use App\Services\AutoSyncService;
use App\Services\AutoSyncSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class AutoSyncController extends Controller
{
    private function credentials()
    {
        return API::where('slug', 'autosync')->firstOrFail();
    }


    public function query(
        Airtime2CashTransactions $transaction,
        string $otp,
        API $provider
    ): array {
        return app(AutoSyncService::class)->complete(
            transaction: $transaction,
            otp: $otp,
            provider: $provider,
        );
    }

    // public function requery($transaction)
    // {
    //     $api = $transaction->api;
    //     $external_reference_id = $transaction->external_reference_id;
    //     try {
    //         $url =  $url = $api->live_base_url ."transaction/{$external_reference_id}";

    //         $headers = [
    //             'Content-Type: application/json',
    //             'Authorization: Bearer ' . $api->api_key,
    //         ];

    //         $res = $this->basicApiCall($url, [], $headers, 'GET');

    //         return $this->formatResponse($res);

    //     } catch (\Throwable $th) {
    //         return [
    //             'status' => 'attention-required',
    //             'user_status' => 'completed',
    //             'api_response' => isset($res) ? json_encode($res) : '',
    //             'description' => 'Transaction completed',
    //             'message' => $res['message'] ?? null,
    //             'status_code' => 2,
    //             'failure_reason' => $th->getMessage().' Line: '.$th->getLine().' File: '.$th->getFile(),
    //             'extras' => null,
    //         ];
    //     }

    //     return $format;
    // }

    // private function formatResponse($res, $payload=null)
    // {
    //     $transaction = $res['data']['transaction'] ?? $res['transaction'];

    //     $status = strtolower($transaction['status'] ?? 'attention-required');

    //     $base = [
    //         'api_response' => $res,
    //         'payload' => $payload,
    //         'extras' => null,
    //     ];

    //     return match ($status) {
    //         'successful' => array_merge($base, [
    //             'status' => 'delivered',
    //             'user_status' => 'delivered',
    //             'description' => 'Transaction successful',
    //             'message' => $res['data']['msg'] ?? null,
    //             'status_code' => 1,
    //             'external_reference_id' => $res['data']['transaction']['reference'] ?? null,
    //         ]),

    //         // 'completed' => array_merge($base, [
    //         //     'status' => 'delivered',
    //         //     'user_status' => 'delivered',
    //         //     'description' => 'Transaction successful',
    //         //     'message' => $res['data']['msg'] ?? null,
    //         //     'status_code' => 1,
    //         // ]),

    //         'pending' => array_merge($base, [
    //             'status' => 'attention-required',
    //             'user_status' => 'completed',
    //             'description' => 'Transaction pending',
    //             'message' => $res['message'] ?? null,
    //             'failure_reason' => $res['message'] ?? 'Pending',
    //             'status_code' => 2,
    //             'external_reference_id' => $res['data']['transaction']['reference'] ?? null,
    //         ]),

    //         'failed' => array_merge($base, [
    //             'status' => 'failed',
    //             'user_status' => 'failed',
    //             'description' => 'Transaction failed',
    //             'message' => $res['message'] ?? null,
    //             'failure_reason' => $res['message'] ?? 'Unknown Reason',
    //             'status_code' => 0,
    //             'external_reference_id' => $res['data']['transaction']['reference'] ?? null,
    //         ]),

    //         default => array_merge($base, [
    //             'status' => 'attention-required',
    //             'user_status' => 'completed',
    //             'description' => 'Transaction requires attention',
    //             'message' => $res['message'] ?? null,
    //             'status_code' => 2,
    //             'external_reference_id' => $res['data']['transaction']['reference'] ?? null,
    //         ]),
    //     };
    // }

    // public function balance($api, $no_format = null)
    // {
    //     try {
    //         $url = $api->live_base_url;
    //         $url = "https://simhosting.ogdams.ng/api/v1/get/balances";

    //         $headers = [
    //             'Content-Type: application/json',
    //             'Authorization: Bearer ' . $api->api_key,
    //         ];


    //         $curl = curl_init();

    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => 'https://simhosting.ogdams.ng/api/v1/get/balances',
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'GET',
    //             CURLOPT_HTTPHEADER => array(
    //                 "Authorization: Bearer {$api->api_key}",
    //             ),
    //         ));

    //         $response = curl_exec($curl);

    //         curl_close($curl);

    //         $response = json_decode($response, true);

    //         if (isset($response['code']) && $response['code'] == 200 && $response['status'] == true) {
    //             $balance = '<br>';

    //             foreach($response['data']['msg'] as $key=>$value){
    //                 if($value > 0) $balance .= $key . ' : '. $value .'<br>';
    //             }

    //             $status = 'success';
    //             $status_code = 1;

    //             $api->update([
    //                 'balance' => $response['data']['msg']['mainBalance'] ?? null,
    //             ]);
    //         } else {
    //             $status = 'failed';
    //             $status_code = 0;
    //             $balance = null;
    //         }

    //         $format = [
    //             'status' => $status,
    //             'balance' => $balance,
    //             'status_code' => $status_code,
    //         ];
    //     } catch (\Throwable $th) {
    //         $format = [
    //             'status' => 'failed',
    //             'status_code' => 0,
    //             'balance' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
    //         ];
    //     }

    //     if (isset($no_format)) {
    //         $format = [
    //             'status' => $status,
    //             'balance' => $response['data']['msg']['mainBalance'] ?? null,
    //             'status_code' => $status_code,
    //         ];
    //     }

    //     return $format;
    // }

    public function verifyWebhookSignature(Request $request)
    {
        $reference = $request->input('transaction.reference');

        if (!$reference || !$request->has('hash')) {
            return ['status' => false, 'message' => 'Missing reference or hash'];
        }

        $hash = hash('sha256', sprintf('%s:%s', '1234', $reference));

        if (!hash_equals($request->input('hash'), $hash)) {
            return ['status' => false, 'message' => 'Invalid signature'];
        }

        return [
            'status' => true,
            'reference' => $reference
        ];
    }

    public function analyzeWebhookResponse($webhook){
        $data = json_decode($webhook->request_payload, true);
        return $this->formatResponse($data);
    }

    public function dummySuccess(){
        $response = '{
        "status": "ok",
        "message": "Request successfully",
        "data": {
            "transaction": {
            "id": 95,
            "user_id": 1,
            "user_product_id": 6,
            "user_variation_id": null,
            "reference": "9bdbe400-76da-4d12-bccc-90d040704dc8",
            "request_ref": "pZxmX4qjpOLRCx8d6jRc",
            "type": "MTN Gifting",
            "details": "MTN Gifting 15GB Weekly Digital Bundle sent to 07047341144",
            "amount": "2000.00",
            "status": "successful",
            "request_data": {
                "phone": "07047341144",
                "product_id": 2,
                "variation_code": "NACT_NG_Data_2003",
                "request_ref": "pZxmX4qjpOLRCx8d6jRc"
            },
            "created_at": "2024-04-21T08:14:13.000000Z",
            "updated_at": "2024-04-21T08:14:29.000000Z",
            "gateway": {
                "id": 7,
                "name": "MTN Gateway",
                "status": "connected",
                "phone": "08134679853",
                "is_site": false,
                "created_at": "2024-04-12T06:35:38.000000Z"
            },
            "logs": [
                {
                "id": 604,
                "user_id": 1,
                "ip_address": "69.57.163.195",
                "logger_type": "App\\\\Models\\\\Transaction",
                "logger_id": 95,
                "message": "",
                "data": {
                    "name": "Unknown",
                    "subscriptionId": "",
                    "productId": "416",
                    "productName": "15GB Weekly Digital Bundle",
                    "rechargeType": "Normal",
                    "phoneNumber": "2347047341144",
                    "traceId": "UgeFwW6dVvcEq3JH7HL79pQZ5N7mQR",
                    "currency": "NGN",
                    "feeBearer": "M",
                    "amount": 2000,
                    "autoRenew": false
                },
                "is_admin_only": false,
                "created_at": "2024-04-21T08:14:20.000000Z",
                "updated_at": "2024-04-21T08:14:20.000000Z"
                },
                {
                "id": 605,
                "user_id": 1,
                "ip_address": "69.57.163.195",
                "logger_type": "App\\\\Models\\\\Transaction",
                "logger_id": 95,
                "message": "",
                "data": {
                    "Pin": "1234",
                    "TranId": "54020240421091419869719",
                    "PhoneNumber": "8134679853"
                },
                "is_admin_only": false,
                "created_at": "2024-04-21T08:14:28.000000Z",
                "updated_at": "2024-04-21T08:14:28.000000Z"
                }
            ]
            }
        }
        }
        ';

        return $response;
    }

}
