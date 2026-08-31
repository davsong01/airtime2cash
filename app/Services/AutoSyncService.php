<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\ApiRequestLog;
use App\Services\BvnVerificationBillingService;
use App\Services\ProviderUtilityService;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;


class AutoSyncService
{
    private function resolveBaseUrl(API $provider): string
    {
        $baseUrl = app()->environment(['local', 'testing'])
            ? $provider->sandbox_base_url
            : $provider->live_base_url;

        if (blank($baseUrl)) {
            throw new RuntimeException('The AutoSync provider endpoint is not configured.');
        }

        return rtrim($baseUrl, '/');
    }

    public function initiate(Airtime2CashTransactions $transaction, API $provider): array
    {
        $productName = strtolower($transaction->product->name);

        $payload = [
            'request_ref' => $transaction->transaction_id,
            'phone' => $transaction->phone_numbers,
            'product_id' => match (true) {
                str_contains($productName, 'mtn') => 'mtn',
                str_contains($productName, 'glo') => 'glo',
                str_contains($productName, 'airtel') => 'airtel',
                str_contains($productName, '9mobile'),
                str_contains($productName, 'etisalat') => '9mobile',
                default => throw new RuntimeException(
                    'Unsupported network: '.$transaction->product->name
                ),
            },
            'amount' => $transaction->total_amount,
            'sharePin' => request()->share_pin,
        ];

        $endpoint = $this->resolveBaseUrl($provider).'/airtime/cash';
        $startedAt = microtime(true);

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$provider->api_key,
        ];

        $response = null;
        $data = null;

        try {
            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->post($endpoint, $payload);

            $data = $response->json();

        } catch (ConnectionException $exception) {
            $this->writeLog(
                'initiate',
                $provider->id,
                $endpoint,
                $headers,
                $payload,
                null,
                null,
                ['transaction_id' => $transaction->transaction_id, 'customer_id' => auth()->id()],
                $startedAt,
                $exception->getMessage()
            );

            throw new RuntimeException(
                'AutoShare could not be reached. Please try again.',
                0,
                $exception
            );
        }

        $this->writeLog(
            'initiate',
            $provider->id,
            $endpoint,
            $headers,
            $payload,
            $response,
            is_array($data)
                ? $data
                : ['raw' => $response->body()],
            ['transaction_id' => $transaction->transaction_id, 'customer_id' => auth()->id()],
            $startedAt
        );

        if (
            $response->successful()
            && isset($data['data']['transaction']['reference'])
        ) {
            $transaction->update([
                'provider_request_ref' => $data['data']['transaction']['reference'],
                'provider_response' => $data,
                'provider_status' => $data['data']['transaction']['status'] ?? null,
            ]);
        }

        if (! is_array($data)) {
            throw new RuntimeException(
                'Something went wrong, please try again.'
            );
        }

        if (! $response->successful()) {
            $message = $data['message']
                ?? $data['error']
                ?? 'Auto Transfer validation failed.';

            if (
                isset($data['data'])
                && is_array($data['data'])
                && ! empty($data['data'])
            ) {
                $errors = [];

                foreach ($data['data'] as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errors = array_merge($errors, $fieldErrors);
                    }
                }

                if ($errors !== []) {
                    $message = implode(' ', $errors);
                }
            }

