<?php

namespace App\Services;

use App\Models\API;
use App\Models\AutoSyncApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AutoSyncService
{
    private string $baseUrl;
    private string $token;
    private bool $fake;
    private bool $providerEnabled;

    public function __construct()
    {
        $providerId = getSettings()?->auto_share_provider_id;
        $provider = API::find($providerId);
        $this->providerEnabled = $provider !== null && $provider->status === 'active';
        $this->baseUrl = env('ENT' == 'local') ? rtrim($provider?->sandbox_base_url) : rtrim($provider?->live_base_url);
    }

    public function initiate(array $payload, array $context = []): array
    {
        return $this->post('initiate', '/airtime/cash', $payload, $context);
    }

    public function complete(string $reference, string $otp, array $context = []): array
    {
        return $this->post('complete', '/airtime/cash/' . rawurlencode($reference), ['otp' => $otp], $context);
    }

    public function resendOtp(string $reference, array $context = []): array
    {
        return $this->post('resend_otp', '/airtime/cash/' . rawurlencode($reference) . '/resend-otp', [], $context);
    }

    private function post(string $operation, string $path, array $payload, array $context): array
    {
        $endpoint = $this->baseUrl . $path;
        dd($endpoint);
        $startedAt = microtime(true);
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer [REDACTED]',
        ];

        if (!$this->providerEnabled) {
            $message = 'Auto Transfer is disabled. Please contact support.';
            $this->writeLog($operation, $endpoint, $headers, $payload, null, null, $context, $startedAt, $message);
            throw new RuntimeException($message);
        }

        if ($this->fake) {
            $data = $this->fakeResponse($operation, $path, $payload, $context);
            $this->writeLog($operation, $endpoint, $headers, $payload, null, $data, $context, $startedAt, null, 200);

            return $data;
        }

        if ($this->token === '') {
            $message = 'Auto Transfer is not configured. Please contact support.';
            $this->writeLog($operation, $endpoint, $headers, $payload, null, null, $context, $startedAt, $message);
            throw new RuntimeException($message);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($this->token)
                ->connectTimeout(10)
                ->timeout(40)
                ->post($endpoint, $payload);

            $data = $response->json();
            $this->writeLog($operation, $endpoint, $headers, $payload, $response, is_array($data) ? $data : ['raw' => $response->body()], $context, $startedAt);
        } catch (ConnectionException $exception) {
            $this->writeLog($operation, $endpoint, $headers, $payload, null, null, $context, $startedAt, $exception->getMessage());
            throw new RuntimeException('AutoSync could not be reached. Please try again.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('AutoSync returned an invalid response. Please try again.');
        }

        if (!$response->successful() || ($data['status'] ?? null) !== 'ok') {
            throw new RuntimeException($data['message'] ?? 'Auto Transfer could not be completed.');
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

    private function fakeResponse(string $operation, string $path, array $payload, array $context): array
    {
        $reference = $context['provider_reference'] ?? trim(str_replace(['/airtime/cash/', '/resend-otp'], '', $path), '/');
        if ($operation === 'initiate') {
            $reference = (string) Str::uuid();
        }

        $status = $operation === 'complete' ? 'successful' : 'pending';
        $message = $operation === 'complete' ? 'Transaction successful' : 'OTP sent successfully.';

        return [
            'status' => 'ok',
            'message' => $message,
            'data' => [
                'transaction' => [
                    'reference' => $reference,
                    'request_ref' => $payload['request_ref'] ?? $context['transaction_id'] ?? null,
                    'type' => 'MTN Airtime to Cash',
                    'details' => $operation === 'complete' ? 'Transaction successful' : '-',
                    'amount' => (string) ($payload['amount'] ?? $context['amount'] ?? 0),
                    'status' => $status,
                    'request_data' => $operation === 'initiate' ? $payload : ['request_ref' => $context['transaction_id'] ?? null],
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
