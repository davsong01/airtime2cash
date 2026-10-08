<?php

namespace App\Services;

use App\Models\API;
use App\Models\Product;
use RuntimeException;

class AutoShareRoutingService
{
    public function selectProvider(
        float $amount,
        ?string $network = null,
        bool $forceAuto = false,
        float $conversionCharge = 0,
        ?Product $product = null
    ): array
    {
        if (! $product) {
            throw new RuntimeException('An Airtime to Cash product is required to select an Auto Share provider.');
        }

        $settings = getSettings();
        $routingMode = $forceAuto ? 'auto' : ($settings?->auto_share_routing_mode ?? 'manual');

        $providers = $product->autoShareProviders()
            ->where('apis.status', 'active')
            ->where('apis.is_auto_share', true)
            ->orderBy('apis.name')
            ->get();

        if ($providers->isEmpty()) {
            throw new RuntimeException("No active Auto Share provider is mapped to {$product->name}.");
        }

        if ($routingMode === 'manual') {
            $configuredProvider = $providers->firstWhere('id', (int) ($settings?->auto_share_provider_id ?? 0));

            if (! $configuredProvider) {
                throw new RuntimeException("The configured Auto Share provider is not mapped to {$product->name}.");
            }

            // Manual mode deliberately does not require a matching pricing
            // band. The administrator has explicitly selected this mapped
            // provider, so band-based eligibility only applies in Auto mode.
            $candidate = [
                'provider' => $configuredProvider,
                'fee' => 0.0,
                'conversion_charge' => round($conversionCharge, 2),
                'effective_total_charge' => round($conversionCharge, 2),
                'charges' => [],
                'availability_score' => max(0, min(100, (int) ($configuredProvider->availability_score ?? 0))),
                'availability_status' => $configuredProvider->availability_status,
                'band_name' => null,
            ];

            return $this->decision($configuredProvider, 'Manual routing: the configured mapped Auto Share provider was selected.', [
                'fallback' => false,
                'product_mapping' => true,
                'amount' => $amount,
                'selected_fee' => $candidate['fee'],
                'conversion_charge' => $candidate['conversion_charge'],
                'selected_total_customer_charge' => $candidate['effective_total_charge'],
                'selected_band' => $candidate['band_name'],
                'selected_availability_score' => $candidate['availability_score'],
                'selected_availability_status' => $candidate['availability_status'],
                'selected_charges' => $candidate['charges'],
                'candidates' => [[
                    'provider_id' => $configuredProvider->id,
                    'provider' => $configuredProvider->name,
                    'fee' => $candidate['fee'],
                    'conversion_charge' => $candidate['conversion_charge'],
                    'effective_total_charge' => $candidate['effective_total_charge'],
                    'charges' => $candidate['charges'],
                    'availability_score' => $candidate['availability_score'],
                    'availability_status' => $candidate['availability_status'],
                    'band' => $candidate['band_name'],
                ]],
            ], 'manual', $network);
        }

        $candidates = $providers
            ->map(fn (API $provider) => $this->evaluateCandidate($provider, $amount, $conversionCharge))
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            throw new RuntimeException("No mapped Auto Share provider has a pricing band for {$product->name} at this amount.");
        }

        // Initial policy: lowest effective fee wins; availability is the
        // deterministic tie-breaker. More routing factors can be added here
        // without changing transaction creation.
        $selected = $candidates->sort(function (array $left, array $right): int {
            $feeComparison = $left['effective_total_charge'] <=> $right['effective_total_charge'];

            return $feeComparison !== 0
                ? $feeComparison
                : ($right['availability_score'] <=> $left['availability_score']);
        })->first();

        $candidateSummary = $candidates->map(fn (array $candidate) => [
            'provider_id' => $candidate['provider']->id,
            'provider' => $candidate['provider']->name,
            'fee' => $candidate['fee'],
            'conversion_charge' => $candidate['conversion_charge'],
            'effective_total_charge' => $candidate['effective_total_charge'],
            'charges' => $candidate['charges'],
            'availability_score' => $candidate['availability_score'],
            'availability_status' => $candidate['availability_status'],
            'band' => $candidate['band_name'],
        ])->values()->all();

