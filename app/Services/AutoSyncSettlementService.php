<?php

namespace App\Services;

use App\Models\Airtime2CashTransactions;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AutoSyncSettlementService
{
    public function settle(Airtime2CashTransactions $transaction, float $completedAmount, array $providerResponse): Airtime2CashTransactions
    {
        if ($completedAmount <= 0 || $completedAmount > (float) $transaction->total_amount) {
            throw new RuntimeException('AutoSync returned an invalid completed amount.');
        }

        return DB::transaction(function () use ($transaction, $completedAmount, $providerResponse) {
            $lockedTransaction = Airtime2CashTransactions::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($lockedTransaction->status === 'approved') {
                return $lockedTransaction;
            }

            if ($lockedTransaction->transfer_mode !== 'auto_share' || $lockedTransaction->payment_method !== 'Transfer to Wallet') {
                throw new RuntimeException('This transaction cannot be settled automatically.');
            }

            $customer = $lockedTransaction->customer()->lockForUpdate()->firstOrFail();
            $amountCharged = ((float) $lockedTransaction->charge_rate / 100) * $completedAmount;
            $amountPaid = $completedAmount - $amountCharged;

            Wallet::create([
                'customer_id' => $customer->id,
                'amount' => $amountPaid,
                'type' => 'credit',
                'transaction_id' => $lockedTransaction->transaction_id,
                'reason' => 'Auto Airtime2Cash Payment',
                'payment_method' => 'wallet',
            ]);

            $customer->increment('wallet', $amountPaid);
            $lockedTransaction->update([
                'amount_charged' => $amountCharged,
                'amount_paid' => $amountPaid,
                'total_amount' => $completedAmount,
                'status' => 'approved',
                'provider_status' => 'successful',
                'provider_response' => json_encode($providerResponse),
                'description' => 'Auto Transfer completed and wallet credited automatically.',
                'completed_at' => now(),
            ]);

            return $lockedTransaction->fresh();
        });
    }
}
