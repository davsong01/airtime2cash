<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_customers_can_not_authenticate(): void
    {
        $user = User::factory()->create([
            'type' => 'customer',
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This account has been suspended. Please contact support.');
    }

    public function test_inactive_customer_sessions_are_revoked_on_the_next_request(): void
    {
        $user = User::factory()->create([
            'type' => 'customer',
            'status' => 'delete',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This account has been deleted. Please contact support.');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
