<?php

namespace App\Services;

use App\Models\Airtime2CashTransactions;
use App\Models\TransactionLog;
use App\Models\Webhook;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WebhookProcessor
{
    public function process(Webhook $webhook, ?int $resolvedBy = null, bool $force = false): Webhook
    {
        $webhook->loadMissing(['provider', 'transaction', 'transactionLog']);

        $providerSlug = Str::lower((string) $webhook->provider?->slug);

        return match ($providerSlug) {
            'autosync' => app(AutoSyncService::class)->process($webhook, $resolvedBy, $force),
            'sagecloud', 'paystack', 'monnify', 'kora' => $this->processGenericWebhook($webhook, $resolvedBy, $force),
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

        return DB::transaction(function () use ($webhook, $resolvedBy) {
            $webhook->update([
                'processing_status' => 'processing',
                'last_error' => null,
            ]);

            try {
                $payload = is_array($webhook->payload) ? $webhook->payload : [];
                $transaction = $this->resolveLinkedTransaction($webhook);

                if (! $transaction) {
                    throw new RuntimeException('No local transaction matches this webhook.');
                }

                $verification = $this->verifyTransactionWithProvider($webhook, $transaction);

                if (! is_array($verification)) {
                    $webhook->update([
                        'processing_status' => 'pending',
                        'processed_at' => null,
                        'resolved_by' => $resolvedBy,
                        'resolved_at' => now(),
                        'last_error' => 'Provider verification could not be performed.',
                    ]);

                    return $webhook->fresh();
                }

                $verifiedResponse = $verification['api_response'] ?? $verification;
                $verifiedStatus = strtolower((string) ($verification['provider_status'] ?? $verification['status'] ?? 'pending'));
                $verifiedMessage = $verification['message'] ?? null;
                $isSuccessful = ($verification['status'] ?? null) === 'success';
                $isFailed = ($verification['status'] ?? null) === 'failed';
                $isPending = ($verification['status'] ?? null) === 'pending';

                $webhook->update([
                    'customer_id' => $transaction->customer_id ?? $webhook->customer_id,
                    'transaction_id' => $transaction->transaction_id ?? $webhook->transaction_id,
                    'provider_reference' => $webhook->provider_reference ?? $this->extractReference($payload),
                    'request_ref' => $webhook->request_ref ?? $this->extractRequestRef($payload),
                    'provider_status' => $verifiedStatus,
                ]);

                if (! $this->transactionIsPending($transaction)) {
                    $webhook->update([
                        'processing_status' => 'processed',
                        'processed_at' => now(),
                        'resolved_by' => $resolvedBy,
                        'resolved_at' => now(),
                        'last_error' => null,
                    ]);

                    return $webhook->fresh();
                }

                if (! $isSuccessful && ! $isFailed && ! $isPending) {
                    $webhook->update([
                        'processing_status' => 'pending',
                        'processed_at' => null,
                        'resolved_by' => $resolvedBy,
                        'resolved_at' => now(),
                        'last_error' => null,
                    ]);

                    return $webhook->fresh();
                }

                if ($transaction instanceof Airtime2CashTransactions) {
                    $transaction->update([
                        'status' => $isSuccessful ? 'successful' : 'failed',
                        'provider_status' => $verifiedStatus ?: ($isSuccessful ? 'successful' : 'failed'),
                        'provider_response' => json_encode($verifiedResponse, JSON_THROW_ON_ERROR),
                        'description' => $isSuccessful
                            ? ($verifiedMessage ?? $this->extractMessage($verifiedResponse) ?? 'Transaction completed successfully.')
                            : ($verifiedMessage ?? $this->extractMessage($verifiedResponse) ?? 'Transaction failed.'),
                        'decline_reason' => $isFailed ? ($verifiedMessage ?? $this->extractMessage($verifiedResponse) ?? 'Transaction failed.') : null,
                        'completed_at' => ($isSuccessful || $isFailed)
                            ? ($transaction->completed_at ?? now())
                            : $transaction->completed_at,
                    ]);
                } elseif ($transaction instanceof TransactionLog) {
                    $transaction->update($this->resolveTransactionLogUpdates($transaction, $verification ?? $payload, $verifiedStatus, $isSuccessful, $isFailed));
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
        });
    }

    private function verifyTransactionWithProvider(Webhook $webhook, Airtime2CashTransactions|TransactionLog $transaction): ?array
    {
        $provider = $webhook->provider
            ?? ($transaction instanceof TransactionLog ? $transaction->api : null);

        $controller = resolveProviderController($provider);

        if (! $controller) {
            return null;
        }

        $reference = $this->resolveVerificationReference($webhook, $transaction);

        try {
            if (method_exists($controller, 'verifyTransaction') && filled($reference)) {
                return $this->normalizeVerificationResult((array) $controller->verifyTransaction((string) $reference));
            }

            if (method_exists($controller, 'requery')) {
                return $this->normalizeVerificationResult((array) $controller->requery($transaction));
            }
        } catch (\Throwable $exception) {
            return [
                'status' => 'pending',
                'provider_status' => 'pending',
                'message' => $exception->getMessage(),
                'api_response' => [
                    'error' => $exception->getMessage(),
                ],
            ];
        }

        return null;
    }

    private function resolveVerificationReference(Webhook $webhook, Airtime2CashTransactions|TransactionLog $transaction): ?string
    {
        return $this->firstString([
            $webhook->provider_reference,
            $webhook->request_ref,
            data_get($transaction, 'reference_id'),
            data_get($transaction, 'transaction_reference'),
            data_get($transaction, 'request_id'),
            data_get($transaction, 'transaction_id'),
        ]);
    }

    private function normalizeVerificationResult(array $verification): array
    {
        $rawStatus = data_get($verification, 'status');
        $apiResponse = data_get($verification, 'api_response', $verification);
        $providerStatus = strtolower((string) (
            data_get($verification, 'provider_status')
            ?? data_get($apiResponse, 'responseBody.paymentStatus')
            ?? data_get($apiResponse, 'responseBody.transaction.status')
            ?? data_get($apiResponse, 'data.transaction.status')
            ?? data_get($apiResponse, 'data.status')
            ?? data_get($apiResponse, 'status')
            ?? (is_string($rawStatus) ? $rawStatus : '')
        ));
        $message = $this->firstString([
            data_get($verification, 'message'),
            data_get($verification, 'error'),
            data_get($apiResponse, 'responseMessage'),
            data_get($apiResponse, 'response_description'),
            data_get($apiResponse, 'message'),
        ]);

        $isSuccess = in_array($providerStatus, ['successful', 'success', 'completed', 'paid', 'overpaid', 'approved', 'authorized'], true)
            || $rawStatus === true
            || (is_string($rawStatus) && in_array(strtolower($rawStatus), ['successful', 'success', 'completed', 'paid', 'overpaid', 'approved', 'authorized'], true));

        $isPending = in_array($providerStatus, ['pending', 'processing', 'initiated', 'awaiting_processing', 'in_progress', 'pending_authorization'], true)
            || (is_string($message) && Str::contains(Str::lower($message), ['pending', 'processing', 'initiated', 'awaiting']));

        $isFailed = in_array($providerStatus, ['failed', 'declined', 'cancelled', 'canceled', 'reversed', 'rejected', 'error', 'expired'], true)
            || ($rawStatus === false && ! $isPending);

        if (! $isSuccess && ! $isPending && ! $isFailed) {
            $isPending = true;
        }

        return [
            'status' => $isSuccess ? 'success' : ($isPending ? 'pending' : 'failed'),
            'provider_status' => $providerStatus ?: ($isSuccess ? 'successful' : ($isPending ? 'pending' : 'failed')),
            'message' => $message,
            'api_response' => $apiResponse,
        ];
    }

    private function transactionIsPending(Airtime2CashTransactions|TransactionLog|null $transaction): bool
    {
        if (! $transaction) {
            return false;
        }

        $status = strtolower((string) ($transaction->status ?? 'pending'));

        return in_array($status, ['pending', 'processing', 'initiated'], true);
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

    private function resolveTransactionLogUpdates(TransactionLog $transaction, array $verification, string $providerStatus, bool $isSuccessful, bool $isFailed): array
    {
        $payload = data_get($verification, 'api_response', $verification);
        $message = $this->firstString([
            data_get($verification, 'message'),
            $this->extractMessage(is_array($payload) ? $payload : []),
        ]);
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
            'api_response' => json_encode($payload, JSON_THROW_ON_ERROR),
            'failure_reason' => $isFailed ? ($message ?? 'Transaction failed.') : null,
            'descr' => $message ?? ($isSuccessful
                ? 'Transaction completed successfully.'
                : ($providerStatus === 'pending' ? 'Transaction is pending provider confirmation.' : 'Transaction failed.')),
            'admin_id' => $isWalletToBank ? auth()->user()?->admin?->id ?? ($transaction->admin_id ?? null) : ($transaction->admin_id ?? null),
        ] + (Schema::hasColumn('transaction_logs', 'completed_at')
            ? ['completed_at' => ($isSuccessful || $isFailed) ? ($transaction->completed_at ?? now()) : $transaction->completed_at]
            : []);
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
