<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIpMiddleware;
use App\Http\Middleware\RouteProtectionMiddleware;
use App\Models\Admin;
use App\Models\API;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_bvn_verification_controls(): void
    {
        $admin = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'Settings',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        Admin::create([
            'user_id' => $admin->id,
            'permissions' => '',
        ]);

        API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'is_bank_verification' => true,
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
            ->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('BVN Verification Provider');
        $response->assertSee('BVN Verification Mode');
        $response->assertSee('Manual');
        $response->assertSee('Auto');
    }

    public function test_settings_update_persists_bvn_verification_charge(): void
    {
        $admin = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'Charge',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        Admin::create([
            'user_id' => $admin->id,
            'permissions' => '',
        ]);

        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'is_bank_transfer' => true,
            'is_bank_verification' => true,
        ]);

        DB::table('settings')->insert([
            'currency' => '₦',
            'logo' => 'site/upgrade.jpg',
            'dashboard_logo' => 'site/upgrade.jpg',
            'favicon' => 'site/upgrade.jpg',
            'bank_transfer_provider_id' => $provider->id,
            'bank_verification_provider_id' => $provider->id,
            'bvn_verification_provider_id' => $provider->id,
            'bvn_verification_mode' => 'manual',
            'bvn_verification_charge' => 0,
            'wallet_to_bank_transfer_auto_status' => 'enabled',
            'wallet_to_bank_transfer_manual_status' => 'enabled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware([
                AdminMiddleware::class,
                CheckIpMiddleware::class,
                RouteProtectionMiddleware::class,
            ])
            ->actingAs($admin)
            ->post(route('settings.update'), [
                'menu_text_color' => '#111111',
                'menu_background_color' => '#222222',
                'active_color' => '#333333',
                'block_header_color' => '#444444',
                'dasboard_customer_details_color' => '#555555',
                'bank_transfer_provider_id' => $provider->id,
                'bank_verification_provider_id' => $provider->id,
                'bvn_verification_provider_id' => $provider->id,
                'bvn_verification_mode' => 'auto',
                'bvn_verification_charge' => '250.00',
                'wallet_to_bank_transfer_auto_status' => 'enabled',
                'wallet_to_bank_transfer_manual_status' => 'enabled',
                'show_provider_status_on_customer_pages' => 1,
                'google_dashboard_ad_enabled' => 1,
                'customer_layout' => 'legacy',
                'admin_layout' => 'legacy',
                'currency' => '₦',
                'payment_gateway' => $provider->slug,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'bvn_verification_mode' => 'auto',
            'bvn_verification_charge' => 250,
            'bvn_verification_provider_id' => $provider->id,
        ]);
    }
}
