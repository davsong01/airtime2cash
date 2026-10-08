<?php

namespace Tests\Feature;

use App\Models\API;
use App\Models\Category;
use App\Models\Product;
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
        $product = $this->product([$configured]);
        $this->configure($configured, 'manual');

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000, product: $product);

        $this->assertSame($configured->id, $decision['provider_id']);
        $this->assertTrue($decision['meta']['product_mapping']);
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
        $product = $this->product([
            $this->provider('Expensive Healthy Provider', 20, 0, 98),
            $this->provider('Cheap Less Healthy Provider', 10, 0, 55),
            $selected,
            $configured,
        ]);
        $this->configure($configured, 'auto');

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000, product: $product);

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

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000, product: $product);

        $this->assertSame('Cheap Healthy Provider', $decision['provider']->name);
        $this->assertSame(9.0, $decision['meta']['selected_fee']);
        $this->assertSame(91, $decision['meta']['selected_availability_score']);
        $this->assertSame(9.0, collect($decision['meta']['selected_charges'])->sum('amount'));
        $this->assertCount(4, $decision['meta']['candidates']);
    }

    public function test_inactive_mapped_provider_is_not_used(): void
    {
        $configured = $this->provider('Inactive Configured Provider', 10, 0, 90);
        $configured->update(['status' => 'inactive']);
        $product = $this->product([$configured]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active Auto Share provider is mapped');

        app(AutoShareRoutingService::class)->selectProvider(1000, product: $product);
    }

    public function test_diagnostic_can_force_auto_routing_without_changing_live_mode(): void
    {
        $configured = $this->provider('Configured Provider', 5, 0, 40);
        $selected = $this->provider('Diagnostic Winner', 2, 0, 80);
        $product = $this->product([$configured, $selected]);

        $decision = app(AutoShareRoutingService::class)->selectProvider(1000, 'mtn', true, 100, $product);

        $this->assertSame($selected->id, $decision['provider_id']);
        $this->assertSame('auto', $decision['mode']);
        $this->assertSame('mtn', $decision['meta']['network']);
        $this->assertSame(102.0, $decision['meta']['selected_total_customer_charge']);
        $this->assertSame(100.0, $decision['meta']['conversion_charge']);
    }

    public function test_product_mapping_takes_priority_over_global_provider_routing(): void
    {
        $global = $this->provider('Global Provider', 1, 0, 90);
        $mapped = $this->provider('Product Provider', 10, 0, 90);
        $this->configure($mapped, 'manual');
        $category = Category::create([
            'name' => 'Airtime to Cash',
            'slug' => 'airtime-to-cash',
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'MTN Airtime to Cash',
            'slug' => 'mtn-airtime-to-cash',
            'category_id' => $category->id,
            'type' => 'airtime2cash',
            'status' => 'active',
            'api_id' => $global->id,
        ]);
        $product->autoShareProviders()->attach($mapped->id);

        $decision = app(AutoShareRoutingService::class)->selectProvider(
            amount: 1000,
            product: $product,
        );

        $this->assertSame($mapped->id, $decision['provider_id']);
        $this->assertTrue($decision['meta']['product_mapping']);
        $this->assertStringContainsString('Manual routing', $decision['reason']);
    }

    public function test_glo_never_routes_to_an_unmapped_airtimetocash_provider(): void
    {
        $autosync = $this->provider('AutoSync', 20, 0, 80);
        $automation = $this->provider('AirtimeToCash Automation', 0, 0, 100);
        $automation->update(['slug' => 'airtimetocash']);
        $glo = $this->product([$autosync]);
        $this->configure($autosync, 'auto');

        $decision = app(AutoShareRoutingService::class)->selectProvider(
            amount: 1000,
            product: $glo,
        );

        $this->assertSame($autosync->id, $decision['provider_id']);
        $this->assertNotSame($automation->id, $decision['provider_id']);
        $this->assertCount(1, $decision['meta']['candidates']);
        $this->assertSame($autosync->id, $decision['meta']['candidates'][0]['provider_id']);
    }

    public function test_provider_without_a_matching_band_is_excluded_before_fee_comparison(): void
    {
        $autosync = $this->provider('AutoSync', 20, 0, 80);
        $automation = $this->provider('AirtimeToCash Automation', 0, 0, 100);
        $automation->update(['slug' => 'airtimetocash', 'pricing_data' => [[
            'band_name' => 'Large amount only',
            'min_amount' => 5000,
            'max_amount' => 10000,
            'provider_fee' => 0,
        ]]]);
        $product = $this->product([$autosync, $automation]);
        $this->configure($autosync, 'auto');

        $decision = app(AutoShareRoutingService::class)->selectProvider(
            amount: 2000,
            product: $product,
        );

        $this->assertSame($autosync->id, $decision['provider_id']);
        $this->assertCount(1, $decision['meta']['candidates']);
    }

    public function test_manual_mode_uses_the_mapped_provider_without_a_matching_band(): void
    {
        $configured = $this->provider('Configured Provider', 20, 0, 80);
        $configured->update(['pricing_data' => [[
            'band_name' => 'Large amount only',
            'min_amount' => 5000,
            'max_amount' => 10000,
            'provider_fee' => 20,
        ]]]);
        $product = $this->product([$configured]);
        $this->configure($configured, 'manual');

        $decision = app(AutoShareRoutingService::class)->selectProvider(
            amount: 2000,
            conversionCharge: 200,
            product: $product,
        );

        $this->assertSame($configured->id, $decision['provider_id']);
        $this->assertSame('manual', $decision['mode']);
        $this->assertSame(0.0, $decision['meta']['selected_fee']);
    }

    private function product(array $providers): Product
    {
        $category = Category::create([
            'name' => 'Airtime to Cash '.uniqid(),
            'slug' => 'airtime-to-cash-'.uniqid(),
            'type' => 'airtime2cash',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Mapped Airtime Product '.uniqid(),
            'slug' => 'mapped-airtime-product-'.uniqid(),
            'category_id' => $category->id,
            'type' => 'airtime2cash',
            'status' => 'active',
            'api_id' => $providers[0]->id,
        ]);

        $product->autoShareProviders()->attach(collect($providers)->pluck('id')->all());

        return $product;
    }

    private function configure(API $provider, string $mode): void
    {
        DB::table('settings')->delete();
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
