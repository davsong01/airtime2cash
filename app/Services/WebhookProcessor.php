<?php

namespace App\Services;

use App\Models\Airtime2CashTransactions;
use App\Models\TransactionLog;
use App\Models\Webhook;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Str;
use RuntimeException;

class WebhookProcessor
{
    public function process(Webhook $webhook, ?int $resolvedBy = null, bool $force = false): Webhook
    {
        $webhook->loadMissing(['provider', 'transaction', 'transactionLog']);

        $providerSlug = Str::lower((string) $webhook->provider?->slug);

        return match ($providerSlug) {
            'autosync' => app(AutoSyncService::class)->process($webhook, $resolvedBy, $force),
            'sagecloud' => $this->processGenericWebhook($webhook, $resolvedBy, $force),
            default => $this->processGenericWebhook($webhook, $resolvedBy, $force),
        };
    }

    private function processGenericWebhook(Webhook $webhook, ?int $resolvedBy = null, bool $force = false): Webhook
    {
        if (! $webhook->signature_valid && ! $force) {
            throw new RuntimeException('This webhook has an invalid signature.');
        }

        if ($webhook->processing_status === 'processed') {
            return $webhook;
        }

        $webhook->update([
            'processing_status' => 'processing',
            'attempts' => (int) $webhook->attempts + 1,
            'last_error' => null,
        ]);

        try {
            $payload = is_array($webhook->payload) ? $webhook->payload : [];
            $transaction = $this->resolveLinkedTransaction($webhook);

            if (! $transaction) {
                throw new RuntimeException('No local transaction matches this webhook.');
            }

            $webhook->update([
                'customer_id' => $transaction->customer_id ?? $webhook->customer_id,
                'transaction_id' => $transaction->transaction_id ?? $webhook->transaction_id,
                'provider_reference' => $webhook->provider_reference ?? $this->extractReference($payload),
                'request_ref' => $webhook->request_ref ?? $this->extractRequestRef($payload),
                'provider_status' => $this->extractProviderStatus($payload, $transaction),
            ]);

            $providerStatus = strtolower((string) $webhook->provider_status);
            $isSuccessful = in_array($providerStatus, ['successful', 'success', 'completed', 'approved'], true);
            $isFailed = in_array($providerStatus, ['failed', 'declined', 'cancelled', 'canceled', 'reversed'], true);
            $isPending = in_array($providerStatus, ['pending', 'processing', 'initiated'], true);

            if (! $isSuccessful && ! $isFailed && ! $isPending) {
                throw new RuntimeException('Webhook does not contain a final transaction status.');
            }

            if ($transaction instanceof Airtime2CashTransactions) {
                $transaction->update([
                    'status' => $isSuccessful ? 'successful' : 'failed',
                    'provider_status' => $providerStatus ?: ($isSuccessful ? 'successful' : 'failed'),
                    'provider_response' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'description' => $isSuccessful
                        ? ($this->extractMessage($payload) ?? 'Transaction completed successfully.')
                        : ($this->extractMessage($payload) ?? 'Transaction failed.'),
                    'decline_reason' => $isFailed ? ($this->extractMessage($payload) ?? 'Transaction failed.') : null,
                    'completed_at' => $isSuccessful ? ($transaction->completed_at ?? now()) : $transaction->completed_at,
                ]);
            } elseif ($transaction instanceof TransactionLog) {
                $transaction->update($this->resolveTransactionLogUpdates($transaction, $payload, $providerStatus, $isSuccessful, $isFailed));
            } else {
                throw new RuntimeException('Unsupported transaction model attached to this webhook.');
            }

            $webhook->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $webhook->update([
                'processing_status' => 'failed',
                'last_error' => $exception->getMessage(),
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
            ]);

            throw $exception;
        }

        return $webhook->fresh();
    }

    private function resolveLinkedTransaction(Webhook $webhook): Airtime2CashTransactions|TransactionLog|null
    {
        if ($webhook->relationLoaded('transaction') && $webhook->transaction) {
            return $webhook->transaction;
        }

        if ($webhook->relationLoaded('transactionLog') && $webhook->transactionLog) {
            return $webhook->transactionLog;
        }

        if ($webhook->transaction) {
            return $webhook->transaction;
        }

        if ($webhook->transactionLog) {
            return $webhook->transactionLog;
        }

        if ($webhook->transaction_id) {
            $airtimeTransaction = Airtime2CashTransactions::where('transaction_id', $webhook->transaction_id)->first();

            if ($airtimeTransaction) {
                return $airtimeTransaction;
            }

            $transactionLog = TransactionLog::where('transaction_id', $webhook->transaction_id)->first();

            if ($transactionLog) {
                return $transactionLog;
            }
        }

        return null;
    }

