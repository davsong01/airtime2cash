<?php

namespace Tests\Feature;

use App\Http\Controllers\Providers\AutoSyncController;
use App\Http\Controllers\Providers\KoraController;
use App\Http\Controllers\Providers\MonnifyController;
use App\Http\Controllers\Providers\PaystackController;
use App\Http\Controllers\Providers\SageController;
use App\Http\Controllers\APIController;
use App\Http\Controllers\TransactionController;
use App\Models\Admin;
use App\Models\API;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\TransactionLog;
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

    public function test_analyze_callback_cron_processes_pending_webhooks_and_skips_final_transactions(): void
    {
        $this->ensureWebhookTableExists();

        $provider = $this->seedProvider(PaystackController::class);
        $user = User::factory()->create([
            'firstname' => 'Cron',
            'lastname' => 'Tester',
            'email' => 'cron.tester@example.com',
            'phone' => '08030000000',
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
        ]);

        $pendingTransaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'REF-PENDING-001',
            'transaction_id' => 'W2B-PENDING-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 500,
            'amount' => 500,
            'total_amount' => 605,
            'balance_before' => 5000,
            'balance_after' => 4395,
            'quantity' => 1,
            'request_data' => json_encode([]),
            'api_response' => null,
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Wallet to bank transfer initiated.',
            'provider_charge' => 105,
        ]);

        $finalTransaction = TransactionLog::create([
            'status' => 'declined',
            'reference_id' => 'REF-FINAL-001',
            'transaction_id' => 'W2B-FINAL-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 400,
            'amount' => 400,
            'total_amount' => 505,
            'balance_before' => 4395,
            'balance_after' => 3890,
            'quantity' => 1,
            'request_data' => json_encode([]),
            'api_response' => null,
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Already declined transaction.',
            'provider_charge' => 105,
        ]);

        Webhook::create([
            'api_id' => $provider->id,
            'customer_id' => $customer->id,
            'transaction_id' => $pendingTransaction->transaction_id,
            'provider_reference' => 'PROVIDER-PENDING-001',
            'request_ref' => $pendingTransaction->transaction_id,
            'provider_status' => 'successful',
            'processing_status' => 'pending',
            'signature_valid' => true,
            'headers' => ['x-test' => 'pending'],
            'payload' => [
                'transaction' => [
                    'reference' => $pendingTransaction->transaction_id,
                    'request_ref' => $pendingTransaction->transaction_id,
                    'status' => 'successful',
                    'details' => 'Completed by provider.',
                ],
            ],
        ]);

        Webhook::create([
            'api_id' => $provider->id,
            'customer_id' => $customer->id,
            'transaction_id' => $finalTransaction->transaction_id,
            'provider_reference' => 'PROVIDER-FINAL-001',
            'request_ref' => $finalTransaction->transaction_id,
            'provider_status' => 'successful',
            'processing_status' => 'pending',
            'signature_valid' => true,
            'headers' => ['x-test' => 'final'],
            'payload' => [
                'transaction' => [
                    'reference' => $finalTransaction->transaction_id,
                    'request_ref' => $finalTransaction->transaction_id,
                    'status' => 'successful',
                    'details' => 'Should be ignored because transaction is already final.',
                ],
            ],
        ]);

        app(WebhookService::class)->analyzeWebhookResponse(10);

        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $pendingTransaction->transaction_id,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $finalTransaction->transaction_id,
            'status' => 'declined',
        ]);

        $this->assertDatabaseHas('webhooks', [
            'transaction_id' => $pendingTransaction->transaction_id,
            'processing_status' => 'processed',
        ]);

        $this->assertDatabaseHas('webhooks', [
            'transaction_id' => $finalTransaction->transaction_id,
            'processing_status' => 'processed',
        ]);
    }

    public function test_sage_pull_banks_returns_a_normalized_banks_list_without_a_data_wrapper(): void
    {
        $controller = new class extends SageController {
            public function __construct()
            {
                $this->base_url = 'https://sage.test';
                $this->secret_key = 'sage-secret';
                $this->public_key = 'sage-public';
                $this->control = $this;
            }

            public function login()
            {
                return [
                    'success' => true,
                    'status' => 'success',
                    'data' => [
                        'token' => [
                            'access_token' => 'sage-access-token',
                        ],
                    ],
                ];
            }

            public function basicApiCall($url, $payload, $headers, $method = 'POST')
            {
                if (str_contains((string) $url, '/merchant/authorization')) {
                    return $this->login();
                }

                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Banks fetched successfully',
                    'banks' => [
                        [
                            'cbn_code' => '110005',
                            'bank_name' => '3line Card Management Limited',
                        ],
                        [
                            'cbn_code' => '120001',
                            'bank_name' => '9Payment Service Bank',
                        ],
                    ],
                ];
            }
        };

        $response = $controller->pullBanks();

        $this->assertSame('success', $response['status']);
        $this->assertArrayHasKey('banks', $response);
        $this->assertArrayNotHasKey('data', $response);
        $this->assertCount(2, $response['banks']);
        $this->assertSame('110005', $response['banks'][0]['cbn_code']);
        $this->assertSame('3line Card Management Limited', $response['banks'][0]['bank_name']);
    }

    public function test_admin_bank_sync_merges_sage_provider_codes_into_existing_banks(): void
    {
        $provider = API::create([
            'name' => 'SageCloud',
            'slug' => 'sagecloud',
            'status' => 'active',
            'is_bank_transfer' => true,
            'is_bank_verification' => true,
        ]);

        Bank::create([
            'bank_name' => '3line Card Management Limited',
            'cbn_code' => '110005',
            'status' => 'active',
            'provider_codes' => [
                'paystack' => '110005',
            ],
            'provider_meta' => [],
        ]);

        $fakeController = new class extends SageController {
            public function __construct()
            {
                // Intentionally empty for the test.
            }

            public function pullBanks(): array
            {
                return [
                    'status' => 'success',
                    'banks' => [
                        [
                            'cbn_code' => '110005',
                            'bank_name' => '3line Card Management Limited',
                            'provider_codes' => [
                                'sagecloud' => '110005',
                            ],
                        ],
                        [
                            'cbn_code' => '120001',
                            'bank_name' => '9Payment Service Bank',
                            'provider_codes' => [
                                'sagecloud' => '120001',
                            ],
                        ],
                    ],
                ];
            }
        };

        $this->app->instance(SageController::class, $fakeController);

        $response = app(APIController::class)->pullBanks($provider);
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertSame(2, $payload['count']);
        $this->assertSame(2, $payload['synced_count']);

        $this->assertDatabaseHas('banks', [
            'cbn_code' => '110005',
        ]);

        $bank = Bank::where('cbn_code', '110005')->first();
        $codes = $bank?->provider_codes ?? [];

        $this->assertSame('110005', $codes['sagecloud'] ?? null);
        $this->assertSame('110005', $codes['paystack'] ?? null);

        $this->assertDatabaseHas('banks', [
            'cbn_code' => '120001',
        ]);
    }

    public function test_monnify_transfer_includes_source_account_number_in_payload(): void
    {
        API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $controller = new class extends MonnifyController {
            public array $capturedPayload = [];

            public function login()
            {
                return 'monnify-token';
            }

            public function basicApiCall($url, $payload, $headers, $method = 'POST')
            {
                $this->capturedPayload = json_decode((string) $payload, true) ?: [];

                return [
                    'requestSuccessful' => true,
                    'responseCode' => '0',
                    'responseMessage' => 'success',
                    'responseBody' => [
                        'status' => 'SUCCESS',
                    ],
                ];
            }
        };

        $response = $controller->transfer([
            'amount' => 2500,
            'transaction_id' => 'W2B-MONNIFY-001',
            'bank_code' => '058',
            'provider_bank_code' => '058',
            'account_number' => '0018455422',
            'account_name' => 'David Oghi',
            'narration' => 'Wallet transfer test',
        ]);

        $this->assertSame('success', $response['status']);
        $this->assertSame('1234567890', $controller->capturedPayload['sourceAccountNumber'] ?? null);
        $this->assertSame('0018455422', $controller->capturedPayload['destinationAccountNumber'] ?? null);
        $this->assertSame('058', $controller->capturedPayload['destinationBankCode'] ?? null);
    }

    public function test_monnify_authorize_transfer_hits_validate_otp_endpoint(): void
    {
        API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $controller = new class extends MonnifyController {
            public array $capturedPayload = [];
            public string $capturedUrl = '';

            public function login()
            {
                return 'monnify-token';
            }

            public function basicApiCall($url, $payload, $headers, $method = 'POST')
            {
                $this->capturedUrl = (string) $url;
                $this->capturedPayload = json_decode((string) $payload, true) ?: [];

                return [
                    'requestSuccessful' => true,
                    'responseCode' => '0',
                    'responseMessage' => 'success',
                    'responseBody' => [
                        'status' => 'SUCCESS',
                    ],
                ];
            }
        };

        $response = $controller->authorizeTransfer('W2B-MONNIFY-OTP-001', '123456');

        $this->assertSame('success', $response['status']);
        $this->assertStringEndsWith('/api/v2/disbursements/single/validate-otp', $controller->capturedUrl);
        $this->assertSame('W2B-MONNIFY-OTP-001', $controller->capturedPayload['reference'] ?? null);
        $this->assertSame('123456', $controller->capturedPayload['authorizationCode'] ?? null);
    }

    public function test_admin_can_authorize_pending_monnify_transaction_from_the_resolve_modal(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'permissions' => '',
        ]);

        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $customerUser = User::factory()->create([
            'firstname' => 'David',
            'lastname' => 'Oghi',
            'email' => 'david@example.com',
            'phone' => '08030000000',
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'REF-MONNIFY-OTP-001',
            'transaction_id' => 'W2B-MONNIFY-OTP-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $customerUser->email,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 500,
            'amount' => 500,
            'total_amount' => 605,
            'balance_before' => 5000,
            'balance_after' => 4395,
            'quantity' => 1,
            'request_data' => json_encode([
                'reference' => 'W2B-MONNIFY-OTP-001',
            ]),
            'api_response' => json_encode([
                'requestSuccessful' => false,
                'responseCode' => '99',
                'responseMessage' => 'PENDING_AUTHORIZATION',
                'responseBody' => [
                    'reference' => 'W2B-MONNIFY-OTP-001',
                    'status' => 'PENDING_AUTHORIZATION',
                ],
            ]),
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Wallet to Bank Transfer initiated.',
            'provider_charge' => 105,
        ]);

        $fakeController = new class extends MonnifyController {
            public function login()
            {
                return 'monnify-token';
            }

            public function authorizeTransfer(string $reference, string $authorizationCode): array
            {
                return [
                    'status' => 'failed',
                    'provider_status' => 'failed',
                    'error' => 'Transaction not awaiting authorization',
                    'api_response' => [
                        'requestSuccessful' => false,
                        'responseMessage' => 'Transaction not awaiting authorization',
                        'responseCode' => 'D01',
                    ],
                    'request_data' => [
                        'reference' => $reference,
                        'authorizationCode' => $authorizationCode,
                    ],
                ];
            }

            public function singleTransferStatus(string $reference): array
            {
                return [
                    'status' => 'success',
                    'provider_status' => 'SUCCESS',
                    'api_response' => [
                        'requestSuccessful' => true,
                        'responseCode' => '0',
                        'responseMessage' => 'success',
                        'responseBody' => [
                            'reference' => $reference,
                            'status' => 'SUCCESS',
                        ],
                    ],
                    'request_data' => [
                        'reference' => $reference,
                    ],
                ];
            }
        };

        $this->app->instance(MonnifyController::class, $fakeController);

        $this->actingAs($user);

        $request = Request::create('/', 'POST', [
            'action' => 'authorize_monnify',
            'authorization_code' => '123456',
        ]);

        $response = app(TransactionController::class)->resolvePendingTransactionAction($request, $transaction);

        $this->assertTrue(method_exists($response, 'getSession'));
        $this->assertSame('Monnify transfer authorized successfully.', $response->getSession()->get('message'));

        $this->assertDatabaseHas('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'success',
        ]);
    }

    public function test_monnify_pending_authorization_does_not_mark_success_when_transfer_status_is_still_pending(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'permissions' => '',
        ]);

        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $customerUser = User::factory()->create([
            'firstname' => 'Wrong',
            'lastname' => 'OTP',
            'email' => 'wrong.otp@example.com',
            'phone' => '08050000000',
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'REF-MONNIFY-OTP-PENDING',
            'transaction_id' => 'W2B-MONNIFY-OTP-PENDING',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $customerUser->email,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 500,
            'amount' => 500,
            'total_amount' => 605,
            'balance_before' => 5000,
            'balance_after' => 4395,
            'quantity' => 1,
            'request_data' => json_encode([
                'reference' => 'W2B-MONNIFY-OTP-PENDING',
            ]),
            'api_response' => json_encode([
                'requestSuccessful' => false,
                'responseCode' => '99',
                'responseMessage' => 'PENDING_AUTHORIZATION',
                'responseBody' => [
                    'reference' => 'W2B-MONNIFY-OTP-PENDING',
                    'status' => 'PENDING_AUTHORIZATION',
                ],
            ]),
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Wallet to Bank Transfer initiated.',
            'provider_charge' => 105,
        ]);

        $fakeController = new class extends MonnifyController {
            public function login()
            {
                return 'monnify-token';
            }

            public function authorizeTransfer(string $reference, string $authorizationCode): array
            {
                return [
                    'status' => 'failed',
                    'provider_status' => 'pending_authorization',
                    'error' => 'Transaction not awaiting authorization',
                    'api_response' => [
                        'requestSuccessful' => false,
                        'responseMessage' => 'Transaction not awaiting authorization',
                        'responseCode' => 'D01',
                    ],
                    'request_data' => [
                        'reference' => $reference,
                        'authorizationCode' => $authorizationCode,
                    ],
                ];
            }

            public function singleTransferStatus(string $reference): array
            {
                return [
                    'status' => 'pending',
                    'provider_status' => 'PENDING_AUTHORIZATION',
                    'api_response' => [
                        'requestSuccessful' => true,
                        'responseCode' => '0',
                        'responseMessage' => 'success',
                        'responseBody' => [
                            'reference' => $reference,
                            'status' => 'PENDING_AUTHORIZATION',
                        ],
                    ],
                    'request_data' => [
                        'reference' => $reference,
                    ],
                ];
            }
        };

        $this->app->instance(MonnifyController::class, $fakeController);
        $this->actingAs($user);

        $request = Request::create('/', 'POST', [
            'action' => 'authorize_monnify',
            'authorization_code' => '096043',
        ]);

        $response = app(TransactionController::class)->resolvePendingTransactionAction($request, $transaction);

        $this->assertTrue(method_exists($response, 'getSession'));
        $this->assertDatabaseHas('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'success',
        ]);

        $storedTransaction = TransactionLog::find($transaction->id);
        $apiResponse = json_decode((string) $storedTransaction?->api_response, true) ?: [];
        $this->assertSame('PENDING_AUTHORIZATION', data_get($apiResponse, 'responseBody.status'));
    }

    public function test_monnify_authorization_error_is_shown_to_admin_even_when_status_remains_pending(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'permissions' => '',
        ]);

        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $customerUser = User::factory()->create([
            'firstname' => 'Error',
            'lastname' => 'Shown',
            'email' => 'error.shown@example.com',
            'phone' => '08060000000',
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
        ]);

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'REF-MONNIFY-OTP-ERROR',
            'transaction_id' => 'W2B-MONNIFY-OTP-ERROR',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $customerUser->email,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 500,
            'amount' => 500,
            'total_amount' => 605,
            'balance_before' => 5000,
            'balance_after' => 4395,
            'quantity' => 1,
            'request_data' => json_encode([
                'reference' => 'W2B-MONNIFY-OTP-ERROR',
            ]),
            'api_response' => json_encode([
                'requestSuccessful' => false,
                'responseCode' => '99',
                'responseMessage' => 'PENDING_AUTHORIZATION',
                'responseBody' => [
                    'reference' => 'W2B-MONNIFY-OTP-ERROR',
                    'status' => 'PENDING_AUTHORIZATION',
                ],
            ]),
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Wallet to Bank Transfer initiated.',
            'provider_charge' => 105,
        ]);

        $fakeController = new class extends MonnifyController {
            public function login()
            {
                return 'monnify-token';
            }

            public function authorizeTransfer(string $reference, string $authorizationCode): array
            {
                return [
                    'status' => 'failed',
                    'provider_status' => 'failed',
                    'error' => 'authorizationCode must be 6 digits',
                    'api_response' => [
                        'requestSuccessful' => false,
                        'responseMessage' => 'authorizationCode must be 6 digits',
                        'responseCode' => '99',
                    ],
                    'request_data' => [
                        'reference' => $reference,
                        'authorizationCode' => $authorizationCode,
                    ],
                ];
            }

            public function singleTransferStatus(string $reference): array
            {
                return [
                    'status' => 'pending',
                    'provider_status' => 'PENDING_AUTHORIZATION',
                    'api_response' => [
                        'requestSuccessful' => true,
                        'responseCode' => '0',
                        'responseMessage' => 'success',
                        'responseBody' => [
                            'reference' => $reference,
                            'status' => 'PENDING_AUTHORIZATION',
                        ],
                    ],
                    'request_data' => [
                        'reference' => $reference,
                    ],
                ];
            }
        };

        $this->app->instance(MonnifyController::class, $fakeController);
        $this->actingAs($user);

        $request = Request::create('/', 'POST', [
            'action' => 'authorize_monnify',
            'authorization_code' => '5555555555',
        ]);

        $response = app(TransactionController::class)->resolvePendingTransactionAction($request, $transaction);

        $this->assertTrue(method_exists($response, 'getSession'));
        $this->assertSame('authorizationCode must be 6 digits', $response->getSession()->get('error'));
        $this->assertDatabaseHas('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('transaction_logs', [
            'id' => $transaction->id,
            'status' => 'success',
        ]);
    }

    public function test_monnify_wallet_to_bank_requery_uses_single_transfer_status_endpoint(): void
    {
        $provider = API::create([
            'name' => 'Monnify',
            'slug' => 'monnify',
            'status' => 'active',
            'live_base_url' => 'https://api.monnify.com',
            'sandbox_base_url' => 'https://sandbox.monnify.com',
            'account_number' => '1234567890',
            'secret_key' => 'monnify-secret',
            'api_key' => 'monnify-api-key',
            'is_bank_transfer' => true,
        ]);

        $customerUser = User::factory()->create([
            'firstname' => 'Requery',
            'lastname' => 'Tester',
            'email' => 'requery.tester@example.com',
            'phone' => '08040000000',
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
        ]);

        $controller = new class extends MonnifyController {
            public string $capturedUrl = '';

            public function login()
            {
                return 'monnify-token';
            }

            public function basicApiCall($url, $payload, $headers, $method = 'POST')
            {
                $this->capturedUrl = (string) $url;

                return [
                    'requestSuccessful' => true,
                    'responseCode' => '0',
                    'responseMessage' => 'success',
                    'responseBody' => [
                        'reference' => 'W2B-MONNIFY-REQUERY-001',
                        'status' => 'SUCCESS',
                    ],
                ];
            }
        };

        $transaction = TransactionLog::create([
            'status' => 'pending',
            'reference_id' => 'REF-MONNIFY-REQUERY-001',
            'transaction_id' => 'W2B-MONNIFY-REQUERY-001',
            'payment_method' => 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $customerUser->email,
            'customer_name' => $customerUser->name,
            'customer_phone' => $customerUser->phone,
            'unique_element' => 'wallet2bank',
            'discount' => 0,
            'unit_price' => 500,
            'amount' => 500,
            'total_amount' => 605,
            'balance_before' => 5000,
            'balance_after' => 4395,
            'quantity' => 1,
            'request_data' => json_encode([
                'reference' => 'W2B-MONNIFY-REQUERY-001',
            ]),
            'api_response' => json_encode([
                'responseBody' => [
                    'reference' => 'W2B-MONNIFY-REQUERY-001',
                    'status' => 'PENDING_AUTHORIZATION',
                ],
            ]),
            'ip_address' => '127.0.0.1',
            'domain_name' => 'localhost',
            'api_id' => $provider->id,
            'descr' => 'Wallet to Bank Transfer initiated.',
            'provider_charge' => 105,
        ]);

        $response = $controller->requery($transaction);

        $this->assertTrue($response['status']);
        $this->assertStringEndsWith('/api/v2/disbursements/single/summary?reference=W2B-MONNIFY-REQUERY-001', $controller->capturedUrl);
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
