<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\Airtime2CashTransactions;
use App\Models\AutoSyncWebhook;
use App\Services\AutoSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutoSyncWebhookController extends Controller
{
    public function store(Request $request, AutoSyncService $autoSync): JsonResponse
    {
        $payload = $request->json()->all();
        $providerTransaction = data_get($payload, 'transaction', []);
        $reference = $providerTransaction['reference'] ?? null;
        $requestRef = $providerTransaction['request_ref'] ?? null;
        $providedHash = (string) ($payload['hash'] ?? '');
        $provider = API::whereKey(getSettings()?->auto_share_provider_id)->where('status', 'active')->first();
        $transactionPin = (string) ($provider?->public_key ?: config('services.autosync.transaction_pin'));
        $expectedHash = $transactionPin !== '' && $reference
            ? hash('sha256', $transactionPin . ':' . $reference)
            : null;
        $signatureValid = $expectedHash !== null && hash_equals($expectedHash, $providedHash);

        $transaction = null;
        if ($reference || $requestRef) {
            $transaction = Airtime2CashTransactions::where(function ($query) use ($reference, $requestRef) {
                if ($reference) {
                    $query->where('provider_reference', $reference);
                }
                if ($requestRef) {
                    $method = $reference ? 'orWhere' : 'where';
                    $query->{$method}('provider_request_ref', $requestRef)->orWhere('transaction_id', $requestRef);
                }
            })->first();
        }

        AutoSyncWebhook::create([
            'customer_id' => $transaction?->customer_id,
            'transaction_id' => $transaction?->transaction_id,
            'provider_reference' => $reference,
            'request_ref' => $requestRef,
            'provider_status' => $providerTransaction['status'] ?? null,
            'processing_status' => $signatureValid ? 'pending' : 'rejected',
            'signature_valid' => $signatureValid,
            'headers' => $autoSync->redact($request->headers->all()),
            'payload' => $autoSync->redact($payload),
            'last_error' => $signatureValid ? null : 'AutoSync webhook signature verification failed.',
        ]);

        if (!$signatureValid) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        return response()->json(['message' => 'Webhook accepted.']);
    }
}
