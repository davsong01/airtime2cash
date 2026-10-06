<?php

namespace App\Services;

use App\Models\API;
use RuntimeException;

class AutoShareRoutingService
{
    public function selectProvider(float $amount): array
    {
        $settings = getSettings();
        $configuredProviderId = $settings?->auto_share_provider_id;
        $routingMode = $settings?->auto_share_routing_mode ?? 'manual';

        if ($routingMode !== 'auto') {
            $provider = $configuredProviderId ? API::query()->find($configuredProviderId) : null;

            if (! $provider) {
                throw new RuntimeException('The configured Auto Share provider is not available.');
            }

            return $this->decision(
                $provider,
                'Manual routing: administrator-configured Auto Share provider.',
                null,
                $routingMode
            );
        }

        $candidates = API::query()
            ->where('status', 'active')
            ->where('is_auto_share', true)
            ->orderBy('name')
            ->get()
            ->map(fn (API $provider) => $this->evaluateCandidate($provider, $amount))
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
                'auto'
            );
        }

        // Initial policy: lowest effective fee wins; availability is the
        // deterministic tie-breaker. More routing factors can be added here
        // without changing transaction creation.
        $selected = $candidates->sort(function (array $left, array $right): int {
            $feeComparison = $left['fee'] <=> $right['fee'];

            return $feeComparison !== 0
                ? $feeComparison
                : ($right['availability_score'] <=> $left['availability_score']);
        })->first();

        $candidateSummary = $candidates->map(fn (array $candidate) => [
            'provider_id' => $candidate['provider']->id,
            'provider' => $candidate['provider']->name,
            'fee' => $candidate['fee'],
            'availability_score' => $candidate['availability_score'],
            'availability_status' => $candidate['availability_status'],
            'band' => $candidate['band_name'],
        ])->values()->all();

        $reason = sprintf(
            'Auto routing: %s selected with effective fee %s and availability score %d%%.',
            $selected['provider']->name,
            $this->formatAmount($selected['fee']),
            $selected['availability_score']
        );

        return $this->decision($selected['provider'], $reason, [
            'fallback' => false,
            'amount' => $amount,
            'selected_fee' => $selected['fee'],
            'selected_band' => $selected['band_name'],
            'selected_availability_score' => $selected['availability_score'],
            'selected_availability_status' => $selected['availability_status'],
            'candidates' => $candidateSummary,
        ], 'auto');
    }

    private function evaluateCandidate(API $provider, float $amount): ?array
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

        return [
            'provider' => $provider,
            'fee' => $this->calculateFee($provider, $band),
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

    private function calculateFee(API $provider, array $band): float
    {
        $fee = (float) ($band['provider_fee'] ?? 0)
            + (float) ($band['extra_charge'] ?? 0);

        foreach (($band['extra_charges'] ?? $band['charges'] ?? []) as $charge) {
            if (is_array($charge)) {
                $fee += (float) ($charge['value'] ?? 0);
            }
        }

        foreach (($provider->extra_charges ?? []) as $charge) {
            if (is_array($charge)) {
                $fee += (float) ($charge['value'] ?? 0);
            }
        }

        return round($fee, 2);
    }

    private function decision(API $provider, string $reason, ?array $meta, string $mode): array
    {
        return [
            'provider' => $provider,
            'provider_id' => $provider->id,
            'reason' => $reason,
            'mode' => $mode,
            'meta' => $meta ?? [
                'fallback' => false,
                'provider_id' => $provider->id,
                'provider' => $provider->name,
            ],
        ];
    }

    private function formatAmount(float $amount): string
    {
        return (string) (getSettings()?->currency ?? '₦') . number_format($amount, 2);
    }
}
