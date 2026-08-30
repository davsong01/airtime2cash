<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Wallet;
use App\Services\BvnVerificationBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletController extends Controller
{
    public function getWalletBalance($user)
    {
        return $user->customer->wallet ?? 0.00;
    }

    public function getReferralBalance($user)
    {
        return $user->customer->referal_wallet ?? 0;
    }

    public function airtime2cashBalance($user){
        return $user->customer->a2cashwallet ?? 0;
    }

    public function logWallet($data)
    {
        $wallet = Wallet::create([
            'customer_id' => $data['customer_id'] ?? auth()->user()->customer->id,
            'amount' => $data['total_amount'],
            'balance_before' => $data['balance_before'] ?? null,
            'balance_after' => $data['balance_after'] ?? null,
            'type' => $data['type'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
        ]);

        return $wallet;
    }

    public function updateCustomerWallet($user, $amount, $type)
    {
        DB::transaction(function () use ($user, $amount, $type) {
            $customer = Customer::query()
                ->whereKey($user->customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->applyCustomerBalanceChange($customer, 'wallet', $amount, $type);
        });
    }

    public function updateReferralWallet($user, $amount, $type)
    {
        DB::transaction(function () use ($user, $amount, $type) {
            $customer = Customer::query()
                ->whereKey($user->customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->applyCustomerBalanceChange($customer, 'referal_wallet', $amount, $type);
        });
    }

    public function updatea2CashWallet($user, $amount, $type)
    {
        DB::transaction(function () use ($user, $amount, $type) {
            $customer = Customer::query()
                ->whereKey($user->customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->applyCustomerBalanceChange($customer, 'a2cashwallet', $amount, $type);
        });
    }

    public function applyCustomerBalanceChange(Customer $customer, string $column, $amount, string $type, bool $settlePendingBvnCharges = true): array
    {
        $currentBalance = (float) ($customer->{$column} ?? 0);
        $amount = (float) $amount;

        if ($type === 'credit') {
            $nextBalance = $currentBalance + $amount;
        } else {
            if ($currentBalance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $nextBalance = $currentBalance - $amount;
        }

        $customer->update([
            $column => $nextBalance,
        ]);
        $customer->setAttribute($column, $nextBalance);

        if ($column === 'wallet' && $type === 'credit' && $settlePendingBvnCharges) {
            try {
                app(BvnVerificationBillingService::class)->settlePendingCharges($customer);
            } catch (Throwable $throwable) {
                Log::error('Unable to settle pending BVN verification charges after wallet credit.', [
                    'customer_id' => $customer->id ?? null,
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                ]);
            }
        }

        return [
            'before' => $currentBalance,
            'after' => $nextBalance,
        ];
    }
}
