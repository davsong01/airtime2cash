<?php

namespace Tests\Feature;

use App\Http\Controllers\APIController;
use App\Http\Controllers\Providers\AirtimeToCashAutomationController;
use App\Models\API;
use App\Models\Airtime2CashTransactions;
use App\Models\Product;
use App\Services\AirtimeToCashAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AirtimeToCashAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_names_are_mapped_to_provider_network_codes(): void
    {
        $service = app(AirtimeToCashAutomationService::class);

        $this->assertSame('MTN', $service->networkNameForProduct('MTN Nigeria'));
        $this->assertSame('GLO', $service->networkNameForProduct('Globacom'));
        $this->assertSame('AIRTEL', $service->networkNameForProduct('Airtel Nigeria'));
        $this->assertSame('9MOBILE', $service->networkNameForProduct('9Mobile'));
        $this->assertSame('9MOBILE', $service->networkNameForProduct('Etisalat'));
    }

    public function test_customer_can_generate_verify_and_transfer_airtime(): void
    {
        [$provider, $transaction] = $this->makeTransaction('MTN Nigeria');

        Http::fake(function (HttpRequest $request) {
            return match (true) {
                str_ends_with($request->url(), '/api/v1/check/quota/availability') => Http::response([
                    'code' => 5030,
                    'message' => 'Recipient(s) Available',
                ], 200),
                str_ends_with($request->url(), '/api/v1/generate/otp') => Http::response([
                    'code' => 2000,
                    'message' => 'Otp sent successfully',
                ], 200),
                str_ends_with($request->url(), '/api/v1/verify/otp') => Http::response([
                    'code' => 2000,
                    'message' => 'Otp verified.',
                    'data' => ['sessionId' => 'session-123'],
                ], 200),
                str_ends_with($request->url(), '/api/v1/login/with/session/id') => Http::response([
                    'code' => 2000,
                    'message' => 'Session record retrieved.',
                    'data' => ['sessionId' => 'session-123'],
                ], 200),
                str_ends_with($request->url(), '/api/v1/transfer/airtime') => Http::response([
                    'code' => 2000,
                    'message' => 'Airtime transferred successfully.',
                    'data' => ['amountConverted' => '₦1,000'],
                ], 200),
                default => Http::response(['code' => 3000, 'message' => 'Unexpected endpoint'], 500),
            };
        });

        $controller = app(AirtimeToCashAutomationController::class);

        $initiated = $controller->initiate($transaction, $provider, true);
        $this->assertTrue($initiated['status']);
        $this->assertSame('otp', $initiated['stage']);

        $verified = $controller->verifyOtp($transaction->fresh(), '123456', $provider);
        $this->assertTrue($verified['status']);
        $this->assertSame('pin', $verified['stage']);
        $this->assertSame('session-123', $transaction->fresh()->provider_session_id);

        $transferred = $controller->transfer($transaction->fresh(), '1234', $provider);
        $this->assertSame('ok', $transferred['status']);
        $this->assertSame('successful', $transferred['provider_status']);

        $this->assertSame('successful', $transaction->fresh()->provider_status);
        $this->assertSame(5, Http::recorded()->count());

        Http::assertSent(function (HttpRequest $request) {
            return str_ends_with($request->url(), '/api/v1/generate/otp')
                && $request->data()['networkName'] === 'MTN'
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });

        Http::assertSent(function (HttpRequest $request) {
            return str_ends_with($request->url(), '/api/v1/transfer/airtime')
                && $request->data()['reference'] === 'A2C-TEST-001'
                && $request->data()['sessionId'] === 'session-123'
                && $request->data()['pin'] === '1234'
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });

        if (Schema::hasTable('api_request_logs')) {
            $loginLog = \App\Models\ApiRequestLog::query()
                ->where('api_id', $provider->id)
                ->where('operation', 'login_with_session_id')
                ->latest('id')
                ->first();

            $this->assertSame('[REDACTED]', data_get($loginLog?->request_payload, 'sessionId'));
        }
    }

    public function test_invalid_otp_does_not_create_a_provider_session(): void
    {
        [$provider, $transaction] = $this->makeTransaction('Airtel Nigeria');

        Http::fake([
            '*api/v1/verify/otp*' => Http::response([
                'code' => 3000,
                'message' => 'Wrong phone number or verification code.',
            ], 200),
        ]);

        $response = app(AirtimeToCashAutomationController::class)->verifyOtp(
            $transaction,
            '000000',
            $provider,
        );

        $this->assertFalse($response['status']);
        $this->assertTrue($response['otp_invalid']);
        $this->assertNull($transaction->fresh()->provider_session_id);
        $this->assertSame('otp_pending', $transaction->fresh()->provider_status);
    }

    public function test_pending_transfer_is_not_reported_as_successful(): void
    {
        [$provider, $transaction] = $this->makeTransaction('Glo Nigeria');
        $transaction->update(['provider_session_id' => 'session-pending']);

        Http::fake([
            '*api/v1/transfer/airtime*' => Http::response([
                'code' => 4000,
                'message' => 'Your request is being processed...!',
            ], 200),
        ]);

        $response = app(AirtimeToCashAutomationController::class)->transfer(
            $transaction->fresh(),
            '1234',
            $provider,
        );

        $this->assertSame('pending', $response['provider_status']);
        $this->assertSame('pending', $transaction->fresh()->provider_status);
    }

    public function test_failed_session_login_does_not_advance_to_pin_stage(): void
    {
        [$provider, $transaction] = $this->makeTransaction('MTN Nigeria');

        Http::fake(function (HttpRequest $request) {
            if (str_ends_with($request->url(), '/api/v1/verify/otp')) {
                return Http::response([
                    'code' => 2000,
                    'message' => 'Otp verified.',
                    'data' => ['sessionId' => 'session-login-fails'],
                ], 200);
            }

            if (str_ends_with($request->url(), '/api/v1/login/with/session/id')) {
                return Http::response([
                    'code' => 2000,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            return Http::response(['code' => 3000, 'message' => 'Unexpected endpoint'], 500);
        });

        $response = app(AirtimeToCashAutomationController::class)->verifyOtp(
            $transaction,
            '123456',
            $provider,
        );

        $this->assertFalse($response['status']);
        $this->assertSame('otp', $response['stage']);
        $this->assertFalse($response['otp_invalid']);
        $this->assertNull($transaction->fresh()->provider_session_id);
        $this->assertSame('otp_pending', $transaction->fresh()->provider_status);
    }

    public function test_admin_can_check_recipient_availability(): void
    {
        [$provider] = $this->makeTransaction('MTN Nigeria');

        Http::fake([
            '*api/v1/check/quota/availability*' => Http::response([
                'code' => 5030,
                'message' => 'Recipient(s) Available',
            ], 200),
        ]);

        $request = Request::create(
            route('api.airtimetocash.availability.check', $provider),
            'POST',
            [
                'network' => 'MTN',
                'amount' => 1000,
            ],
        );

        $response = app(APIController::class)->checkAirtimeToCashAvailability(
            $request,
            $provider,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(5030, $response->getData(true)['code']);
        $this->assertSame('Recipient(s) Available', $response->getData(true)['message']);

        Http::assertSent(function (HttpRequest $request) {
            return str_ends_with($request->url(), '/api/v1/check/quota/availability')
                && $request->data()['networkName'] === 'MTN'
                && $request->data()['amount'] === 1000
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    private function makeTransaction(string $productName): array
    {
        $provider = API::create([
            'name' => 'Airtime to Cash Automation',
            'slug' => 'airtimetocash',
            'status' => 'active',
            'api_key' => 'test-api-key',
            'sandbox_base_url' => 'https://automation.airtimetocash.com',
            'live_base_url' => 'https://automation.airtimetocash.com',
            'is_auto_share' => true,
        ]);

        $product = Product::create([
            'category_id' => 1,
            'name' => $productName,
            'slug' => strtolower(str_replace(' ', '-', $productName)).'-'.uniqid(),
            'type' => 'airtime2cash',
            'api_id' => (string) $provider->id,
            'status' => 'active',
        ]);

        $transaction = Airtime2CashTransactions::create([
            'product_id' => $product->id,
            'customer_id' => 1,
            'transaction_id' => 'A2C-TEST-001',
            'phone_numbers' => '08012345678',
            'total_amount' => 1000,
            'amount_paid' => 900,
            'amount_charged' => 100,
            'status' => 'pending',
            'transfer_mode' => 'auto_share',
        ]);

        return [$provider, $transaction];
    }
}
