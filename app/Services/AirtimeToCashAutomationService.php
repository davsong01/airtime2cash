<?php

namespace App\Services;

use App\Models\API;
use App\Models\ApiRequestLog;
use App\Models\Airtime2CashTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AirtimeToCashAutomationService
{
    private const PROVIDER_SLUG = 'airtimetocash';

    public function networkNameForProduct(string $productName): string
    {
        $productName = Str::lower($productName);

        return match (true) {
            Str::contains($productName, 'mtn') => 'MTN',
            Str::contains($productName, 'glo') => 'GLO',
            Str::contains($productName, 'airtel') => 'AIRTEL',
            Str::contains($productName, '9mobile'),
            Str::contains($productName, 'etisalat') => '9MOBILE',
            default => throw new RuntimeException(
                'Unsupported network: '.$productName
            ),
        };
    }

    public function networkNameForTransaction(Airtime2CashTransactions $transaction): string
    {
        return $this->networkNameForProduct((string) $transaction->product?->name);
    }

    public function generateOtp(
        string $networkName,
        string $sender,
        API $provider,
        array $context = []
    ): array {
        return $this->request(
            operation: 'generate_otp',
            method: 'POST',
            path: '/api/v1/generate/otp',
            provider: $provider,
            payload: [
                'networkName' => $networkName,
                'sender' => $sender,
            ],
            authenticated: false,
            context: $context,
        );
    }

    public function verifyOtp(
        string $networkName,
        string $sender,
        string $otp,
        API $provider,
        array $context = []
    ): array {
        return $this->request(
            operation: 'verify_otp',
            method: 'POST',
            path: '/api/v1/verify/otp',
            provider: $provider,
            payload: [
                'networkName' => $networkName,
                'sender' => $sender,
                'otp' => $otp,
            ],
            authenticated: false,
            context: $context,
        );
    }

    public function loginWithSessionId(
        string $networkName,
        string $sender,
        string $sessionId,
        API $provider,
        array $context = []
    ): array {
        return $this->request(
            operation: 'login_with_session_id',
            method: 'POST',
            path: '/api/v1/login/with/session/id',
            provider: $provider,
            payload: [
                'networkName' => $networkName,
                'sender' => $sender,
                'sessionId' => $sessionId,
            ],
            context: $context,
        );
    }

    public function checkQuota(
        string $networkName,
        int|float $amount,
        API $provider,
        array $context = []
    ): array {
        return $this->request(
            operation: 'check_quota',
            method: 'POST',
            path: '/api/v1/check/quota/availability',
            provider: $provider,
            payload: [
                'networkName' => $networkName,
                'amount' => (int) $amount,
            ],
            context: $context,
        );
    }

    public function transferAirtime(
        string $networkName,
        string $sender,
        int|float $amount,
        string $reference,
        string $pin,
        string $sessionId,
        API $provider,
        array $context = []
    ): array {
        if (mb_strlen($reference) < 10 || mb_strlen($reference) > 40) {
            throw new RuntimeException('The provider reference must be between 10 and 40 characters.');
        }

        return $this->request(
            operation: 'transfer_airtime',
            method: 'POST',
            path: '/api/v1/transfer/airtime',
            provider: $provider,
            payload: [
                'networkName' => $networkName,
                'sender' => $sender,
                'amount' => (int) $amount,
                'reference' => $reference,
                'pin' => $pin,
                'sessionId' => $sessionId,
            ],
            context: $context,
        );
    }

    public function transferTransaction(
        Airtime2CashTransactions $transaction,
        string $pin,
        string $sessionId,
        API $provider
    ): array {
        $sender = trim((string) $transaction->phone_numbers);
        if ($sender === '' || Str::contains($sender, ',')) {
            throw new RuntimeException('This provider supports one sender phone number per transaction.');
        }

        return $this->transferAirtime(
            networkName: $this->networkNameForTransaction($transaction),
            sender: $sender,
            amount: (float) $transaction->total_amount,
            reference: (string) $transaction->transaction_id,
            pin: $pin,
            sessionId: $sessionId,
            provider: $provider,
            context: [
                'customer_id' => $transaction->customer_id,
                'transaction_id' => $transaction->transaction_id,
            ],
        );
    }

    private function request(
        string $operation,
        string $method,
        string $path,
        API $provider,
        array $payload,
        bool $authenticated = true,
        array $context = []
    ): array {
        if ($provider->slug !== self::PROVIDER_SLUG) {
            throw new RuntimeException('The selected provider is not Airtime to Cash Automation.');
        }

        $baseUrl = app()->environment(['local', 'testing'])
            ? $provider->sandbox_base_url
            : $provider->live_base_url;

        if (blank($baseUrl)) {
            throw new RuntimeException('The Airtime to Cash Automation endpoint is not configured.');
        }

        $endpoint = rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($authenticated) {
            if (blank($provider->api_key)) {
                throw new RuntimeException('The Airtime to Cash Automation API key is not configured.');
            }

            $headers['Authorization'] = 'Bearer '.$provider->api_key;
        }

        $startedAt = microtime(true);
        $response = null;
        $data = null;

        try {
            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(10)
                ->timeout(40)
                ->send($method, $endpoint, ['json' => $payload]);

            $data = $response->json();
        } catch (ConnectionException $exception) {
            $this->writeLog(
                operation: $operation,
                provider: $provider,
                endpoint: $endpoint,
                method: $method,
                headers: $headers,
                payload: $payload,
                response: null,
                responseBody: null,
                context: $context,
                startedAt: $startedAt,
                error: $exception->getMessage(),
            );

            throw new RuntimeException(
                'Airtime to Cash Automation could not be reached. Please try again.',
                0,
                $exception
            );
        }

        $this->writeLog(
            operation: $operation,
            provider: $provider,
            endpoint: $endpoint,
            method: $method,
            headers: $headers,
            payload: $payload,
            response: $response,
            responseBody: is_array($data) ? $data : ['raw' => $response->body()],
            context: $context,
            startedAt: $startedAt,
        );

        if (! is_array($data)) {
            throw new RuntimeException('Airtime to Cash Automation returned an invalid response.');
        }

        return $data;
    }

    private function writeLog(
        string $operation,
        API $provider,
        string $endpoint,
        string $method,
        array $headers,
        array $payload,
        ?Response $response,
        ?array $responseBody,
        array $context,
        float $startedAt,
        ?string $error = null
    ): void {
        if (! Schema::hasTable('api_request_logs')) {
            return;
        }

        ApiRequestLog::create([
            'api_id' => $provider->id,
            'customer_id' => $context['customer_id'] ?? null,
            'transaction_id' => $context['transaction_id'] ?? null,
            'operation' => $operation,
            'method' => $method,
            'endpoint' => $endpoint,
            'request_headers' => $this->redact($headers),
            'request_payload' => $this->redact($payload),
            'response_status' => $response?->status(),
            'response_headers' => $response?->headers(),
            'response_body' => $responseBody ? $this->redact($responseBody) : null,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(Str::lower((string) $key), [
                'api_key',
                'authorization',
                'otp',
                'pin',
                'sessionid',
                'session_id',
                'sharepin',
                'share_pin',
                'token',
                'access_token',
            ], true)) {
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
