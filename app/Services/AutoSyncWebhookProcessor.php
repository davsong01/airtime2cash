<?php

namespace App\Services;

use App\Models\Airtime2CashTransactions;
use App\Models\AutoSyncWebhook;
use RuntimeException;
use Throwable;

class AutoSyncWebhookProcessor
{
    public function __construct(private AutoSyncSettlementService $settlement)
    {
    }

    public function process(AutoSyncWebhook $webhook, ?int $resolvedBy = null): AutoSyncWebhook
    {
        $webhook->refresh();
        if (!$webhook->signature_valid) {
            throw new RuntimeException('This webhook has an invalid AutoSync signature.');
        }

        if ($webhook->processing_status === 'processed') {
            return $webhook;
        }

        $webhook->update([
            'processing_status' => 'processing',
            'attempts' => $webhook->attempts + 1,
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

            $providerStatus = strtolower((string) ($providerTransaction['status'] ?? ''));
            if (in_array($providerStatus, ['successful', 'success', 'completed'], true)) {
                $completedAmount = (float) ($providerTransaction['actual_amount'] ?? $providerTransaction['amount'] ?? 0);
                $this->settlement->settle($transaction, $completedAmount, $payload);
            } elseif (in_array($providerStatus, ['failed', 'declined', 'cancelled'], true)) {
                if ($transaction->status !== 'approved') {
                    $transaction->update([
                        'status' => 'declined',
                        'provider_status' => $providerStatus,
                        'provider_response' => json_encode($payload),
                        'description' => $providerTransaction['details'] ?? 'Auto Transfer failed at AutoSync.',
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
    }
}
