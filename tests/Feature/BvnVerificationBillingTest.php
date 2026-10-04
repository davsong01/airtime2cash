<?php

namespace Tests\Feature;

use App\Http\Controllers\WalletController;
use App\Models\Customer;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BvnVerificationBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BvnVerificationBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bvn_fee_is_debited_immediately_from_an_existing_primary_wallet(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Funded',
            'lastname' => 'Customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 100,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        $charge = app(BvnVerificationBillingService::class)->recordCharge(
            $customer,
            20,
            ['bvn' => '12345678901'],
        );

        $this->assertTrue($charge['settled']);
        $this->assertSame('success', $charge['status']);
        $this->assertSame(80.0, (float) $customer->fresh()->wallet);
        $this->assertDatabaseHas('wallets', [
            'customer_id' => $customer->id,
            'transaction_id' => $charge['transaction']->transaction_id,
            'type' => 'debit',
            'reason' => BvnVerificationBillingService::REASON,
        ]);
    }

    public function test_pending_bvn_fee_is_debited_from_the_first_primary_wallet_credit(): void
    {
        $user = User::factory()->create([
            'firstname' => 'BVN',
            'lastname' => 'Customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 0,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        $billing = app(BvnVerificationBillingService::class);
        $firstCharge = $billing->recordCharge($customer, 20, ['bvn' => '12345678901']);
        $secondCharge = $billing->recordCharge($customer, 20, ['bvn' => '12345678901']);

        $this->assertSame('pending', $firstCharge['status']);
        $this->assertSame(
            $firstCharge['transaction']->transaction_id,
            $secondCharge['transaction']->transaction_id,
        );
        $this->assertSame(1, TransactionLog::where('reason', BvnVerificationBillingService::REASON)->count());

        app(WalletController::class)->updateCustomerWallet($user->fresh(), 100, 'credit');

        $fee = TransactionLog::where('reason', BvnVerificationBillingService::REASON)->firstOrFail();
        $this->assertSame('success', $fee->status);
        $this->assertSame(80.0, (float) $customer->fresh()->wallet);
        $this->assertDatabaseHas('wallets', [
            'customer_id' => $customer->id,
            'transaction_id' => $fee->transaction_id,
            'type' => 'debit',
            'reason' => BvnVerificationBillingService::REASON,
        ]);

        app(WalletController::class)->updateCustomerWallet($user->fresh(), 50, 'credit');

        $this->assertSame(130.0, (float) $customer->fresh()->wallet);
        $this->assertSame(
            1,
            Wallet::where('customer_id', $customer->id)
                ->where('reason', BvnVerificationBillingService::REASON)
                ->where('type', 'debit')
                ->count(),
        );
    }
}
