<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIpMiddleware;
use App\Http\Middleware\RouteProtectionMiddleware;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerAccessControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_customers_page_shows_service_access_badges(): void
    {
        $admin = $this->createAdminUser();

        $enabledUser = $this->createCustomerUser('Enabled', 'Customer', [
            'can_access_w2bank' => 1,
            'can_access_a2c' => 0,
        ]);

        $disabledUser = $this->createCustomerUser('Disabled', 'Customer', [
            'can_access_w2bank' => 0,
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
        $response->assertSee('Wallet 2 Bank Enabled');
        $response->assertSee('Airtime 2 Cash Disabled');
        $response->assertSee('Wallet 2 Bank Disabled');
        $response->assertSee('Airtime 2 Cash Enabled');

        $this->assertDatabaseHas('customers', [
            'id' => $enabledUser->customer->id,
            'can_access_w2bank' => 1,
            'can_access_a2c' => 0,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $disabledUser->customer->id,
            'can_access_w2bank' => 0,
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
                'action' => 'enable_w2bank_access',
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
            'can_access_a2c' => 0,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $secondUser->customer->id,
            'can_access_w2bank' => 1,
            'can_access_a2c' => 0,
        ]);
    }

    public function test_blocked_wallet_and_airtime_pages_show_access_message_and_whatsapp_contact(): void
    {
        $user = $this->createCustomerUser('Blocked', 'Customer', [
            'can_access_w2bank' => 0,
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
        $walletResponse->assertSee('Contact Admin on WhatsApp');
        $walletResponse->assertSee('2348012345678');

        $a2cResponse = $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('airtime-to-cash'));

        $a2cResponse->assertStatus(403);
        $a2cResponse->assertSee('This service (Airtime 2 Cash) is not available for you at the moment');
        $a2cResponse->assertSee('Contact Admin on WhatsApp');
        $a2cResponse->assertSee('2348012345678');
    }

    public function test_account_tab_exposes_and_saves_service_access_controls(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createCustomerUser('Account', 'Tester', [
            'can_access_w2bank' => 0,
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
        $response->assertSee('Wallet 2 Bank access');
        $response->assertSee('Airtime 2 Cash access');

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('customers.update', $user->id), [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'status' => $user->status,
                'phone' => $user->phone,
                'customerlevel' => $user->customer->customer_level,
                'can_access_w2bank' => 1,
                'can_access_a2c' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $user->customer->id,
            'can_access_w2bank' => 1,
            'can_access_a2c' => 1,
        ]);
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

    private function createCustomerUser(string $firstName, string $lastName, array $customerAttributes = []): User
    {
        $user = User::factory()->create([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        Customer::create(array_merge([
            'user_id' => $user->id,
            'wallet' => 0,
            'referal_wallet' => 0,
            'a2cashwallet' => 0,
            'can_access_w2bank' => 0,
            'can_access_a2c' => 0,
        ], $customerAttributes));

        return $user->fresh(['customer']);
    }
}
