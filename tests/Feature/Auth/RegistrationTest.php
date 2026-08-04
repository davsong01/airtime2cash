<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CheckIpMiddleware;
use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->withoutMiddleware(CheckIpMiddleware::class);

        Settings::create([
            'customer_layout' => 'modern',
            'admin_layout' => 'legacy',
            'currency' => 'NGN',
            'login_email_notification' => 'no',
        ]);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '08012345678',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'privacy' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '08012345678',
            'username' => 'testuser',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('kyc_data', [
            'key' => 'PHONE_NUMBER',
            'value' => '08012345678',
            'status' => 'unverified',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertNotNull(auth()->user()->fresh()->email_verified_at);
        $this->get('/dashboard')->assertOk();
        $this->get('/profile')->assertOk();
        $this->get('/customer-update-kyc-info')->assertOk();
        $this->get('/customer-load-wallet')->assertRedirect(route('dashboard'));
    }

    public function test_username_is_generated_when_it_is_not_submitted(): void
    {
        $this->withoutMiddleware(CheckIpMiddleware::class);

        Settings::create([
            'customer_layout' => 'modern',
            'admin_layout' => 'legacy',
            'currency' => 'NGN',
            'login_email_notification' => 'no',
        ]);

        $this->post('/register', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'phone' => '08012345679',
            'email' => 'ada@example.com',
            'password' => 'password',
            'privacy' => '1',
        ])->assertRedirect(route('login'));

        $user = \App\Models\User::where('email', 'ada@example.com')->firstOrFail();

        $this->assertMatchesRegularExpression('/^adalovelace\d{3,4}$/', $user->username);
    }
}
