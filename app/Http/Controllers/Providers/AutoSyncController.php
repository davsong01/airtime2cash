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
        $data = is_array($webhook->payload ?? null)
            ? $webhook->payload
            : json_decode((string) ($webhook->request_payload ?? '{}'), true);

        $providerStatus = strtolower((string) data_get($data, 'transaction.status', data_get($data, 'data.transaction.status', data_get($data, 'status', 'pending'))));

        return [
            'status' => true,
            'status_code' => in_array($providerStatus, ['successful', 'success', 'completed'], true) ? 1 : 0,
            'provider_status' => $providerStatus,
            'api_response' => $data,
            'payload' => data_get($data, 'transaction', data_get($data, 'data.transaction', [])),
        ];
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