    private function resolveTransactionLogUpdates(TransactionLog $transaction, array $payload, string $providerStatus, bool $isSuccessful, bool $isFailed): array
    {
        $message = $this->extractMessage($payload);
        $isWalletToBank = Str::of((string) ($transaction->product?->type ?? $transaction->unique_element ?? ''))
            ->lower()
            ->contains('wallet2bank');

        if ($isWalletToBank && $isFailed && ! in_array(strtolower((string) $transaction->status), ['failed', 'declined'], true)) {
            $this->refundWalletToBankTransaction($transaction);
        }

        return [
            'status' => $isWalletToBank
                ? ($isSuccessful ? 'success' : ($isFailed ? 'failed' : 'pending'))
                : ($isSuccessful ? 'approved' : ($isFailed ? 'declined' : 'pending')),
            'user_status' => $isWalletToBank
                ? ($isSuccessful ? 'success' : ($isFailed ? 'failed' : 'pending'))
                : ($isSuccessful ? 'success' : ($isFailed ? 'failed' : 'pending')),
            'provider_status' => $providerStatus ?: ($isSuccessful ? 'successful' : 'failed'),
            'api_response' => json_encode($payload, JSON_THROW_ON_ERROR),
            'failure_reason' => $isFailed ? ($message ?? 'Transaction failed.') : null,
            'descr' => $message ?? ($isSuccessful
                ? 'Transaction completed successfully.'
                : ($isPending ? 'Transaction is pending provider confirmation.' : 'Transaction failed.')),
            'completed_at' => $isSuccessful ? ($transaction->completed_at ?? now()) : $transaction->completed_at,
            'admin_id' => $isWalletToBank ? auth()->user()?->admin?->id ?? ($transaction->admin_id ?? null) : ($transaction->admin_id ?? null),
        ];
    }

    private function refundWalletToBankTransaction(TransactionLog $transaction): void
    {
        $transaction->loadMissing('customer.user');

        if (! $transaction->customer?->user) {
            throw new RuntimeException('Unable to refund wallet because the customer record is missing.');
        }

        $wallet = app(WalletController::class);
        $amount = (float) ($transaction->total_amount ?? $transaction->amount ?? 0);

        if ($amount <= 0) {
            throw new RuntimeException('Unable to refund an invalid wallet amount.');
        }

        $wallet->logWallet([
            'customer_id' => $transaction->customer_id,
            'type' => 'credit',
            'total_amount' => $amount,
            'transaction_id' => $transaction->transaction_id,
            'reason' => 'Wallet to Bank Transfer refund',
            'payment_method' => 'wallet',
        ]);

        $wallet->updateCustomerWallet($transaction->customer->user, $amount, 'credit');

        $transaction->update([
            'balance_after' => (float) ($transaction->balance_before ?? 0) + $amount,
        ]);
    }

    private function extractReference(array $payload): ?string
    {
        return $this->firstString([
            data_get($payload, 'transaction.reference'),
            data_get($payload, 'data.transaction.reference'),
            data_get($payload, 'reference'),
            data_get($payload, 'provider_reference'),
            data_get($payload, 'transaction_id'),
        ]);
    }

    private function extractRequestRef(array $payload): ?string
    {
        return $this->firstString([
            data_get($payload, 'transaction.request_ref'),
            data_get($payload, 'data.transaction.request_ref'),
            data_get($payload, 'request_ref'),
        ]);
    }

    private function extractProviderStatus(array $payload, mixed $transaction): string
    {
        $transactionStatus = data_get($payload, 'transaction.status')
            ?? data_get($payload, 'data.transaction.status')
            ?? data_get($payload, 'status')
            ?? data_get($payload, 'provider_status')
            ?? data_get($transaction, 'provider_status')
            ?? 'pending';

        return strtolower((string) $transactionStatus);
    }

    private function extractMessage(array $payload): ?string
    {
        return $this->firstString([
            data_get($payload, 'message'),
            data_get($payload, 'data.transaction.details'),
            data_get($payload, 'response_description'),
            data_get($payload, 'description'),
            data_get($payload, 'error'),
        ]);
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
