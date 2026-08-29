<?php

namespace Tests\Feature;

use App\Models\Airtime2CashTransactions;
use App\Models\Category;
use App\Models\API;
use App\Models\Product;
use App\Models\Bank;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIpMiddleware;
use App\Http\Middleware\RouteProtectionMiddleware;
use App\Models\Customer;
use App\Models\Admin;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Services\AutoSyncService;
use App\Services\WalletSnapshotBackfillService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function test_wallet_to_bank_rejects_duplicate_submissions_within_five_minutes(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Duplicate',
            'lastname' => 'Guard',
            'email' => 'duplicate.guard@example.com',
            'phone' => '08030000001',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 50000,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);

        $provider = API::create([
            'name' => 'Duplicate Transfer Provider',
            'slug' => 'duplicate-transfer-provider',
            'status' => 'active',
            'pricing_data_status' => true,
            'pricing_data' => json_encode([
                [
                    'min_amount' => 1000,
                    'max_amount' => 10000,
                    'provider_fee' => 50,
                    'extra_charge' => 0,
                ],
            ]),
            'extra_charges' => json_encode([]),
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'bank_transfer_provider_id' => $provider->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bank = Bank::create([
            'bank_name' => 'Guard Bank',
            'cbn_code' => '999',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Wallet Transfer',
            'slug' => 'wallet-transfer-guard-category',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Wallet to Bank',
            'slug' => 'wallet-to-bank-guard-product',
            'type' => 'wallet2bank',
            'category_id' => $category->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'W2B-DUP-REF-001',
            'transaction_id' => 'W2B-DUP-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_name' => $user->firstname,
            'discount' => 0,
            'unit_price' => 8000,
            'quantity' => 1,
            'total_amount' => 8050,
            'amount' => 8000,
            'balance_before' => 50000,
            'balance_after' => 41950,
            'descr' => 'Wallet to Bank Transfer initiated.',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_id' => null,
            'unique_element' => 'Wallet2Bank',
            'reason' => 'Wallet to Bank Transfer',
            'api_id' => $provider->id,
            'bank_id' => $bank->id,
            'bank_code' => $bank->cbn_code,
            'account_name' => 'Guard Receiver',
            'account_number' => '1234567890',
            'transfer_mode' => 'auto_share',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('initialize.wallet2banktransaction', $product->id), [
                'amount' => '₦8,000.00',
                'bank' => $bank->cbn_code,
                'account_name' => 'Guard Receiver',
                'account_number' => '1234567890',
                'transfer_mode' => 'auto_share',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This looks like a duplicate wallet to bank transaction. Please wait 5 minutes and try again with the same parameters.');

        $this->assertSame(50000.0, (float) $user->fresh()->customer->wallet);
        $this->assertDatabaseCount('transaction_logs', 1);
    }

    public function test_manual_wallet_to_bank_requests_do_not_store_provider_linkage(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Manual',
            'lastname' => 'Request',
            'email' => 'manual.request@example.com',
            'phone' => '08040000002',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        Customer::create([
            'user_id' => $user->id,
            'wallet' => 50000,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 0,
            'kyc_status' => 'verified',
        ]);

        $provider = API::create([
            'name' => 'Manual Transfer Provider',
            'slug' => 'manual-transfer-provider',
            'status' => 'active',
            'pricing_data_status' => true,
            'pricing_data' => json_encode([
                [
                    'min_amount' => 1000,
                    'max_amount' => 10000,
                    'provider_fee' => 50,
                    'extra_charge' => 0,
                ],
            ]),
            'extra_charges' => json_encode([]),
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'bank_transfer_provider_id' => $provider->id,
            'wallet_to_bank_transfer_manual_status' => 'enabled',
            'wallet_to_bank_transfer_auto_status' => 'enabled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bank = Bank::create([
            'bank_name' => 'Manual Bank',
            'cbn_code' => '999',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Wallet Transfer Manual',
            'slug' => 'wallet-transfer-manual-category',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Wallet to Bank Manual',
            'slug' => 'wallet-to-bank-manual-product',
            'type' => 'wallet2bank',
            'category_id' => $category->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('initialize.wallet2banktransaction', $product->id), [
                'amount' => '₦8,000.00',
                'bank' => $bank->cbn_code,
                'account_name' => 'Manual Receiver',
                'account_number' => '1234567890',
                'transfer_mode' => 'manual',
            ]);

        $response->assertRedirect();

        $transaction = TransactionLog::query()
            ->where('customer_id', $user->customer->id)
            ->where('reason', 'Wallet to Bank Transfer')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('manual', $transaction->transfer_mode);
        $this->assertNull($transaction->api_id);
        $this->assertDatabaseHas('transaction_logs', [
            'id' => $transaction->id,
            'transfer_mode' => 'manual',
        ]);
    }

    public function test_wallet_to_bank_resolution_modal_hides_credit_and_process_actions(): void
    {
        $admin = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'Viewer',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        \App\Models\Admin::create([
            'user_id' => $admin->id,
            'permissions' => '',
        ]);

        $user = User::factory()->create([
            'firstname' => 'Wallet',
            'lastname' => 'Viewer',
            'email' => 'wallet.viewer@example.com',
            'phone' => '08040000003',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 41950,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        $provider = API::create([
            'name' => 'Wallet View Provider',
            'slug' => 'wallet-view-provider',
            'status' => 'active',
            'pricing_data_status' => true,
            'pricing_data' => json_encode([
                [
                    'min_amount' => 1000,
                    'max_amount' => 10000,
                    'provider_fee' => 50,
                    'extra_charge' => 0,
                ],
            ]),
            'extra_charges' => json_encode([]),
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'bank_transfer_provider_id' => $provider->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bank = Bank::create([
            'bank_name' => 'Viewer Bank',
            'cbn_code' => '999',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Wallet Transfer View',
            'slug' => 'wallet-transfer-view-category',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Wallet to Bank View',
            'slug' => 'wallet-to-bank-view-product',
            'type' => 'wallet2bank',
            'category_id' => $category->id,
            'status' => 'active',
            'image' => 'site/upgrade.jpg',
            'api_id' => $provider->id,
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'W2B-VIEW-001',
            'transaction_id' => 'W2B-VIEW-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->firstname,
            'customer_phone' => $user->phone,
            'discount' => 0,
            'unit_price' => 8000,
            'amount' => 8000,
            'total_amount' => 8050,
            'balance_before' => 50000,
            'balance_after' => 41950,
            'quantity' => 1,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_id' => $category->id,
            'unique_element' => 'wallet2bank',
            'reason' => 'Wallet to Bank Transfer',
            'descr' => 'Wallet to Bank Transfer initiated for manual processing.',
            'api_id' => $provider->id,
            'bank_id' => $bank->id,
            'bank_code' => $bank->cbn_code,
            'account_name' => 'Viewer Receiver',
            'account_number' => '1234567890',
            'transfer_mode' => 'manual',
        ]);

        $response = $this->withoutMiddleware()
            ->withViewErrors([])
            ->actingAs($admin)
            ->get(route('admin.single.transaction.view', $transaction->id));

        $response->assertOk();
        $response->assertSee('Wallet to Bank');
        $response->assertDontSee('value="credit_customer"');
        $response->assertDontSee('value="process"');
    }

    public function test_wallet_to_bank_failed_resolution_refunds_the_customer_wallet(): void
    {
        $admin = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'Resolver',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        \App\Models\Admin::create([
            'user_id' => $admin->id,
            'permissions' => '',
        ]);

        $user = User::factory()->create([
            'firstname' => 'Wallet',
            'lastname' => 'Refund',
            'email' => 'wallet.refund@example.com',
            'phone' => '08040000001',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 41950,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'W2B-REFUND-001',
            'transaction_id' => 'W2B-REFUND-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 8000,
            'amount' => 8000,
            'total_amount' => 8050,
            'balance_before' => 50000,
            'balance_after' => 41950,
            'quantity' => 1,
            'reason' => 'Wallet to Bank Transfer',
            'descr' => 'Wallet to Bank Transfer initiated for manual processing.',
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
        ]);

        Wallet::create([
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => 8050,
            'balance_before' => 50000,
            'balance_after' => 41950,
            'transaction_id' => 'W2B-REFUND-001',
            'reason' => 'Wallet to Bank Transfer',
            'payment_method' => 'wallet',
        ]);

        $this->actingAs($admin);

        $request = Request::create('/', 'POST', [
            'action' => 'failed',
            'reason' => 'Manual decline by admin',
        ]);

        $response = app(\App\Http\Controllers\TransactionController::class)->resolvePendingTransactionAction($request, $transaction);

        $this->assertTrue(method_exists($response, 'getSession'));
        $this->assertSame('Customer has been credited and the transaction was closed.', $response->getSession()->get('message'));
        $this->assertSame(50000.0, (float) $customer->fresh()->wallet);

        $this->assertDatabaseHas('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'failed',
            'balance_after' => 41950,
        ]);

        $this->assertDatabaseHas('wallets', [
            'transaction_id' => 'W2B-REFUND-001',
            'type' => 'credit',
            'amount' => 8050,
            'payment_method' => 'ADMIN-REFUND',
        ]);
    }

    public function test_airtime_to_cash_detail_view_shows_wallet_trail(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Trail',
            'lastname' => 'Viewer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 250,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash-trail-test',
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $api = API::create([
            'name' => 'Trail Provider',
            'slug' => 'trail-provider',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash-trail-product',
            'category_id' => $category->id,
            'type' => 'airtime2cash',
            'image' => 'site/upgrade.jpg',
            'status' => 'active',
            'api_id' => $api->id,
        ]);

        $transaction = Airtime2CashTransactions::create([
            'transaction_id' => 'A2C-TEST-TRAIL-001',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'provider_id' => $api->id,
            'type' => 'credit',
            'amount_paid' => 120,
            'amount_charged' => 30,
            'charge_rate' => 25,
            'total_amount' => 150,
            'payment_method' => 'Transfer to Wallet',
            'status' => 'successful',
        ]);

        DB::table('wallets')->insert([
            'customer_id' => $customer->id,
            'amount' => 120,
            'balance_before' => 250,
            'balance_after' => 370,
            'type' => 'credit',
            'transaction_id' => $transaction->transaction_id,
            'reason' => 'Airtime to Cash',
            'payment_method' => 'wallet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()
            ->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->withViewErrors([])
            ->actingAs($user)
            ->get(route('admin.single.airtime2cash.transaction.view', $transaction));

        $response->assertOk();
        $response->assertSee('Wallet Trail');
        $response->assertSee('Initial Balance:');
        $response->assertSee('Final Balance:');
        $response->assertSee('A2C-TEST-TRAIL-001');
        $response->assertSee('120.00');
        $response->assertSee('250.00');
        $response->assertSee('370.00');
        $response->assertSee('Query Provider Status');
        $response->assertDontSee('n/a → n/a');

        $customerResponse = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($user)
            ->get(url('/admin/customer/edit/' . $user->id . '?tab=airtime2cash-transactions'));

        $customerResponse->assertOk();
        $customerResponse->assertSee('Wallet');
        $customerResponse->assertSee('₦250.00 → ₦370.00');
        $customerResponse->assertSee('badge-light-success');

        $adminLogResponse = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($user)
            ->get(url('/admin/admin-airtime-2-cash-log?transaction_id=A2C-TEST-TRAIL-001'));

        $adminLogResponse->assertOk();
        $adminLogResponse->assertSee('₦250.00');
        $adminLogResponse->assertSee('₦370.00');
        $adminLogResponse->assertSee('Initial / final wallet balance');
        $adminLogResponse->assertSee('badge-light-success');

        $statusResponse = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($user)
            ->get(url('/admin/single-airtime2cash-transaction-view/' . $transaction->id . '/requery'));

        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'status' => true,
            'transaction_status' => 'successful',
            'provider_status' => 'successful',
            'provider' => 'Trail Provider',
        ]);
    }

    public function test_airtime_to_cash_detail_view_falls_back_to_transaction_log_snapshot_when_wallet_trail_is_missing(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Fallback',
            'lastname' => 'Viewer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 697450,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash-fallback-test',
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $api = API::create([
            'name' => 'Fallback Provider',
            'slug' => 'fallback-provider',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash-fallback-product',
            'category_id' => $category->id,
            'type' => 'airtime2cash',
            'image' => 'site/upgrade.jpg',
            'status' => 'active',
            'api_id' => $api->id,
        ]);

        $transaction = Airtime2CashTransactions::create([
            'transaction_id' => 'A2C-TEST-FALLBACK-001',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'provider_id' => $api->id,
            'type' => 'credit',
            'amount_paid' => 600,
            'amount_charged' => 400,
            'charge_rate' => 40,
            'total_amount' => 1000,
            'payment_method' => 'Transfer to Wallet',
            'status' => 'pending',
        ]);

        TransactionLog::create([
            'status' => 'pending',
            'reference_id' => $transaction->transaction_id,
            'transaction_id' => $transaction->transaction_id,
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_name' => $user->firstname,
            'discount' => 0,
            'unit_price' => 600,
            'quantity' => 1,
            'total_amount' => 1000,
            'amount' => 600,
            'balance_before' => 697450,
            'balance_after' => 697450,
            'descr' => 'Airtime2Cash request initiated.',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_id' => $category->id,
            'unique_element' => 'Airtime2Cash Payment',
            'reason' => 'Airtime2Cash Payment',
            'api_id' => $api->id,
        ]);

        $response = $this->withoutExceptionHandling()
            ->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($user)
            ->get(route('admin.single.airtime2cash.transaction.view', $transaction));

        $response->assertOk();
        $response->assertSee('Wallet Trail');
        $response->assertSee('Showing transaction log snapshot while wallet rows are unavailable.');
        $response->assertSee('Initial Balance:');
        $response->assertSee('Final Balance:');
        $response->assertSee('₦697,450.00');
        $response->assertSee('A2C-TEST-FALLBACK-001');
        $response->assertSee('Status: Pending');
        $response->assertDontSee('No wallet trail recorded for this airtime-to-cash transaction.');
    }

    public function test_wallet_snapshot_backfill_reconstructs_missing_balances_from_the_current_customer_balance(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Backfill',
            'lastname' => 'Tester',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 130,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        Wallet::create([
            'customer_id' => $customer->id,
            'amount' => 100,
            'type' => 'credit',
            'transaction_id' => 'A2C-BF-001',
            'reason' => 'Airtime-to-cash conversion',
            'payment_method' => 'wallet',
        ]);

        Wallet::create([
            'customer_id' => $customer->id,
            'amount' => 30,
            'type' => 'debit',
            'transaction_id' => 'A2C-BF-002',
            'reason' => 'Wallet to Bank Transfer',
            'payment_method' => 'wallet',
        ]);

        Wallet::create([
            'customer_id' => $customer->id,
            'amount' => 60,
            'type' => 'credit',
            'transaction_id' => 'A2C-BF-003',
            'reason' => 'Airtime-to-cash conversion',
            'payment_method' => 'wallet',
        ]);

        TransactionLog::create([
            'status' => 'successful',
            'reference_id' => 'A2C-BF-002',
            'transaction_id' => 'A2C-BF-002',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_name' => $user->firstname,
            'discount' => 0,
            'unit_price' => 30,
            'quantity' => 1,
            'total_amount' => 30,
            'amount' => 30,
            'balance_before' => null,
            'balance_after' => null,
            'descr' => 'Backfill test snapshot',
            'product_id' => null,
            'product_name' => null,
            'category_id' => null,
            'unique_element' => 'Airtime2Cash Payment',
            'reason' => 'Airtime2Cash Payment',
        ]);

        $stats = app(WalletSnapshotBackfillService::class)->backfill();

        $this->assertGreaterThan(0, $stats['wallet_rows_updated']);

        $first = DB::table('wallets')->where('transaction_id', 'A2C-BF-001')->first();
        $second = DB::table('wallets')->where('transaction_id', 'A2C-BF-002')->first();
        $third = DB::table('wallets')->where('transaction_id', 'A2C-BF-003')->first();

        $this->assertSame(0.0, (float) $first->balance_before);
        $this->assertSame(100.0, (float) $first->balance_after);
        $this->assertSame(100.0, (float) $second->balance_before);
        $this->assertSame(70.0, (float) $second->balance_after);
        $this->assertSame(70.0, (float) $third->balance_before);
        $this->assertSame(130.0, (float) $third->balance_after);

        $txLog = DB::table('transaction_logs')->where('transaction_id', 'A2C-BF-002')->first();

        $this->assertSame(100.0, (float) $txLog->balance_before);
        $this->assertSame(70.0, (float) $txLog->balance_after);
    }

    public function test_airtime_to_cash_requery_hits_autosync_transaction_status_endpoint(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Auto',
            'lastname' => 'Query',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 1000,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash-autosync-test',
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $provider = API::create([
            'name' => 'Autosync',
            'slug' => 'autosync',
            'status' => 'active',
            'sandbox_base_url' => 'https://autosync.test/api',
            'api_key' => 'test-key',
        ]);

        $product = Product::create([
            'name' => 'MTN Airtime to Cash',
            'slug' => 'mtn-airtime-to-cash-autosync',
            'category_id' => $category->id,
            'type' => 'airtime2cash',
            'image' => 'site/upgrade.jpg',
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        $transaction = Airtime2CashTransactions::create([
            'transaction_id' => 'A2C-AUTOSYNC-REQUERY-001',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'provider_request_ref' => 'ASNA2C20260828082539QHJE6M',
            'type' => 'credit',
            'amount_paid' => 1200,
            'amount_charged' => 100,
            'charge_rate' => 8.33,
            'total_amount' => 1300,
            'payment_method' => 'Transfer to Wallet',
            'status' => 'pending',
            'provider_status' => 'pending',
        ]);

        Http::fake([
            'https://autosync.test/api/*' => Http::response([
                'status' => 'ok',
                'message' => 'Transaction retrieved successfully.',
                'data' => [
                    'transaction' => [
                        'reference' => 'ASNA2C20260828082539QHJE6M',
                        'request_ref' => 'A2C-AUTOSYNC-REQUERY-001',
                        'status' => 'successful',
                        'amount' => 1300,
                    ],
                ],
            ], 200),
        ]);

        $response = app(AutoSyncService::class)->queryTransaction($transaction, $provider);

        $this->assertSame('successful', data_get($response, 'data.transaction.status'));
        $this->assertSame('Autosync', $provider->name);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://autosync.test/api/transaction/ASNA2C20260828082539QHJE6M';
        });
    }

    public function test_admin_transactions_index_shows_wallet_to_bank_and_airtime_to_cash_modes(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create([
            'firstname' => 'Mode',
            'lastname' => 'Tester',
            'email' => 'mode.tester@example.com',
            'phone' => '08030000099',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'wallet' => 50000,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $walletCategory = Category::create([
            'name' => 'Mode Tests W2B',
            'slug' => 'mode-tests-w2b',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        $airtimeCategory = Category::create([
            'name' => 'Mode Tests A2C',
            'slug' => 'mode-tests-a2c',
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $provider = API::create([
            'name' => 'Mode Provider',
            'slug' => 'mode-provider',
            'status' => 'active',
        ]);

        $walletProduct = Product::create([
            'name' => 'Mode Wallet Product',
            'slug' => 'mode-wallet-product',
            'display_name' => 'Mode Wallet Product',
            'type' => 'wallet2bank',
            'category_id' => $walletCategory->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        $airtimeProduct = Product::create([
            'name' => 'Mode Airtime Product',
            'slug' => 'mode-airtime-product',
            'display_name' => 'Mode Airtime Product',
            'type' => 'airtime2cash',
            'category_id' => $airtimeCategory->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        TransactionLog::create([
            'status' => 'success',
            'reference_id' => 'W2B-MODE-001',
            'transaction_id' => 'W2B-MODE-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->firstname,
            'customer_phone' => $user->phone,
            'discount' => 0,
            'unit_price' => 5000,
            'amount' => 5000,
            'total_amount' => 5050,
            'balance_before' => 20000,
            'balance_after' => 14950,
            'quantity' => 1,
            'product_id' => $walletProduct->id,
            'product_name' => $walletProduct->name,
            'category_id' => $walletCategory->id,
            'api_id' => $provider->id,
            'unique_element' => 'Wallet2Bank',
            'transfer_mode' => 'manual',
        ]);

        TransactionLog::create([
            'status' => 'success',
            'reference_id' => 'A2C-MODE-001',
            'transaction_id' => 'A2C-MODE-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->firstname,
            'customer_phone' => $user->phone,
            'discount' => 0,
            'unit_price' => 5000,
            'amount' => 5000,
            'total_amount' => 5050,
            'balance_before' => 20000,
            'balance_after' => 20000,
            'quantity' => 1,
            'product_id' => $airtimeProduct->id,
            'product_name' => $airtimeProduct->name,
            'category_id' => $airtimeCategory->id,
            'api_id' => $provider->id,
            'unique_element' => 'Airtime2Cash',
            'transfer_mode' => 'auto_share',
        ]);

        $response = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->get(route('admin.trans'));

        $response->assertOk();
        $response->assertSee('Mode:');
        $response->assertSee('Manual');
        $response->assertSee('Auto');
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        Admin::create([
            'user_id' => $user->id,
            'permissions' => '',
        ]);

        return $user;
    }
}