            throw new RuntimeException($message);
        }

        return $data;
    }

    public function complete(Airtime2CashTransactions $transaction, string $otp, API $provider): array
    {
        if (blank($transaction->provider_request_ref)) {
            throw new RuntimeException('The provider transaction reference is missing.');
        }

        $endpoint = $this->resolveBaseUrl($provider).'/airtime/cash/'.rawurlencode($transaction->provider_request_ref);
        $payload = ['otp' => $otp];
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$provider->api_key,
        ];
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->post($endpoint, $payload);

            $data = $response->json();

            $this->writeLog(
                'complete',
                $provider->id,
                $endpoint,
                $headers,
                $payload,
                $response,
                is_array($data) ? $data : ['raw' => $response->body()],
                [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt
            );
        } catch (ConnectionException $exception) {
            $this->writeLog(
                'complete',
                $provider->id,
                $endpoint,
                $headers,
                $payload,
                null,
                null,
                [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt,
                $exception->getMessage()
            );

            throw new RuntimeException('AutoSync could not be reached. Please try again.', 0, $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('AutoSync returned an invalid response.');
        }

        $providerStatus = strtolower((string) data_get($data, 'data.transaction.status', 'failed'));

        $transaction->update([
            'provider_status' => $providerStatus,
            'bank_transfer_api_response' => json_encode($data, JSON_THROW_ON_ERROR),
            'completed_at' => $providerStatus === 'successful'
                ? ($transaction->completed_at ?? now())
                : $transaction->completed_at,
        ]);

        if($providerStatus == 'successful'){
            $this->balance($provider);
            app(ProviderUtilityService::class)->sendWarningEmail($provider);
        }

        if (! $response->successful() || ($data['status'] ?? null) !== 'ok') {
            throw new RuntimeException(
                $data['message']
                    ?? $data['error']
                    ?? 'Auto Transfer could not be completed.'
            );
        }

        return $data;
    }

    public function queryTransaction(Airtime2CashTransactions $transaction, API $provider): array
    {
        $reference = $transaction->provider_request_ref ?: $transaction->transaction_id;

        if (blank($reference)) {
            throw new RuntimeException('The provider transaction reference is missing.');
        }

        $endpoint = $this->resolveBaseUrl($provider).'/transaction/'.rawurlencode($reference);
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$provider->api_key,
        ];
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->get($endpoint);

            $data = $response->json();

            $this->writeLog(
                'query_transaction',
                $provider->id,
                $endpoint,
                $headers,
                ['reference' => $reference],
                $response,
                is_array($data) ? $data : ['raw' => $response->body()],
                [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt,
                null,
                null,
                'GET'
            );
        } catch (ConnectionException $exception) {
            $this->writeLog(
                'query_transaction',
                $provider->id,
                $endpoint,
                $headers,
                ['reference' => $reference],
                null,
                null,
                [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt,
                $exception->getMessage(),
                null,
                'GET'
            );

            throw new RuntimeException('AutoSync could not be reached. Please try again.', 0, $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('AutoSync returned an invalid response.');
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                $data['message']
                    ?? $data['error']
                    ?? 'AutoSync transaction lookup failed.'
            );
        }

        return $data;
    }

    public function resendOtp(Airtime2CashTransactions $transaction, API $provider, array $context = []): array
    {
        if (blank($transaction->provider_request_ref)) {
            throw new RuntimeException('The provider transaction reference is missing.');
        }

        $endpoint = $this->resolveBaseUrl($provider).'/airtime/cash/'.rawurlencode($transaction->provider_request_ref).'/resend-otp';
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$provider->api_key,
        ];
        $payload = [];
        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->post($endpoint, $payload);

            $data = $response->json();

            $this->writeLog(
                'resend_otp',
                $provider->id,
                $endpoint,
                $headers,
                $payload,
                $response,
                is_array($data) ? $data : ['raw' => $response->body()],
                $context + [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt
            );
        } catch (ConnectionException $exception) {
            $this->writeLog(
                'resend_otp',
                $provider->id,
                $endpoint,
                $headers,
                $payload,
                null,
                null,
                $context + [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ],
                $startedAt,
                $exception->getMessage()
            );

            throw new RuntimeException('AutoSync could not be reached. Please try again.', 0, $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('AutoSync returned an invalid response. Please try again.');
        }

        $transaction->update([
            'provider_status' => data_get(
                $data,
                'data.transaction.status',
                $transaction->provider_status ?? 'pending'
            ),
            'provider_response' => $data,
        ]);

        if (! $response->successful() || ($data['status'] ?? null) === 'error') {
            throw new RuntimeException(
                $data['message']
                    ?? $data['error']
                    ?? 'The OTP could not be resent.'
            );
        }

        return $data;
    }

    private function writeLog(
        string $operation,
        int $apiId,
        string $endpoint,
        array $headers,
        array $payload,
        ?Response $response,
        ?array $responseBody,
        array $context,
        float $startedAt,
        ?string $error = null,
        ?int $fallbackStatus = null,
        string $method = 'POST'
    ): void {
        if (! Schema::hasTable('api_request_logs')) {
            return;
        }

        ApiRequestLog::create([
            'api_id' => $apiId ?? null,
            'customer_id' => $context['customer_id'] ?? null,
            'transaction_id' => $context['transaction_id'] ?? null,
            'operation' => $operation,
            'method' => $method,
            'endpoint' => $endpoint,
            'request_headers' => $headers,
            'request_payload' => $this->redact($payload),
            'response_status' => $response?->status() ?? $fallbackStatus,
            'response_headers' => $response?->headers(),
            'response_body' => $responseBody ? $this->redact($responseBody) : null,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), ['sharepin', 'share_pin', 'otp', 'authorization', 'token', 'access_token'], true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    public function settle(Airtime2CashTransactions $transaction, float $completedAmount, array $providerResponse, ?int $resolvedBy = null): Airtime2CashTransactions
    {
        if ($completedAmount <= 0 || $completedAmount > (float) $transaction->total_amount) {
            throw new RuntimeException('AutoSync returned an invalid completed amount.');
        }

        return DB::transaction(function () use ($transaction, $completedAmount, $providerResponse, $resolvedBy) {
            $lockedTransaction = Airtime2CashTransactions::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($lockedTransaction->status === 'approved') {
                return $lockedTransaction;
            }

            if ($lockedTransaction->transfer_mode !== 'auto_share' || $lockedTransaction->payment_method !== 'Transfer to Wallet') {
                throw new RuntimeException('This transaction cannot be settled automatically.');
            }

            $customer = $lockedTransaction->customer()->lockForUpdate()->firstOrFail();
            $amountCharged = ((float) $lockedTransaction->charge_rate / 100) * $completedAmount;
            $amountPaid = $completedAmount - $amountCharged;

            $creditResult = app(BvnVerificationBillingService::class)->applyPendingChargeOnIncomingCredit($customer, $amountPaid, [
                'transaction_id' => $lockedTransaction->transaction_id,
                'credit_reason' => 'Auto Airtime2Cash Payment',
                'payment_method' => 'wallet',
                'fee_description' => 'BVN verification fee was collected from wallet funding.',
            ]);

            $lockedTransaction->update([
                'amount_charged' => $amountCharged,
                'amount_paid' => $amountPaid,
                'total_amount' => $completedAmount,
                'status' => 'approved',
                'provider_response' => json_encode($providerResponse),
                'description' => 'Auto Transfer completed and wallet credited automatically.',
                'completed_at' => now(),
                'approved_by' => $resolvedBy ?? $lockedTransaction->approved_by,
                'balance_after' => $creditResult['credit_after'] ?? ($customer->wallet ?? 0),
            ]);

            return $lockedTransaction->fresh();
        });
    }

    public function process(Webhook $webhook, ?int $resolvedBy = null, bool $force = false): Webhook
    {
        $webhook->refresh();
        if (! $webhook->signature_valid && ! $force) {
            throw new RuntimeException('This webhook has an invalid AutoSync signature.');
        }

        if ($webhook->processing_status === 'processed') {
            return $webhook;
        }

        return DB::transaction(function () use ($webhook, $resolvedBy) {
            $webhook->update([
                'processing_status' => 'processing',
                'last_error' => null,
            ]);

            try {
                $payload = $webhook->payload;
                $providerTransaction = data_get($payload, 'transaction', []);
                $transaction = Airtime2CashTransactions::where(function ($query) use ($webhook) {
                    if ($webhook->provider_reference) {
                        $query->where('provider_reference', $webhook->provider_reference);
                    }
                    if ($webhook->request_ref) {
                        $method = $webhook->provider_reference ? 'orWhere' : 'where';
                        $query->{$method}('provider_request_ref', $webhook->request_ref)
                            ->orWhere('transaction_id', $webhook->request_ref);
                    }
                })->first();

                if (!$transaction) {
                    throw new RuntimeException('No local Airtime2Cash transaction matches this webhook.');
                }

                $webhook->update([
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                ]);

                if (! in_array(strtolower((string) $transaction->status), ['pending', 'processing', 'initiated'], true)) {
                    $webhook->update([
                        'processing_status' => 'processed',
                        'processed_at' => now(),
                        'resolved_by' => $resolvedBy,
                        'resolved_at' => $resolvedBy ? now() : null,
                    ]);

                    return $webhook->fresh();
                }

                $providerStatus = strtolower((string) ($providerTransaction['status'] ?? ''));
                if (in_array($providerStatus, ['successful', 'success', 'completed'], true)) {
                    $completedAmount = (float) ($providerTransaction['actual_amount'] ?? $providerTransaction['amount'] ?? 0);
                    $this->settlement->settle($transaction, $completedAmount, $payload, $resolvedBy);
                } elseif (in_array($providerStatus, ['failed', 'declined', 'cancelled'], true)) {
                if ($transaction->status !== 'approved') {
                    $transaction->update([
                        'status' => 'declined',
                        'provider_status' => $providerStatus,
                        'provider_response' => json_encode($payload),
                        'description' => $providerTransaction['details'] ?? 'Auto Transfer failed at AutoSync.',
                        'approved_by' => $resolvedBy ?? $transaction->approved_by,
                        'completed_at' => $transaction->completed_at ?? now(),
                    ]);
                }
                } else {
                    throw new RuntimeException('Webhook does not contain a final AutoSync transaction status.');
                }

                $webhook->update([
                    'processing_status' => 'processed',
                    'processed_at' => now(),
                    'resolved_by' => $resolvedBy,
                    'resolved_at' => $resolvedBy ? now() : null,
                ]);
            } catch (Throwable $exception) {
                $webhook->update([
                    'processing_status' => 'failed',
                    'last_error' => $exception->getMessage(),
                    'resolved_by' => $resolvedBy,
                    'resolved_at' => $resolvedBy ? now() : null,
                ]);

                throw $exception;
            }

            return $webhook->fresh();
        });
    }

    public function balance(API $provider, $no_format = null)
    {
        return [
            'status' => 'success',
            'balance' => 'N/A',
            'status_code' => 1,
        ];
        return 'N/A'; // Autosync doesnt have a balance API
        // $baseUrl = env('ENT') === 'local' ? $provider->sandbox_base_url : $provider->live_base_url;

        // if (blank($baseUrl)) {
        //     throw new RuntimeException('The AutoSync provider endpoint is not configured.');
        // }

        // $endpoint = rtrim($baseUrl, '/').'/airtime/cash/'.rawurlencode($transaction->provider_request_ref);
        // $payload = ['otp' => $otp];
        // $headers = [
        //     'Accept' => 'application/json',
        //     'Authorization' => 'Bearer '.$provider->api_key,
        // ];
        // $startedAt = microtime(true);

        // try {
        //     $response = Http::withHeaders($headers)
        //         ->asJson()
        //         ->connectTimeout(10)
        //         ->timeout(40)
        //         ->post($endpoint, $payload);

        //     $data = $response->json();

        //     if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data'])) {
        //         $result = $response;
        //         $balance = '#' . number_format($response['data']['wallet_balance'], 2);
        //         $status = 'success';
        //         $status_code = 1;

        //         $this->api->update([
        //             'balance' => $response['data']['wallet_balance'],
        //         ]);
        //     } else {
        //         $status = 'failed';
        //         $status_code = 0;
        //         $balance = null;
        //     }

        //     $format = [
        //         'status' => $status,
        //         'balance' => $balance,
        //         'status_code' => $status_code,
        //     ];
        // } catch (\Throwable $th) {
        //     $format = [
        //         'status' => 'failed',
        //         'status_code' => 0,
        //         'balance' => $th->getMessage() . '. File: ' . $th->getFile() . '. Line:' . $th->getLine(),
        //     ];
        // }

        // if (isset($no_format)) {
        //     $format = [
        //         'status' => $status,
        //         'balance' => $response['contents']['balance'] ?? null,
        //         'status_code' => $status_code,
        //     ];
        // }

        // return $format;
    }

}
