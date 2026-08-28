<?php

namespace Tests\Feature;

use App\Http\Controllers\WalletController;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WalletConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_wallet_instances_do_not_overwrite_each_other(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Race',
            'lastname' => 'Condition',
            'status' => 'active',
        ]);

        Customer::create([
            'user_id' => $user->id,
            'wallet' => 100,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        $firstRequest = User::with('customer')->findOrFail($user->id);
        $secondRequest = User::with('customer')->findOrFail($user->id);

        $wallet = new WalletController();
        $wallet->updateCustomerWallet($firstRequest, 60, 'debit');
        $wallet->updateCustomerWallet($secondRequest, 30, 'debit');

        $this->assertSame(10.0, (float) number_format((float) $user->fresh()->customer->wallet, 2, '.', ''));
    }

    public function test_atomic_wallet_updates_persist_a_balance_snapshot(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Atomic',
            'lastname' => 'Wallet',
            'status' => 'active',
        ]);

        Customer::create([
            'user_id' => $user->id,
            'wallet' => 100,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        $wallet = new WalletController();

        DB::transaction(function () use ($user, $wallet): void {
            $customer = Customer::query()
                ->whereKey($user->customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $snapshot = $wallet->applyCustomerBalanceChange($customer, 'wallet', 30, 'debit');

            $wallet->logWallet([
                'customer_id' => $customer->id,
                'type' => 'debit',
                'total_amount' => 30,
                'transaction_id' => 'W2B-DEMO-001',
                'reason' => 'Wallet to Bank Transfer',
                'payment_method' => 'wallet',
                'balance_before' => $snapshot['before'],
                'balance_after' => $snapshot['after'],
            ]);
        });

        $walletLog = DB::table('wallets')->where('transaction_id', 'W2B-DEMO-001')->first();

        $this->assertNotNull($walletLog);
        $this->assertSame(100.0, (float) $walletLog->balance_before);
        $this->assertSame(70.0, (float) $walletLog->balance_after);
        $this->assertSame(70.0, (float) $user->fresh()->customer->wallet);
    }
}
