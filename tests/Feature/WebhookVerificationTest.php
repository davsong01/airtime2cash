<?php

namespace Tests\Feature;

use App\Http\Controllers\Providers\AutoSyncController;
use App\Http\Controllers\Providers\KoraController;
use App\Http\Controllers\Providers\MonnifyController;
use App\Http\Controllers\Providers\PaystackController;
use App\Http\Controllers\Providers\SageController;
use App\Models\Admin;
use App\Models\API;
use App\Models\Webhook;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider webhookControllersProvider
     */
    public function test_webhook_signature_verification_resolves_the_expected_reference(string $controllerClass, array $payload, string $expectedReference): void
    {
        $provider = $this->seedProvider($controllerClass);
        $request = $this->signedWebhookRequest($provider, $controllerClass, $payload);
        $controller = $this->resolveController($controllerClass, $provider);

        $verification = $controller->verifyWebhookSignature($request);

        $this->assertTrue($verification['status']);
        $this->assertSame($expectedReference, $verification['reference']);
    }

    /**
     * @dataProvider webhookControllersProvider
     */
    public function test_webhook_signature_verification_rejects_invalid_signatures(string $controllerClass, array $payload, string $expectedReference): void
    {
        $provider = $this->seedProvider($controllerClass);
        $request = $this->unsignedWebhookRequest($controllerClass, $payload, $provider);
        $controller = $this->resolveController($controllerClass, $provider);

        $verification = $controller->verifyWebhookSignature($request);

        $this->assertFalse($verification['status']);
        $this->assertSame($expectedReference, $verification['reference']);
    }

    public function test_webhook_service_logs_raw_body_and_ignores_duplicates(): void
    {
        $provider = $this->seedProvider(KoraController::class);

        $payload = [
            'data' => [
                'transaction' => [
                    'reference' => 'W2B-TEST-0001',
                    'status' => 'pending',
                ],
            ],
            'provider_status' => 'pending',
        ];

        $request = $this->signedWebhookRequest($provider, KoraController::class, $payload);
        $service = app(WebhookService::class);
        $this->ensureWebhookTableExists();

        $firstResponse = $service->logWebhookResponse($request, $provider->id);
        $this->assertSame(200, $firstResponse->status());
        $this->assertDatabaseCount('webhooks', 1);

        $secondResponse = $service->logWebhookResponse($request, $provider->id);
        $this->assertSame(200, $secondResponse->status());
        $this->assertSame('Duplicate webhook ignored.', $secondResponse->getData(true)['message']);
        $this->assertDatabaseCount('webhooks', 1);

        $webhook = Webhook::first();
        $this->assertSame('W2B-TEST-0001', $webhook->provider_reference);
        $this->assertSame('pending', $webhook->provider_status);
        $this->assertTrue($webhook->signature_valid);
    }

    public function test_webhook_service_rejects_invalid_signatures_without_logging(): void
    {
        $provider = $this->seedProvider(PaystackController::class);

        $payload = [
            'data' => [
                'reference' => 'PAYSTACK-BAD-001',
            ],
        ];

        $request = $this->unsignedWebhookRequest(PaystackController::class, $payload, $provider);
        $service = app(WebhookService::class);
        $this->ensureWebhookTableExists();

        $response = $service->logWebhookResponse($request, $provider->id);

        $this->assertSame(403, $response->status());
        $this->assertSame('Invalid Paystack webhook signature.', $response->getData(true)['message']);
        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_legacy_payment_callback_routes_still_store_and_analyze_callbacks(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $customer = \App\Models\Customer::create([
            'user_id' => $user->id,
        ]);

        \App\Models\PaymentGateway::create([
            'id' => 1,
            'name' => 'Monnify',
        ]);

        \App\Models\ReservedAccountNumber::create([
            'account_number' => '1234567890',
            'account_name' => 'Test Customer',
            'bank_name' => 'Test Bank',
            'paymentgateway_id' => 1,
            'status' => 'active',
            'customer_id' => $customer->id,
            'bvn' => '22222222222',
        ]);

        $payload = [
            'eventData' => [
                'destinationAccountInformation' => [
                    'accountNumber' => '1234567890',
                ],
                'paymentSourceInformation' => [
                    ['sessionId' => 'sess_123'],
                ],
                'transactionReference' => 'LEGACY-REF-001',
                'paymentReference' => 'LEGACY-REF-001',
                'paymentMethod' => 'CARD',
                'paidOn' => now()->toIso8601String(),
            ],
        ];

        $response = $this->post(route('log.payment.response', 1), $payload);
        $response->assertSuccessful();
        $this->assertDatabaseHas('reserved_account_callbacks', [
            'provider_id' => 1,
            'account_number' => '1234567890',
            'transaction_reference' => 'LEGACY-REF-001',
        ]);

        $this->get(route('callback.analyze'))->assertSuccessful();
        $callback = \App\Models\ReservedAccountCallback::where('transaction_reference', 'LEGACY-REF-001')->first();
        $this->assertNotNull($callback);
        $this->assertStringStartsWith('PICKED-', (string) $callback->status);
    }

    public function test_admin_can_bulk_revert_and_delete_webhooks(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'permissions' => '',
        ]);

        $this->ensureWebhookTableExists();
        $provider = $this->seedProvider(PaystackController::class);

        $webhookIds = collect([
            Webhook::create([
                'api_id' => $provider->id,
                'transaction_id' => 'W2B-REVERT-001',
                'provider_reference' => 'WEBHOOK-REVERT-001',
                'request_ref' => 'REQ-REVERT-001',
                'provider_status' => 'pending',
                'processing_status' => 'processed',
                'signature_valid' => true,
                'headers' => ['x-test' => '1'],
                'payload' => ['reference' => 'WEBHOOK-REVERT-001'],
                'last_error' => 'Old error',
                'resolved_by' => 999,
                'processed_at' => now(),
                'resolved_at' => now(),
            ])->id,
            Webhook::create([
                'api_id' => $provider->id,
                'transaction_id' => 'W2B-DELETE-001',
                'provider_reference' => 'WEBHOOK-DELETE-001',
                'request_ref' => 'REQ-DELETE-001',
                'provider_status' => 'failed',
                'processing_status' => 'failed',
                'signature_valid' => true,
                'headers' => ['x-test' => '2'],
                'payload' => ['reference' => 'WEBHOOK-DELETE-001'],
            ])->id,
        ]);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('admin.webhooks.bulk-revert'), [
                'webhook_ids' => [$webhookIds[0]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhookIds[0],
            'processing_status' => 'pending',
            'resolved_by' => null,
            'last_error' => null,
        ]);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('admin.webhooks.bulk-delete'), [
                'webhook_ids' => [$webhookIds[1]],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('webhooks', [
            'id' => $webhookIds[1],
        ]);
    }

    public static function webhookControllersProvider(): array
    {
        return [
            'paystack' => [
                PaystackController::class,
                ['data' => ['reference' => 'PAYSTACK-REF-001']],
                'PAYSTACK-REF-001',
            ],
            'monnify' => [
                MonnifyController::class,
                ['eventData' => ['paymentReference' => 'MONNIFY-REF-002']],
                'MONNIFY-REF-002',
            ],
            'kora' => [
                KoraController::class,
                ['data' => ['transaction' => ['reference' => 'KORA-REF-003']]],
                'KORA-REF-003',
            ],
            'sagecloud' => [
                SageController::class,
                ['transaction' => ['reference' => 'SAGE-REF-004']],
                'SAGE-REF-004',
            ],
            'autosync' => [
                AutoSyncController::class,
                [
                    'transaction' => ['reference' => 'AUTO-REF-005'],
                    'hash' => hash('sha256', 'test-webhook-secret:AUTO-REF-005'),
                ],
                'AUTO-REF-005',
            ],
        ];
    }

    private function providerSlugFor(string $controllerClass): string
    {
        return match ($controllerClass) {
            PaystackController::class => 'paystack',
            MonnifyController::class => 'monnify',
            KoraController::class => 'kora',
            SageController::class => 'sagecloud',
            AutoSyncController::class => 'autosync',
            default => throw new \InvalidArgumentException('Unsupported controller: ' . $controllerClass),
        };
    }

    private function seedProvider(string $controllerClass): API
    {
        return API::updateOrCreate([
            'slug' => $this->providerSlugFor($controllerClass),
        ], [
            'name' => match ($controllerClass) {
                PaystackController::class => 'Paystack',
                MonnifyController::class => 'Monnify',
                KoraController::class => 'Kora',
                SageController::class => 'SageCloud',
                AutoSyncController::class => 'AutoSync',
                default => 'Provider',
            },
            'status' => 'active',
            'secret_key' => match ($controllerClass) {
                SageController::class => 'sage-secret',
                AutoSyncController::class => 'auto-access-token',
                default => 'test-secret',
            },
            'public_key' => match ($controllerClass) {
                AutoSyncController::class => 'auto-webhook-secret',
                default => 'test-public',
            },
        ]);
    }

    private function signedWebhookRequest(API $provider, string $controllerClass, array $payload): Request
    {
        if ($controllerClass === AutoSyncController::class) {
            $payload['hash'] = hash('sha256', sprintf('%s:%s', (string) $provider->public_key, data_get($payload, 'transaction.reference')));
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $server = ['CONTENT_TYPE' => 'application/json'];

        return match ($controllerClass) {
            PaystackController::class => Request::create('/', 'POST', [], [], [], array_merge($server, [
                'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, (string) $provider->secret_key),
            ]), $body),
            MonnifyController::class => Request::create('/', 'POST', [], [], [], array_merge($server, [
                'HTTP_MONNIFY_SIGNATURE' => hash_hmac('sha512', $body, (string) $provider->secret_key),
            ]), $body),
            KoraController::class => Request::create('/', 'POST', [], [], [], array_merge($server, [
                'HTTP_X_KORAPAY_SIGNATURE' => hash_hmac('sha256', json_encode($payload['data'] ?? [], JSON_UNESCAPED_SLASHES), (string) $provider->secret_key),
            ]), $body),
            SageController::class => Request::create('/', 'POST', [], [], [], array_merge($server, [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $provider->secret_key,
            ]), $body),
            AutoSyncController::class => Request::create('/', 'POST', [], [], [], $server, $body),
            default => throw new \InvalidArgumentException('Unsupported controller: ' . $controllerClass),
        };
    }

    private function unsignedWebhookRequest(string $controllerClass, array $payload, API $provider): Request
    {
        if ($controllerClass === AutoSyncController::class) {
            $payload['hash'] = hash('sha256', 'wrong-secret:' . data_get($payload, 'transaction.reference'));
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $server = ['CONTENT_TYPE' => 'application/json'];

        return match ($controllerClass) {
            PaystackController::class => Request::create('/', 'POST', [], [], [], $server, $body),
            MonnifyController::class => Request::create('/', 'POST', [], [], [], $server, $body),
            KoraController::class => Request::create('/', 'POST', [], [], [], $server, $body),
            SageController::class => Request::create('/', 'POST', [], [], [], array_merge($server, [
                'HTTP_AUTHORIZATION' => 'Bearer wrong-secret',
            ]), $body),
            AutoSyncController::class => Request::create('/', 'POST', [], [], [], $server, $body),
            default => throw new \InvalidArgumentException('Unsupported controller: ' . $controllerClass),
        };
    }

    private function resolveController(string $controllerClass, API $provider): object
    {
        if ($controllerClass === AutoSyncController::class) {
            return app($controllerClass);
        }

        return new $controllerClass();
    }

    private function ensureWebhookTableExists(): void
    {
        if (Schema::hasTable('webhooks')) {
            return;
        }

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('provider_reference')->nullable()->index();
            $table->string('request_ref')->nullable()->index();
            $table->string('provider_status')->nullable()->index();
            $table->string('processing_status')->default('pending')->index();
            $table->boolean('signature_valid')->default(false)->index();
            $table->longText('headers')->nullable();
            $table->longText('payload');
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }
}