        $reason = sprintf(
            'Product routing: %s selected with total customer charge %s and availability score %d%%.',
            $selected['provider']->name,
            $this->formatAmount($selected['effective_total_charge']),
            $selected['availability_score']
        );

        return $this->decision($selected['provider'], $reason, [
            'fallback' => false,
            'product_mapping' => true,
            'amount' => $amount,
            'selected_fee' => $selected['fee'],
            'conversion_charge' => $selected['conversion_charge'],
            'selected_total_customer_charge' => $selected['effective_total_charge'],
            'selected_band' => $selected['band_name'],
            'selected_availability_score' => $selected['availability_score'],
            'selected_availability_status' => $selected['availability_status'],
            'selected_charges' => $selected['charges'],
            'candidates' => $candidateSummary,
        ], 'auto', $network);
    }

    private function evaluateCandidate(API $provider, float $amount, float $conversionCharge = 0): ?array
    {
        if (! $provider->pricing_data_status) {
            return null;
        }

        $bands = collect($provider->pricing_data ?? [])
            ->filter(fn ($band) => is_array($band))
            ->values();
        $band = $bands->first(fn (array $candidate) => $this->matchesAmount($candidate, $amount));

        if (! $band) {
            return null;
        }

        $availabilityScore = max(0, min(100, (int) ($provider->availability_score ?? 0)));

        $charges = $this->chargeBreakdown($provider, $band);

        return [
            'provider' => $provider,
            'fee' => collect($charges)->sum(fn (array $charge) => (float) ($charge['amount'] ?? 0)),
            'conversion_charge' => round($conversionCharge, 2),
            'effective_total_charge' => round($conversionCharge + collect($charges)->sum(fn (array $charge) => (float) ($charge['amount'] ?? 0)), 2),
            'charges' => $charges,
            'availability_score' => $availabilityScore,
            'availability_status' => $provider->availability_status,
            'band_name' => $band['band_name'] ?? $band['name'] ?? 'Matching band',
        ];
    }

    private function matchesAmount(array $band, float $amount): bool
    {
        $minimum = ($band['min_amount'] ?? '') === '' || $band['min_amount'] === null
            ? null
            : (float) $band['min_amount'];
        $maximum = ($band['max_amount'] ?? '') === '' || $band['max_amount'] === null
            ? null
            : (float) $band['max_amount'];

        return ($minimum === null || $amount >= $minimum)
            && ($maximum === null || $amount <= $maximum);
    }

    private function chargeBreakdown(API $provider, array $band): array
    {
        $charges = [[
            'label' => 'Auto Share Provider Fee',
            'amount' => (float) ($band['provider_fee'] ?? 0),
            'type' => 'provider_fee',
        ]];

        if ((float) ($band['extra_charge'] ?? 0) > 0) {
            $charges[] = [
                'label' => 'Auto Share Our Charge',
                'amount' => (float) $band['extra_charge'],
                'type' => 'our_charge',
            ];
        }

        foreach (($band['extra_charges'] ?? $band['charges'] ?? []) as $charge) {
            if (is_array($charge)) {
                $charges[] = [
                    'label' => $charge['charge_name'] ?? $charge['name'] ?? 'Auto Share Band Extra Charge',
                    'amount' => (float) ($charge['value'] ?? 0),
                    'type' => 'band_extra_charge',
                ];
            }
        }

        foreach (($provider->extra_charges ?? []) as $charge) {
            if (is_array($charge)) {
                $charges[] = [
                    'label' => $charge['charge_name'] ?? $charge['name'] ?? 'Auto Share Global Extra Charge',
                    'amount' => (float) ($charge['value'] ?? 0),
                    'type' => 'global_extra_charge',
                ];
            }
        }

        return $charges;
    }

    private function decision(API $provider, string $reason, ?array $meta, string $mode, ?string $network = null): array
    {
        $meta ??= [
            'fallback' => false,
            'provider_id' => $provider->id,
            'provider' => $provider->name,
        ];

        if ($network !== null) {
            $meta['network'] = $network;
        }

        return [
            'provider' => $provider,
            'provider_id' => $provider->id,
            'reason' => $reason,
            'mode' => $mode,
            'meta' => $meta,
        ];
    }

    private function formatAmount(float $amount): string
    {
        return (string) (getSettings()?->currency ?? '₦') . number_format($amount, 2);
    }
}
