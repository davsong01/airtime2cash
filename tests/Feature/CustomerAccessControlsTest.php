<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIpMiddleware;
use App\Http\Middleware\ReservedAccountCreationMiddleware;
use App\Http\Middleware\TransactionPinMiddleware;
use App\Http\Middleware\RouteProtectionMiddleware;
use App\Models\API;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Bank;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use App\Http\Controllers\Providers\MonnifyController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CustomerAccessControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_customers_page_shows_service_access_badges(): void
    {
        $admin = $this->createAdminUser();

        $enabledUser = $this->createCustomerUser('Enabled', 'Customer', [
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);

        $disabledUser = $this->createCustomerUser('Disabled', 'Customer', [
            'can_access_w2bank' => 0,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 1,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->get(route('customers'));

        $response->assertOk();
        $response->assertSee('Manual Wallet 2 Bank Enabled');
        $response->assertSee('Auto Wallet 2 Bank Enabled');
        $response->assertSee('Airtime 2 Cash Disabled');
        $response->assertSee('Manual Wallet 2 Bank Disabled');
        $response->assertSee('Auto Wallet 2 Bank Disabled');
        $response->assertSee('Airtime 2 Cash Enabled');

        $this->assertDatabaseHas('customers', [
            'id' => $enabledUser->customer->id,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $disabledUser->customer->id,
            'can_access_w2bank' => 0,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 1,
        ]);
    }

    public function test_admin_can_bulk_toggle_wallet_to_bank_and_airtime_to_cash_access(): void
    {
        $admin = $this->createAdminUser();
        $firstUser = $this->createCustomerUser('Bulk', 'One');
        $secondUser = $this->createCustomerUser('Bulk', 'Two');

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.bulk-actions'), [
                'action' => 'enable_w2bank_manual_access',
                'customer_ids' => $firstUser->id . ',' . $secondUser->id,
            ])
            ->assertRedirect();

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.bulk-actions'), [
                'action' => 'enable_w2bank_auto_access',
                'customer_ids' => $firstUser->id . ',' . $secondUser->id,
            ])
            ->assertRedirect();

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.bulk-actions'), [
                'action' => 'disable_a2c_access',
                'customer_ids' => $firstUser->id . ',' . $secondUser->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $firstUser->customer->id,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $secondUser->customer->id,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 0,
        ]);
    }

    public function test_blocked_wallet_and_airtime_pages_show_access_message_and_whatsapp_contact(): void
    {
        $user = $this->createCustomerUser('Blocked', 'Customer', [
            'can_access_w2bank' => 0,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'whatsapp_number' => '2348012345678',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $walletResponse = $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('wallet-to-bank', 'wallet-to-bank-slug'));

        $walletResponse->assertStatus(403);
        $walletResponse->assertSee('This service (Wallet 2 Bank) is not available for you at the moment');
        $walletResponse->assertSee('Click to contact admin on WhatsApp');
        $walletResponse->assertSee('2348012345678');

        $a2cResponse = $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('airtime-to-cash'));

        $a2cResponse->assertStatus(403);
        $a2cResponse->assertSee('This service (Airtime 2 Cash) is not available for you at the moment');
        $a2cResponse->assertSee('Click to contact admin on WhatsApp');
        $a2cResponse->assertSee('2348012345678');
    }

    public function test_wallet_to_bank_page_shows_blocked_modes_with_contact_admin_cta(): void
    {
        $user = $this->createCustomerUser('Partial', 'Access', [
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 0,
            'kyc_status' => 'verified',
        ]);

        $provider = API::create([
            'name' => 'Wallet Transfer Provider',
            'slug' => 'wallet-transfer-provider',
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

        $category = Category::create([
            'name' => 'Wallet Transfer',
            'slug' => 'wallet-transfer-access-category',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Wallet to Bank',
            'slug' => 'wallet-to-bank-access-product',
            'type' => 'wallet2bank',
            'category_id' => $category->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'whatsapp_number' => '2348012345678',
            'bank_transfer_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        view()->share('errors', new ViewErrorBag());
        $initialBufferLevel = ob_get_level();

        try {
            $response = $this->withoutMiddleware()
                ->actingAs($user)
                ->get(route('wallet-to-bank', $product->slug));

            $response->assertOk();
            $response->assertSee('Auto Transfer');
            $response->assertSee('Manual Transfer');
            $response->assertSee('Click here to contact admin on WhatsApp');
            $response->assertSee('This transfer mode is currently unavailable for your account.');
        } finally {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }
        }
    }

    public function test_account_tab_exposes_and_saves_service_access_controls(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Account', 'Tester', [
            'can_access_w2bank' => 0,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 0,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->withoutExceptionHandling()
            ->actingAs($admin)
            ->get(route('customers.edit', ['id' => $user->id, 'tab' => 'account']));

        $response->assertOk();
        $response->assertSee('Manual Wallet 2 Bank access');
        $response->assertSee('Auto Wallet 2 Bank access');
        $response->assertSee('Airtime 2 Cash access');
        $response->assertSee('Verify account only');

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.update', $user->id), [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => 'account-updated@example.com',
                'status' => $user->status,
                'phone' => $user->phone,
                'customerlevel' => $user->customer->customer_level,
                'can_access_w2bank' => 1,
                'can_access_w2bank_auto' => 1,
                'can_access_a2c' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $user->customer->id,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 1,
            'can_access_a2c' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'account-updated@example.com',
        ]);
    }

    public function test_wallet_bank_picklists_only_show_banks_supported_by_the_active_verification_provider(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Filter', 'Tester', [
            'kyc_status' => 'verified',
        ]);

        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        $supportedBank = Bank::create([
            'bank_name' => 'Supported Bank',
            'cbn_code' => '111',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '111',
            ],
        ]);

        $unsupportedBank = Bank::create([
            'bank_name' => 'Unsupported Bank',
            'cbn_code' => '222',
            'status' => 'active',
            'provider_codes' => [
                'paystack' => '222',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'bank_transfer_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminResponse = $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->get(route('customers.edit', ['id' => $user->id, 'tab' => 'account']));

        $adminResponse->assertOk();
        $adminResponse->assertSee($supportedBank->bank_name, false);
        $adminResponse->assertDontSee($unsupportedBank->bank_name, false);

        $profileResponse = $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('profile.edit'));

        $profileResponse->assertOk();
        $profileResponse->assertSee($supportedBank->bank_name, false);
        $profileResponse->assertDontSee($unsupportedBank->bank_name, false);
    }

    public function test_customer_can_save_a_verified_locked_wallet_bank_account(): void
    {
        $user = $this->createCustomerUser('John', 'Doe');
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        Bank::create([
            'bank_name' => 'Access Bank',
            'cbn_code' => '044',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '044',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance(MonnifyController::class, new class extends MonnifyController {
            public function verifyBankDetails(array $data)
            {
                return response()->json([
                    'status' => true,
                    'message' => 'Bank details verified successfully.',
                    'data' => [
                        'account_name' => 'John Doe',
                        'account_number' => $data['account_number'] ?? null,
                        'bank_name' => 'Access Bank',
                    ],
                ]);
            }
        });

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('profile.wallet-bank-account.store'), [
                'bank' => '044',
                'account_number' => '1234567890',
            ])
            ->assertRedirect();

        $customer = $user->fresh('customer')->customer;

        $this->assertNotNull($customer?->wallet_bank_account);
        $this->assertSame('Access Bank', data_get($customer->wallet_bank_account, 'bank_name'));
        $this->assertSame('John Doe', data_get($customer->wallet_bank_account, 'account_name'));
        $this->assertSame('1234567890', data_get($customer->wallet_bank_account, 'account_number'));
    }

    public function test_receipt_download_does_not_crash_when_authenticated_user_has_no_customer_profile(): void
    {
        $user = User::factory()->create([
            'type' => 'customer',
            'email_verified_at' => now(),
            'transaction_pin' => null,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'W2B-TEST-NO-CUSTOMER-001',
            'transaction_id' => 'W2B-TEST-NO-CUSTOMER-001',
            'payment_method' => 'wallet',
            'customer_id' => 1,
            'customer_email' => 'missing.customer@example.com',
            'customer_phone' => '08000000000',
            'customer_name' => 'Missing Customer',
            'discount' => 0,
            'unit_price' => 1000,
            'quantity' => 1,
            'total_amount' => 1020,
            'amount' => 1000,
            'balance_before' => 5000,
            'balance_after' => 3980,
            'descr' => 'Wallet to Bank Transfer',
            'product_name' => 'Wallet to Bank',
            'unique_element' => 'Wallet2Bank',
            'reason' => 'Wallet to Bank Transfer',
            'transfer_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware([
                CheckIpMiddleware::class,
                TransactionPinMiddleware::class,
                ReservedAccountCreationMiddleware::class,
            ])
            ->actingAs($user)
            ->get(route('transaction.receipt.download', $transaction->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_download_receipt_without_customer_profile(): void
    {
        $admin = $this->createAdminUser();
        $customer = $this->createCustomerUser('Receipt', 'Owner', [
            'kyc_status' => 'verified',
        ]);
        $bank = Bank::create([
            'bank_name' => 'Access Bank',
            'cbn_code' => '044',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '044',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction = TransactionLog::create([
            'status' => 'success',
            'reference_id' => 'W2B-TEST-RECEIPT-001',
            'transaction_id' => 'W2B-TEST-RECEIPT-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->customer->id,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'customer_name' => $customer->name,
            'discount' => 0,
            'unit_price' => 1000,
            'quantity' => 1,
            'total_amount' => 1020,
            'amount' => 1000,
            'balance_before' => 5000,
            'balance_after' => 3980,
            'descr' => 'Wallet to Bank Transfer',
            'product_name' => 'Wallet to Bank',
            'unique_element' => 'Wallet2Bank',
            'reason' => 'Wallet to Bank Transfer',
            'bank_id' => $bank->id,
            'bank_code' => $bank->cbn_code,
            'account_name' => 'Receipt Receiver',
            'account_number' => '1234567890',
            'transfer_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware([
                CheckIpMiddleware::class,
                TransactionPinMiddleware::class,
                ReservedAccountCreationMiddleware::class,
            ])
            ->actingAs($admin)
            ->get(route('transaction.receipt.download', $transaction->id));

        $response->assertOk();
    }

    public function test_admin_can_edit_customer_wallet_bank_account_details(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Editable', 'Customer');
        $customer = $user->customer;
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        $bank = Bank::create([
            'bank_name' => 'Access Bank',
            'cbn_code' => '044',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '044',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'bank_transfer_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance(MonnifyController::class, new class extends MonnifyController {
            public function verifyBankDetails(array $data)
            {
                return response()->json([
                    'status' => true,
                    'message' => 'Bank details verified successfully.',
                    'data' => [
                        'account_name' => 'Editable Customer',
                        'account_number' => $data['account_number'] ?? null,
                        'bank_name' => 'Access Bank',
                    ],
                ]);
            }
        });

        $customer->forceFill([
            'wallet_bank_account' => [
                'bank_id' => '1',
                'bank_code' => '000',
                'bank_name' => 'Old Bank',
                'account_number' => '0000000000',
                'account_name' => 'Old Name',
                'profile_name' => 'Editable Customer',
                'verified_name' => 'Old Name',
                'verified_at' => '2026-08-29 10:00:00',
                'verification_response' => ['status' => 'old'],
            ],
        ])->save();

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.wallet-bank-account.update', $customer->id), [
                'wallet_bank_bank' => '044',
                'wallet_bank_account_name' => 'Editable Customer',
                'wallet_bank_account_number' => '1234567890',
                'wallet_bank_profile_name' => 'Editable Customer',
                'wallet_bank_verified_name' => 'Editable Customer',
                'wallet_bank_verified_at' => '2026-08-29T17:30',
                'wallet_bank_verification_response' => json_encode([
                    'status' => true,
                    'message' => 'Updated',
                ]),
            ])
            ->assertRedirect();

        $customer->refresh();

        $this->assertSame($bank->id, data_get($customer->wallet_bank_account, 'bank_id'));
        $this->assertSame('Access Bank', data_get($customer->wallet_bank_account, 'bank_name'));
        $this->assertSame('044', data_get($customer->wallet_bank_account, 'bank_code'));
        $this->assertSame('1234567890', data_get($customer->wallet_bank_account, 'account_number'));
        $this->assertSame('Editable Customer', data_get($customer->wallet_bank_account, 'verified_name'));
        $this->assertSame('2026-08-29 17:30:00', data_get($customer->wallet_bank_account, 'verified_at'));
        $this->assertSame('Updated', data_get($customer->wallet_bank_account, 'verification_response.message'));
    }

    public function test_admin_can_override_wallet_bank_account_details_without_reverification(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Override', 'Customer');
        $customer = $user->customer;
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        $bank = Bank::create([
            'bank_name' => 'Override Bank',
            'cbn_code' => '055',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '055',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'bank_transfer_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.wallet-bank-account.update', $customer->id), [
                'wallet_bank_bank' => '055',
                'wallet_bank_account_name' => 'Manual Override Name',
                'wallet_bank_account_number' => '9876543210',
                'wallet_bank_profile_name' => 'Override Customer',
                'wallet_bank_verified_name' => 'Manual Override Name',
                'wallet_bank_verified_at' => '2026-08-29T18:00',
            ])
            ->assertRedirect();

        $customer->refresh();

        $this->assertSame($bank->id, data_get($customer->wallet_bank_account, 'bank_id'));
        $this->assertSame('Override Bank', data_get($customer->wallet_bank_account, 'bank_name'));
        $this->assertSame('Manual Override Name', data_get($customer->wallet_bank_account, 'account_name'));
        $this->assertSame('9876543210', data_get($customer->wallet_bank_account, 'account_number'));
        $this->assertSame('Override Customer', data_get($customer->wallet_bank_account, 'profile_name'));
        $this->assertSame('Manual Override Name', data_get($customer->wallet_bank_account, 'verified_name'));
    }

    public function test_customer_can_save_wallet_bank_account_when_verified_name_matches_even_if_order_differs(): void
    {
        $user = $this->createCustomerUser('David', 'Oghi', [], [
            'middlename' => 'Oghenerume',
        ]);
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        Bank::create([
            'bank_name' => 'Access Bank',
            'cbn_code' => '044',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '044',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance(MonnifyController::class, new class extends MonnifyController {
            public function verifyBankDetails(array $data)
            {
                return response()->json([
                    'status' => true,
                    'message' => 'Bank details verified successfully.',
                    'data' => [
                        'account_name' => 'OGHI DAVID OGHENERUME',
                        'account_number' => $data['account_number'] ?? null,
                        'bank_name' => 'Access Bank',
                    ],
                ]);
            }
        });

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('profile.wallet-bank-account.store'), [
                'bank' => '044',
                'account_number' => '1234567890',
            ])
            ->assertRedirect();

        $customer = $user->fresh('customer')->customer;

        $this->assertNotNull($customer?->wallet_bank_account);
        $this->assertSame('Access Bank', data_get($customer->wallet_bank_account, 'bank_name'));
        $this->assertSame('OGHI DAVID OGHENERUME', data_get($customer->wallet_bank_account, 'account_name'));
    }

    public function test_customer_cannot_save_wallet_bank_account_when_verified_name_does_not_match_profile(): void
    {
        $user = $this->createCustomerUser('John', 'Doe');
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
        ]);

        Bank::create([
            'bank_name' => 'Access Bank',
            'cbn_code' => '044',
            'status' => 'active',
            'provider_codes' => [
                'monnify' => '044',
            ],
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_verification_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance(MonnifyController::class, new class extends MonnifyController {
            public function verifyBankDetails(array $data)
            {
                return response()->json([
                    'status' => true,
                    'message' => 'Bank details verified successfully.',
                    'data' => [
                        'account_name' => 'Jane Doe',
                        'account_number' => $data['account_number'] ?? null,
                        'bank_name' => 'Access Bank',
                    ],
                ]);
            }
        });

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('profile.wallet-bank-account.store'), [
                'bank' => '044',
                'account_number' => '1234567890',
            ])
            ->assertSessionHas('error');

        $customer = $user->fresh('customer')->customer;
        $this->assertEmpty($customer?->wallet_bank_account);
    }

    public function test_wallet_to_bank_page_prompts_for_locked_bank_account_when_missing(): void
    {
        $user = $this->createCustomerUser('Page', 'Tester', [
            'kyc_status' => 'verified',
        ]);

        $provider = API::create([
            'name' => 'Wallet Provider',
            'slug' => 'monnify',
            'status' => 'active',
            'pricing_data_status' => true,
            'pricing_data' => json_encode([
                [
                    'min_amount' => 100,
                    'max_amount' => 10000,
                    'provider_fee' => 50,
                    'extra_charge' => 0,
                ],
            ]),
            'extra_charges' => json_encode([]),
        ]);

        $category = Category::create([
            'name' => 'Wallet Transfer',
            'slug' => 'wallet-transfer-profile-test',
            'type' => 'wallet2bank',
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Wallet to Bank',
            'slug' => 'wallet-to-bank-profile-test',
            'type' => 'wallet2bank',
            'category_id' => $category->id,
            'status' => 'active',
            'api_id' => $provider->id,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'bank_transfer_provider_id' => $provider->id,
            'bank_verification_provider_id' => $provider->id,
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('wallet-to-bank', 'wallet-to-bank-profile-test'));

        $response->assertOk();
        $response->assertSee('You have not set up your wallet to bank account details yet.');
        $response->assertSee('id="transfer-submit"', false);
        $response->assertSee('disabled', false);
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

    private function createCustomerUser(string $firstName, string $lastName, array $customerAttributes = [], array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email_verified_at' => now(),
            'status' => 'active',
        ], $userAttributes));

        Customer::create(array_merge([
            'user_id' => $user->id,
            'wallet' => 0,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
            'can_access_w2bank' => 1,
            'can_access_w2bank_auto' => 0,
            'can_access_a2c' => 0,
        ], $customerAttributes));

        return $user->fresh(['customer']);
    }
}
