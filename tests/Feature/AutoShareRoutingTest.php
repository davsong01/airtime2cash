<?php

namespace Tests\Feature;

use App\Models\API;
use App\Services\AutoShareRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutoShareRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_mode_uses_the_configured_provider_without_comparing_candidates(): void
    {
        $configured = $this->provider('Configured Provider', 1, 10, 10);
        $other = $this->provider('Cheaper Provider', 1, 1, 99);
        $this->configure($configured, 'manual');

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000);

        $this->assertSame($configured->id, $decision['provider_id']);
        $this->assertStringContainsString('Manual routing', $decision['reason']);
        $this->assertNotSame($other->id, $decision['provider_id']);
    }

    public function test_auto_mode_selects_lowest_effective_fee_and_uses_health_as_tie_breaker(): void
    {
        $this->provider('Expensive Healthy Provider', 20, 0, 98);
        $this->provider('Cheap Less Healthy Provider', 10, 0, 55);
        $selected = $this->provider('Cheap Healthy Provider', 8, 2, 91, [
            ['charge_name' => 'Stamp', 'value' => 1],
        ]);
        $configured = $this->provider('Configured Fallback', 100, 0, 1);
        $this->configure($configured, 'auto');

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000);

        // Cheap Healthy Provider: provider fee 8 + band charge 2 + stamp 1
        // = 11, beating the other candidate at 10? No: Cheap Less Healthy
        // Provider is 10, so this asserts fee remains the primary rule.
        $this->assertSame('Cheap Less Healthy Provider', $decision['provider']->name);

        // Equalize the competing fee and confirm health becomes the tie-breaker.
        $selected->update(['pricing_data' => [[
            'band_name' => 'Default',
            'min_amount' => 200,
            'max_amount' => 5000,
            'provider_fee' => 8,
            'extra_charge' => 1,
        ]]]);
        API::where('name', 'Cheap Less Healthy Provider')->update(['pricing_data' => [[
            'band_name' => 'Default',
            'min_amount' => 200,
            'max_amount' => 5000,
            'provider_fee' => 8,
            'extra_charge' => 1,
        ]]]);

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000);

        $this->assertSame('Cheap Healthy Provider', $decision['provider']->name);
        $this->assertSame(9.0, $decision['meta']['selected_fee']);
        $this->assertSame(91, $decision['meta']['selected_availability_score']);
        $this->assertCount(4, $decision['meta']['candidates']);
    }

    public function test_auto_mode_does_not_fall_back_to_an_inactive_configured_provider(): void
    {
        $configured = $this->provider('Inactive Configured Provider', 10, 0, 90);
        $configured->update(['status' => 'inactive']);
        $this->configure($configured, 'auto');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('configured fallback is not active');

        app(AutoShareRoutingService::class)->selectProvider(1000);
    }

    public function test_diagnostic_can_force_auto_routing_without_changing_live_mode(): void
    {
        $configured = $this->provider('Configured Provider', 5, 0, 40);
        $selected = $this->provider('Diagnostic Winner', 2, 0, 80);
        $this->configure($configured, 'manual');

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000, 'mtn', true);

        $this->assertSame($selected->id, $decision['provider_id']);
        $this->assertSame('auto', $decision['mode']);
        $this->assertSame('mtn', $decision['meta']['network']);
        $this->assertSame('manual', DB::table('settings')->value('auto_share_routing_mode'));
    }

    private function configure(API $provider, string $mode): void
    {
        DB::table('settings')->insert([
            'currency' => '₦',
            'auto_share_provider_id' => $provider->id,
            'auto_share_routing_mode' => $mode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function provider(
        string $name,
        float $providerFee,
        float $extraCharge,
        int $availabilityScore,
        array $bandExtras = []
    ): API {
        return API::create([
            'name' => $name,
            'slug' => str($name)->slug('-'),
            'status' => 'active',
            'is_auto_share' => true,
            'pricing_data_status' => true,
            'availability_score' => $availabilityScore,
            'availability_status' => $availabilityScore >= 80 ? 'healthy' : 'average',
            'pricing_data' => [[
                'band_name' => 'Default',
                'min_amount' => 200,
                'max_amount' => 5000,
                'provider_fee' => $providerFee,
                'extra_charge' => $extraCharge,
                'extra_charges' => $bandExtras,
            ]],
            'extra_charges' => [],
        ]);
    }
}
