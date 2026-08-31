<?php

namespace App\Services;

use App\Http\Controllers\WalletController;
use App\Models\Customer;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BvnVerificationBillingService
{
    public const REASON = 'BVN Verification Fee';
    public const UNIQUE_ELEMENT = 'BVN_VERIFICATION_FEE';

    public function __construct(
        private readonly WalletController $walletController
    ) {
    }

    public function recordCharge(Customer $customer, float $amount, array $context = []): array
    {
        if ($amount <= 0) {
            return [
                'status' => 'skipped',
                'settled' => false,
                'transaction' => null,
                'amount' => 0,
            ];
        }

        return DB::transaction(function () use ($customer, $amount, $context) {
            $customer = Customer::query()
                ->with('user')
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) ($customer->wallet ?? 0);
            $transactionId = (string) ($context['transaction_id'] ?? $this->buildTransactionId($customer));
            $referenceId = (string) ($context['reference_id'] ?? $transactionId);

            $transaction = TransactionLog::query()
                ->where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->first();

            if ($transaction && in_array(strtolower((string) $transaction->status), $transaction->terminalStatuses(), true)) {
                return [
                    'status' => $transaction->status,
                    'settled' => true,
                    'transaction' => $transaction->fresh(),
                    'amount' => $amount,
                ];
            }

            $payload = $this->buildTransactionPayload($customer, $amount, $balanceBefore, $context, $transactionId, $referenceId);

            if ($transaction) {
                $transaction->fill($payload);
            } else {
                $transaction = TransactionLog::create($payload);
            }

            $transaction->update([
                'status' => 'pending',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore,
                'descr' => $context['pending_description'] ?? 'BVN verification fee is pending wallet funding.',
            ]);

            return [
                'status' => 'pending',
                'settled' => false,
                'transaction' => $transaction->fresh(),
                'amount' => $amount,
            ];
        });
    }

    public function applyPendingChargeOnIncomingCredit(Customer $customer, float $grossAmount, array $context = []): array
    {
        if ($grossAmount <= 0) {
            return [
                'gross_amount' => 0,
                'net_amount' => 0,
                'fee_amount' => 0,
                'applied' => false,
                'fee_transaction_id' => null,
            ];
        }

        return DB::transaction(function () use ($customer, $grossAmount, $context) {
            $customer = Customer::query()
                ->with('user')
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $creditTransactionId = (string) ($context['transaction_id'] ?? $this->buildIncomingTransactionId($customer));

            $existingCredit = Wallet::query()
                ->where('transaction_id', $creditTransactionId)
                ->where('type', 'credit')
                ->lockForUpdate()
                ->first();

            if ($existingCredit) {
                return [
                    'gross_amount' => $grossAmount,
                    'net_amount' => (float) ($customer->wallet ?? $existingCredit->balance_after ?? 0),
                    'fee_amount' => 0,
                    'applied' => false,
                    'fee_transaction_id' => null,
                    'credit_before' => (float) ($existingCredit->balance_before ?? 0),
                    'credit_after' => (float) ($customer->wallet ?? $existingCredit->balance_after ?? 0),
                ];
            }

            $pendingCharge = TransactionLog::query()
                ->where('customer_id', $customer->id)
                ->where('reason', self::REASON)
                ->where('unique_element', self::UNIQUE_ELEMENT)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            $grossAmount = (float) $grossAmount;
            $creditBefore = (float) ($customer->wallet ?? 0);
            $creditChange = $this->walletController->applyCustomerBalanceChange($customer, 'wallet', $grossAmount, 'credit', false);
            $customer->setAttribute('wallet', $creditChange['after']);

            $creditReason = (string) ($context['credit_reason'] ?? 'Wallet funding');
            $creditPaymentMethod = (string) ($context['payment_method'] ?? 'wallet');

            $this->walletController->logWallet([
                'customer_id' => $customer->id,
                'amount' => $grossAmount,
                'total_amount' => $grossAmount,
                'balance_before' => $creditChange['before'],
                'balance_after' => $creditChange['after'],
                'type' => 'credit',
                'transaction_id' => $creditTransactionId,
                'reason' => $creditReason,
                'payment_method' => $creditPaymentMethod,
            ]);

            $feeAmount = 0.0;
            $feeTransactionId = null;
            $netAmount = $grossAmount;
            $feeApplied = false;

            if ($pendingCharge) {
                $pendingChargeAmount = (float) ($pendingCharge->total_amount ?? $pendingCharge->amount ?? 0);

                if ($pendingChargeAmount > 0 && $creditChange['after'] >= $pendingChargeAmount) {
                    $feeAmount = $pendingChargeAmount;
                    $feeTransactionId = $pendingCharge->transaction_id;
                    $feeChange = $this->walletController->applyCustomerBalanceChange($customer, 'wallet', $feeAmount, 'debit', false);
                    $customer->setAttribute('wallet', $feeChange['after']);
                    $netAmount = $grossAmount - $feeAmount;
                    $feeApplied = true;

                    $this->walletController->logWallet([
                        'customer_id' => $customer->id,
                        'amount' => $feeAmount,
                        'total_amount' => $feeAmount,
                        'balance_before' => $feeChange['before'],
                        'balance_after' => $feeChange['after'],
                        'type' => 'debit',
                        'transaction_id' => $feeTransactionId,
                        'reason' => self::REASON,
                        'payment_method' => 'wallet',
                    ]);

                    $pendingCharge->update([
                        'status' => 'success',
                        'balance_before' => $feeChange['before'],
                        'balance_after' => $feeChange['after'],
                        'descr' => $context['fee_description'] ?? 'BVN verification fee was collected from wallet funding.',
                    ]);
                }
            }

            return [
                'gross_amount' => $grossAmount,
                'net_amount' => $netAmount,
                'fee_amount' => $feeAmount,
                'applied' => $feeApplied,
                'fee_transaction_id' => $feeTransactionId,
                'credit_before' => $creditChange['before'],
                'credit_after' => $feeApplied ? $customer->wallet : $creditChange['after'],
            ];
        });
    }

    private function buildTransactionPayload(Customer $customer, float $amount, float $balanceBefore, array $context, string $transactionId, string $referenceId): array
    {
        $user = $customer->user;
        $customerName = trim(implode(' ', array_filter([
            $user?->firstname,
            $user?->middlename,
            $user?->lastname,
        ])));

        $payload = [
            'status' => 'pending',
            'reference_id' => $referenceId,
            'transaction_id' => $transactionId,
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user?->email ?? '',
            'customer_phone' => $user?->phone ?? '',
            'customer_name' => $customerName !== '' ? $customerName : ($user?->firstname ?? $user?->name ?? 'Customer'),
            'discount' => 0,
            'unit_price' => $amount,
            'quantity' => 1,
            'total_amount' => $amount,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore,
            'descr' => $context['pending_description'] ?? 'BVN verification fee pending wallet settlement.',
            'product_id' => null,
            'product_name' => null,
            'variation_id' => null,
            'variation_name' => null,
            'category_id' => null,
            'unique_element' => self::UNIQUE_ELEMENT,
            'ip_address' => $context['ip_address'] ?? request()?->ip(),
            'domain_name' => $context['domain_name'] ?? request()->getHost(),
            'app_version' => $context['app_version'] ?? null,
            'api_id' => $context['api_id'] ?? null,
            'reason' => self::REASON,
            'provider_charge' => 0,
            'charge_breakdown' => null,
            'transfer_mode' => null,
            'bank_id' => null,
            'bank_code' => null,
            'account_name' => null,
            'account_number' => null,
        ];

        if (! empty($context)) {
            $payload['request_data'] = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $payload;
    }

    private function buildTransactionId(Customer $customer): string
    {
        return sprintf(
            'BVN-%s-%s-%s',
            now()->format('YmdHis'),
            $customer->id,
            Str::upper(Str::random(6))
        );
    }

    private function buildIncomingTransactionId(Customer $customer): string
    {
        return sprintf(
            'WALLET-%s-%s-%s',
            now()->format('YmdHis'),
            $customer->id,
            Str::upper(Str::random(6))
        );
    }
}
