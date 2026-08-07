<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\AutoSyncApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;


class AutoSyncService
{
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

        $baseUrl = app()->environment('local')
            ? $provider->sandbox_base_url
            : $provider->live_base_url;

        if (blank($baseUrl)) {
            throw new RuntimeException(
                'The AutoShare provider endpoint is not configured.'
            );
        }

        $endpoint = rtrim($baseUrl, '/').'/airtime/cash';
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

        $baseUrl = env('ENT') === 'local' ? $provider->sandbox_base_url : $provider->live_base_url;

        if (blank($baseUrl)) {
            throw new RuntimeException('The AutoSync provider endpoint is not configured.');
        }

        $endpoint = rtrim($baseUrl, '/').'/airtime/cash/'.rawurlencode($transaction->provider_request_ref);
        $payload = ['otp' => $otp];
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$provider->api_key,
        ];
        $startedAt = microtime(true);

        try {
            // if (env('ENT') === 'local') {
            //     $dummyStatus = strtolower((string) env('AUTOSYNC_DUMMY_STATUS', 'successful'));
            //     $dummyHttpStatus = (int) env('AUTOSYNC_DUMMY_HTTP_STATUS', 200);

            //     $message = match ($dummyStatus) {
            //         'successful' => 'Transaction successful',
            //         'pending', 'processing', 'initiated' => 'Transaction is pending. Please do not retry while it is being processed.',
            //         default => 'Transaction failed',
            //     };

            //     $data = [
            //         'status' => $dummyHttpStatus >= 200 && $dummyHttpStatus < 300 ? 'ok' : 'error',
            //         'message' => $message,
            //         'data' => [
            //             'transaction' => [
            //                 'reference' => $transaction->provider_request_ref,
            //                 'request_ref' => $transaction->transaction_id,
            //                 'type' => ($transaction->product?->name ?? 'Airtime').' Airtime to Cash',
            //                 'details' => $message,
            //                 'amount' => number_format((float) $transaction->total_amount, 2, '.', ''),
            //                 'status' => $dummyStatus,
            //                 'request_data' => [
            //                     'phone' => $transaction->phone_numbers,
            //                     'amount' => (float) $transaction->total_amount,
            //                     'request_ref' => $transaction->transaction_id,
            //                 ],
            //                 'created_at' => now()->toISOString(),
            //                 'updated_at' => now()->toISOString(),
            //             ],
            //         ],
            //     ];

            //     $response = new Response(
            //         new GuzzleResponse(
            //             $dummyHttpStatus,
            //             ['Content-Type' => 'application/json'],
            //             json_encode($data, JSON_THROW_ON_ERROR)
            //         )
            //     );
            // } else {
            //     $response = Http::withHeaders($headers)
            //         ->asJson()
            //         ->connectTimeout(10)
            //         ->timeout(40)
            //         ->post($endpoint, $payload);

            //     $data = $response->json();
            // }

            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->post($endpoint, $payload);

            $data = $response->json();

            $this->writeLog(
                'complete',
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

        if (! $response->successful() || ($data['status'] ?? null) !== 'ok') {
            throw new RuntimeException(
                $data['message']
                    ?? $data['error']
                    ?? 'Auto Transfer could not be completed.'
            );
        }

        return $data;
    }

    public function resendOtp(Airtime2CashTransactions $transaction, API $provider, array $context = []): array
    {
        if (blank($transaction->provider_request_ref)) {
            throw new RuntimeException('The provider transaction reference is missing.');
        }

        $baseUrl = env('ENT') == 'local' ? $provider->sandbox_base_url : $provider->live_base_url;

        if (blank($baseUrl)) {
            throw new RuntimeException('The AutoSync provider endpoint is not configured.');
        }

        $endpoint = rtrim($baseUrl, '/').'/airtime/cash/'.rawurlencode($transaction->provider_request_ref).'/resend-otp';
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
        string $endpoint,
        array $headers,
        array $payload,
        ?Response $response,
        ?array $responseBody,
        array $context,
        float $startedAt,
        ?string $error = null,
        ?int $fallbackStatus = null
    ): void {
        AutoSyncApiLog::create([
            'customer_id' => $context['customer_id'] ?? null,
            'transaction_id' => $context['transaction_id'] ?? null,
            'operation' => $operation,
            'method' => 'POST',
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

}
