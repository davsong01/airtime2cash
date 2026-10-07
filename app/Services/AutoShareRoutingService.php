<?php

namespace App\Services;

use App\Models\API;
use RuntimeException;

class AutoShareRoutingService
{
    public function selectProvider(float $amount, ?string $network = null, bool $forceAuto = false, float $conversionCharge = 0): array
    {
        $settings = getSettings();
        $configuredProviderId = $settings?->auto_share_provider_id;
        $routingMode = $forceAuto ? 'auto' : ($settings?->auto_share_routing_mode ?? 'manual');

        if ($routingMode !== 'auto') {
            $provider = $configuredProviderId ? API::query()->find($configuredProviderId) : null;

            if (! $provider) {
                throw new RuntimeException('The configured Auto Share provider is not available.');
            }

            $candidate = $this->evaluateCandidate($provider, $amount, $conversionCharge);
            $meta = [
                'fallback' => false,
                'amount' => $amount,
                'candidates' => [],
            ];

            if ($candidate) {
                $meta['selected_fee'] = $candidate['fee'];
                $meta['conversion_charge'] = $candidate['conversion_charge'];
                $meta['selected_total_customer_charge'] = $candidate['effective_total_charge'];
                $meta['selected_band'] = $candidate['band_name'];
                $meta['selected_charges'] = $candidate['charges'];
                $meta['candidates'] = [[
                    'provider_id' => $provider->id,
                    'provider' => $provider->name,
                    'fee' => $candidate['fee'],
                    'conversion_charge' => $candidate['conversion_charge'],
                    'effective_total_charge' => $candidate['effective_total_charge'],
                    'charges' => $candidate['charges'],
                    'availability_score' => $candidate['availability_score'],
                    'availability_status' => $candidate['availability_status'],
                    'band' => $candidate['band_name'],
                ]];
            }

            return $this->decision(
                $provider,
                'Manual routing: administrator-configured Auto Share provider.',
                $meta,
                $routingMode,
                $network
            );
        }

        $candidates = API::query()
            ->where('status', 'active')
            ->where('is_auto_share', true)
            ->orderBy('name')
            ->get()
            ->map(fn (API $provider) => $this->evaluateCandidate($provider, $amount, $conversionCharge))
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            $fallback = $configuredProviderId
                ? API::query()
                    ->whereKey($configuredProviderId)
                    ->where('status', 'active')
                    ->where('is_auto_share', true)
                    ->first()
                : null;

            if (! $fallback) {
                throw new RuntimeException('No active Auto Share provider has a pricing band for this amount, and the configured fallback is not active.');
            }

            return $this->decision(
                $fallback,
                'Auto routing fallback: no eligible provider had a matching pricing band; configured provider used.',
                [
                    'fallback' => true,
                    'amount' => $amount,
                    'candidates' => [],
                ],
                'auto',
                $network
            );
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
            'Auto routing: %s selected with total customer charge %s and availability score %d%%.',
            $selected['provider']->name,
            $this->formatAmount($selected['effective_total_charge']),
            $selected['availability_score']
        );

        return $this->decision($selected['provider'], $reason, [
            'fallback' => false,
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
