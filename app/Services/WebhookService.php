<?php

namespace App\Services;

use App\Http\Controllers\Providers\SageController;
use App\Http\Controllers\TransactionController;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\TransactionLog;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

define('LIMIT', 50000);

class WebhookService
{
    public function analyzeWebhookResponse($pick)
    {
        $webhooks = Webhook::with(['provider', 'transaction', 'transactionLog'])
            ->where('processing_status', 'pending')
            ->where('signature_valid', true)
            ->take($pick)
            ->get();

        if ($webhooks->isEmpty()) {
            return 'No pending webhook';
        }

        foreach ($webhooks as $webhook) {
            app(WebhookProcessor::class)->process($webhook);
        }

        return 'Webhook queue processed successfully.';
    }

    public function logWebhookResponse(Request $request, int $providerId)
    {
        try {
            $provider = API::find($providerId);

            if (! $provider) {
                return false;
            }

            $payload = $request->all();
            if (empty($payload)) {
                $rawContent = trim((string) $request->getContent());
                if ($rawContent !== '') {
                    $decodedContent = json_decode($rawContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedContent)) {
                        $payload = $decodedContent;
                    }
                }
            }

            foreach (['transaction', 'data'] as $key) {
                if (isset($payload[$key]) && is_string($payload[$key])) {
                    $decoded = json_decode($payload[$key], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $payload[$key] = $decoded;
                    }
                }
            }

            $headers = $this->normalizeHeaders($request->headers->all());
            $controller = resolveProviderController($provider);
            $verification = ['status' => true, 'reference' => null];

            if ($controller && method_exists($controller, 'verifyWebhookSignature')) {
                $verification = (array) $controller->verifyWebhookSignature($request);
            }

            if (! (bool) ($verification['status'] ?? false)) {
                return response()->json([
                    'status' => false,
                    'message' => $verification['message'] ?? 'Invalid webhook signature.',
                ], 403);
            }

            $providerReference = $this->extractProviderReference($payload, $verification['reference'] ?? null);
            $requestRef = $this->extractRequestReference($payload);
            $transactionId = $this->extractTransactionId($payload, $providerReference, $requestRef);
            $providerStatus = $this->extractProviderStatus($payload);
            $customerId = $this->resolveCustomerId($transactionId);

            $duplicate = Webhook::query()
                ->where('api_id', $provider->id)
                ->where(function ($query) use ($providerReference, $requestRef, $transactionId) {
                    if (filled($providerReference)) {
                        $query->orWhere('provider_reference', $providerReference);
                    }

                    if (filled($requestRef)) {
                        $query->orWhere('request_ref', $requestRef);
                    }

                    if (filled($transactionId)) {
                        $query->orWhere('transaction_id', $transactionId);
                    }
                })
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status' => true,
                    'message' => 'Duplicate webhook ignored.',
                ], 200);
            }

            $webhook = new Webhook();

            $webhook->fill([
                'api_id' => $provider->id,
                'customer_id' => $customerId,
                'transaction_id' => $transactionId,
                'provider_reference' => $providerReference,
                'request_ref' => $requestRef,
                'provider_status' => $providerStatus,
                'processing_status' => 'pending',
                'signature_valid' => true,
                'headers' => $headers,
                'payload' => $payload,
                'last_error' => null,
            ]);

            DB::transaction(function () use ($webhook) {
                $webhook->save();
            });

            return response()->json([
                'status' => true,
                'message' => 'Webhook logged successfully.',
            ], 200);
        } catch (\Throwable $exception) {
            Log::error('Webhook processing failed', [
                'provider_id' => $providerId,
                'error' => $exception->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Webhook could not be logged.',
            ], 200);
        }
    }

    private function resolveCustomerId(?string $transactionId): ?int
    {
        if (blank($transactionId)) {
            return null;
        }

        $airtimeTransaction = Airtime2CashTransactions::where('transaction_id', $transactionId)->first();

        if ($airtimeTransaction) {
            return $airtimeTransaction->customer_id;
        }

        $transactionLog = TransactionLog::where('transaction_id', $transactionId)->first();

        return $transactionLog?->customer_id;
    }

    private function extractProviderReference(array $payload, ?string $fallback = null): ?string
    {
        return $this->firstString([
            $fallback,
            data_get($payload, 'transaction.reference'),
            data_get($payload, 'data.transaction.reference'),
            data_get($payload, 'reference'),
            data_get($payload, 'provider_reference'),
            data_get($payload, 'transaction_id'),
        ]);
    }

    private function extractRequestReference(array $payload): ?string
    {
        return $this->firstString([
            data_get($payload, 'transaction.request_ref'),
            data_get($payload, 'data.transaction.request_ref'),
            data_get($payload, 'request_ref'),
        ]);
    }

    private function extractTransactionId(array $payload, ?string $providerReference, ?string $requestRef): ?string
    {
        $candidates = array_filter([
            data_get($payload, 'transaction_id'),
            data_get($payload, 'transaction.reference'),
            data_get($payload, 'data.transaction.reference'),
            $requestRef,
            $providerReference,
        ]);

        foreach ($candidates as $candidate) {
            $transactionId = (string) $candidate;

            if (Airtime2CashTransactions::where('transaction_id', $transactionId)->exists()) {
                return $transactionId;
            }

            if (TransactionLog::where('transaction_id', $transactionId)->exists()) {
                return $transactionId;
            }
        }

        return $this->firstString($candidates);
    }

    private function extractProviderStatus(array $payload): ?string
    {
        return $this->firstString([
            data_get($payload, 'provider_status'),
            data_get($payload, 'transaction.status'),
            data_get($payload, 'data.transaction.status'),
            data_get($payload, 'status'),
        ]);
    }

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(function ($value) {
                if (is_array($value) && count($value) === 1) {
                    return $value[0];
                }

                return $value;
            })
            ->all();
    }

    private function firstString(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
