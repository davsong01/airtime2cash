<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\API;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BankTransferProviderController extends Controller
{
    protected ?API $api = null;

    abstract protected function providerSlug(): string;

    protected function api(): ?API
    {
        if ($this->api instanceof API) {
            return $this->api;
        }

        $this->api = API::query()
            ->where('slug', $this->providerSlug())
            ->where('status', 'active')
            ->first();

        return $this->api;
    }

    protected function baseUrl(): ?string
    {
        return providerBaseUrl($this->api());
    }

    protected function providerStatus(array $response): string
    {
        return strtolower((string) data_get($response, 'data.transaction.status', data_get($response, 'data.status', data_get($response, 'status', 'pending'))));
    }

    protected function successStatuses(): array
    {
        return ['successful', 'success', 'completed'];
    }

    protected function pendingStatuses(): array
    {
        return ['pending', 'processing', 'initiated', 'awaiting_processing', 'in_progress', 'pending_authorization'];
    }

    protected function makeLocalResponse(string $status = 'success', string $message = 'Request processed successfully.', array $extra = []): array
    {
        return array_merge([
            'success' => $status === 'success',
            'status' => $status,
            'message' => $message,
            'data' => [
                'transaction' => [
                    'status' => $status === 'success' ? 'successful' : $status,
                ],
            ],
        ], $extra);
    }

    public function verifyWebhookSignature(Request $request): array
    {
        $payload = normalizeWebhookPayload($request);
        $reference = data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'data.transaction.reference')
            ?? data_get($payload, 'reference')
            ?? data_get($payload, 'transaction_id');

        return [
            'status' => filled($reference),
            'reference' => $reference,
            'message' => filled($reference)
                ? 'Webhook signature verified.'
                : 'Webhook reference could not be resolved.',
        ];
    }

    public function analyzeWebhookResponse($webhook): array
    {
        $data = is_array($webhook->payload ?? null)
            ? $webhook->payload
            : json_decode((string) ($webhook->request_payload ?? '{}'), true);

        $status = strtolower((string) data_get($data, 'transaction.status', data_get($data, 'data.transaction.status', data_get($data, 'status', 'pending'))));

        return [
            'status' => true,
            'status_code' => in_array($status, ['successful', 'success', 'completed'], true) ? 1 : (in_array($status, ['pending', 'processing', 'initiated'], true) ? 2 : 0),
            'provider_status' => $status,
            'api_response' => $data,
            'payload' => data_get($data, 'transaction', data_get($data, 'data.transaction', $data)),
        ];
    }

    public function verifyBankDetails(array $data)
    {
        $provider = $this->api();

        if (! $provider) {
            return [
                'status' => 'failed',
                'message' => 'No active provider configured.',
            ];
        }

        $payload = [
            'bank_code' => $data['bank_code'] ?? null,
            'account_number' => $data['account_number'] ?? null,
        ];

        $url = rtrim((string) $this->baseUrl(), '/') . '/' . ltrim($this->bankVerificationPath(), '/');

        return $this->basicApiCall($url, json_encode($payload), $this->headers(), 'POST') ?? [
            'status' => 'failed',
            'message' => 'Unable to verify bank details.',
        ];
    }

    public function balance(): array
    {
        return [
            'status' => 'failed',
            'message' => 'Balance lookup not supported for this provider.',
        ];
    }

    public function transfer(array $data): array
    {
        $provider = $this->api();

        if (! $provider) {
            return [
                'status' => 'failed',
                'error' => 'No active bank transfer provider configured.',
                'request_data' => $data,
            ];
        }

        $payload = $this->transferPayload($data);

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/' . ltrim($this->transferPath(), '/'),
            json_encode($payload),
            $this->headers(),
            'POST'
        );

        $status = $this->providerStatus($response ?: []);
        $success = in_array($status, ['successful', 'success', 'completed'], true)
            || (($response['success'] ?? false) === true && in_array(($response['status'] ?? null), ['success', 'successful'], true));
        $pending = in_array($status, ['pending', 'processing', 'initiated'], true);

        return [
            'status' => $success ? 'success' : ($pending ? 'pending' : 'failed'),
            'provider_status' => $status ?: ($success ? 'successful' : 'failed'),
            'error' => $success ? null : ($pending ? null : ($response['message'] ?? 'Bank transfer failed.')),
            'request_data' => $payload,
            'api_response' => $response,
        ];
    }

    public function requery($transaction)
    {
        $provider = $this->api();

        if (! $provider) {
            return null;
        }

        $payload = [
            'reference' => $transaction->transaction_id,
        ];

        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/' . ltrim($this->requeryPath(), '/'),
            json_encode($payload),
            $this->headers(),
            'POST'
        );

        return [
            'status' => (bool) data_get($response, 'success', false),
            'api_status' => (bool) data_get($response, 'success', false),
            'provider_status' => $this->providerStatus($response ?: []),
            'api_response' => $response,
            'payload' => $payload,
            'message' => data_get($response, 'message'),
        ];
    }

    public function pullBanks(): array
    {
        $response = $this->basicApiCall(
            rtrim((string) $this->baseUrl(), '/') . '/' . ltrim($this->banksPath(), '/'),
            [],
            $this->headers(),
            'GET'
        );

        return [
            'status' => is_array($response) ? 'success' : 'failed',
            'data' => $response,
        ];
    }

    protected function bankVerificationPath(): string
    {
        return 'bank/resolve';
    }

    protected function transferPath(): string
    {
        return 'transfer';
    }

    protected function requeryPath(): string
    {
        return 'transfer/verify';
    }

    protected function banksPath(): string
    {
        return 'banks';
    }

    protected function headers(): array
    {
        return [
            'Content-Type: application/json',
        ];
    }

    protected function transferPayload(array $data): array
    {
        return [
            'bank_code' => $data['bank_code'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'amount' => (float) ($data['amount'] ?? 0),
            'reference' => $data['transaction_id'] ?? (string) Str::uuid(),
            'narration' => $data['narration'] ?? ('Transfer from ' . config('app.name')),
        ];
    }
}
