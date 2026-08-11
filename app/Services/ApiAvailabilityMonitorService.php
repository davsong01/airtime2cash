<?php

namespace App\Services;

use App\Models\API;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiAvailabilityMonitorService
{
    public function run(int $windowMinutes = 60, int $sampleSize = 20): array
    {
        $windowMinutes = max(1, $windowMinutes);
        $sampleSize = max(1, $sampleSize);
        $checkedAt = now();

        $results = [];

        API::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->each(function (API $api) use (&$results, $windowMinutes, $sampleSize, $checkedAt) {
                $results[] = $this->evaluateProvider($api, $windowMinutes, $sampleSize, $checkedAt);
            });

        return [
            'status' => 'success',
            'checked_at' => $checkedAt->toDateTimeString(),
            'window_minutes' => $windowMinutes,
            'sample_size' => $sampleSize,
            'providers_checked' => count($results),
            'providers' => $results,
        ];
    }

    private function evaluateProvider(API $api, int $windowMinutes, int $sampleSize, $checkedAt): array
    {
        $recent = $this->sampleTransactions($api, true, $windowMinutes, $sampleSize);
        $usedFallback = false;

        if ($recent->isEmpty()) {
            $sample = $this->sampleTransactions($api, false, $windowMinutes, $sampleSize);
            $usedFallback = true;
        } else {
            $sample = $recent->take($sampleSize);
        }

        if ($sample->isEmpty()) {
            $api->forceFill([
                'availability_check_transactions_count' => 0,
                'successful_transactions' => 0,
                'failed_transactions' => 0,
                'availability_checked_at' => $checkedAt,
            ])->save();

            return [
                'api_id' => $api->id,
                'name' => $api->name,
                'slug' => $api->slug,
                'availability_score' => $api->availability_score,
                'availability_status' => $api->availability_status,
                'availability_check_transactions_count' => 0,
                'successful_transactions' => 0,
                'failed_transactions' => 0,
                'availability_checked_at' => $checkedAt->toDateTimeString(),
                'sample_source' => null,
                'note' => 'No transactions were found in the selected window or fallback sample. Previous score was preserved.',
            ];
        }

        $successStatuses = $this->successfulStatuses();
        $successful = $sample->filter(fn ($row) => in_array(Str::lower((string) $row->status), $successStatuses, true))->count();
        $considered = $sample->count();
        $failed = max(0, $considered - $successful);
        $score = $considered > 0
            ? (int) round(($successful / $considered) * 100)
            : 0;
        $statusBand = $this->scoreBand($score);

        $api->forceFill([
            'availability_score' => $score,
            'availability_status' => $statusBand,
            'availability_check_transactions_count' => $considered,
            'successful_transactions' => $successful,
            'failed_transactions' => $failed,
            'availability_checked_at' => $checkedAt,
        ])->save();

        return [
            'api_id' => $api->id,
            'name' => $api->name,
            'slug' => $api->slug,
            'availability_score' => $score,
            'availability_status' => $statusBand,
            'availability_check_transactions_count' => $considered,
            'successful_transactions' => $successful,
            'failed_transactions' => $failed,
            'availability_checked_at' => $checkedAt->toDateTimeString(),
            'sample_source' => $usedFallback ? 'fallback_transactions' : 'recent_transactions',
            'considered_transactions' => $considered,
        ];
    }

    private function sampleTransactions(API $api, bool $useWindow, int $windowMinutes, int $limit): Collection
    {
        $cutoff = $useWindow ? now()->subMinutes($windowMinutes) : null;

        $transactionLogs = DB::table('transaction_logs')
            ->selectRaw("'transaction_logs' as source")
            ->selectRaw('id')
            ->selectRaw('transaction_id')
            ->selectRaw('status')
            ->selectRaw('COALESCE(completed_at, created_at) as activity_at')
            ->where('api_id', $api->id);

        $airtimeTransactions = DB::table('airtime2_cash_transactions')
            ->selectRaw("'airtime2cash' as source")
            ->selectRaw('id')
            ->selectRaw('transaction_id')
            ->selectRaw('status')
            ->selectRaw('COALESCE(completed_at, created_at) as activity_at')
            ->where('provider_id', $api->id);

        if ($cutoff) {
            $transactionLogs->whereRaw('COALESCE(completed_at, updated_at, created_at) >= ?', [$cutoff]);
            $airtimeTransactions->whereRaw('COALESCE(completed_at, updated_at, created_at) >= ?', [$cutoff]);
        }

        $union = $transactionLogs->unionAll($airtimeTransactions);

        return DB::query()
            ->fromSub($union, 'provider_activity')
            ->orderByDesc('activity_at')
            ->limit($limit)
            ->get();
    }

    private function scoreBand(int $score): string
    {
        return match (true) {
            $score <= 25 => 'unstable',
            $score <= 50 => 'degraded',
            $score <= 75 => 'stable',
            default => 'healthy',
        };
    }

    private function successfulStatuses(): array
    {
        return ['success', 'successful', 'completed', 'approved', 'delivered'];
    }
}
