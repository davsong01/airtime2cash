<?php
namespace App\Services;

use App\Http\Controllers\TransactionController;
use App\Models\API;
use App\Models\ProviderWebhook;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

define('LIMIT', 50000);

class WebhookService {

    public function analyzeWebhookResponse($pick)
    {
        $webhooks = ProviderWebhook::with('provider')
            ->where('status', 'pending')
            ->take($pick)
            ->get();

        if ($webhooks->isEmpty()) {
            return 'No pending webhook';
        }

        foreach ($webhooks as $webhook) {
            // Check for pending/attention-required transaction logs for this reference
            $transaction = TransactionLog::where('external_reference_id', $webhook->reference)->first();

            if (in_array($transaction->status, ['attention-required','pending'])) {
                $file_name = $webhook->provider->file_name;
                $class = "App\\Http\\Controllers\\Providers\\" . $file_name;

                if (class_exists($class) && method_exists($class, 'analyzeWebhookResponse')) {
                    $analyze = app($class)->analyzeWebhookResponse($webhook);

                    if (!empty($analyze['status']) && in_array($analyze['status_code'], [1,0]) && isset($transaction)) {
                        app(TransactionController::class)->handleTransactionProcessing($transaction, $analyze);

                        $webhook->update([
                            'status' => 'resolved'
                        ]);
                    }
                }
            } else {
                $webhook->update([
                    'status' => 'analyzed'
                ]);
            }
        }
    }

    public function logWebhookResponse(Request $request, API $provider_id)
    {
        try {
            $provider = API::find($provider_id);
            dd($provider);
            if (!$provider) {
                return false;
            }

            $class = "App\\Http\\Controllers\\Providers\\" . $provider->file_name;

            if (!class_exists($class)) {
                return false;
            }

            $providerService = app($class);

            if (!method_exists($providerService, 'verifyWebhookSignature')) {
                return false;
            }

            $verifySignature = $providerService->verifyWebhookSignature($request);

            if (
                empty($verifySignature['status']) ||
                empty($verifySignature['reference'])
            ) {
                return false;
            }

            // Prevent duplicate webhook
            if (ProviderWebhook::where('reference', $verifySignature['reference'])->exists()) {
                return true;
            }

            DB::beginTransaction();

            try {
                ProviderWebhook::create([
                    'api_id'          => $provider_id,
                    'reference'       => $verifySignature['reference'],
                    'status'          => 'pending',
                    'request_payload'=> json_encode($request->all()),
                    'type'            => $request->input('type', 'transaction'),
                ]);

                DB::commit();
                return true;

            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('Webhook DB insert failed', [
                    'provider_id' => $provider_id,
                    'error'       => $e->getMessage(),
                    'payload'     => $request->all(),
                ]);

                return false;
            }

        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', [
                'provider_id' => $provider_id,
                'error'       => $e->getMessage(),
                'payload'     => $request->all(),
            ]);

            return false;
        }
    }

}
