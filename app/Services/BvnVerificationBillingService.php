<?php

namespace App\Services;

use App\Http\Controllers\WalletController;
use App\Models\Customer;
use App\Models\TransactionLog;
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
                'settled' => true,
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

            $transaction = TransactionLog::updateOrCreate(
                ['transaction_id' => $transactionId],
                $this->buildTransactionPayload($customer, $amount, $balanceBefore, $context, $transactionId, $referenceId)
            );

            if ($balanceBefore >= $amount) {
                $change = $this->walletController->applyCustomerBalanceChange($customer, 'wallet', $amount, 'debit', false);
                $customer->setAttribute('wallet', $change['after']);

                $this->walletController->logWallet([
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'total_amount' => $amount,
                    'balance_before' => $change['before'],
                    'balance_after' => $change['after'],
                    'type' => 'debit',
                    'transaction_id' => $transactionId,
                    'reason' => self::REASON,
                    'payment_method' => 'wallet',
                ]);

                $transaction->update([
                    'status' => 'success',
                    'balance_before' => $change['before'],
                    'balance_after' => $change['after'],
                    'descr' => $context['settled_description'] ?? 'BVN verification fee was charged successfully.',
                ]);

                return [
                    'status' => 'success',
                    'settled' => true,
                    'transaction' => $transaction->fresh(),
                    'amount' => $amount,
                ];
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

    public function settlePendingCharges(Customer $customer): int
    {
        return DB::transaction(function () use ($customer) {
            $customer = Customer::query()
                ->with('user')
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingCharges = TransactionLog::query()
                ->where('customer_id', $customer->id)
                ->where('reason', self::REASON)
                ->where('unique_element', self::UNIQUE_ELEMENT)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $settled = 0;
            $currentBalance = (float) ($customer->wallet ?? 0);

            foreach ($pendingCharges as $charge) {
                $amount = (float) ($charge->total_amount ?? $charge->amount ?? 0);

                if ($amount <= 0 || $currentBalance < $amount) {
                    break;
                }

                $change = $this->walletController->applyCustomerBalanceChange($customer, 'wallet', $amount, 'debit', false);
                $customer->setAttribute('wallet', $change['after']);
                $currentBalance = (float) $change['after'];

                $this->walletController->logWallet([
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'total_amount' => $amount,
                    'balance_before' => $change['before'],
                    'balance_after' => $change['after'],
                    'type' => 'debit',
                    'transaction_id' => $charge->transaction_id,
                    'reason' => self::REASON,
                    'payment_method' => 'wallet',
                ]);

                $charge->update([
                    'status' => 'success',
                    'balance_before' => $change['before'],
                    'balance_after' => $change['after'],
                    'descr' => 'BVN verification fee was collected after wallet funding.',
                ]);

                $settled++;
            }

            return $settled;
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
}
