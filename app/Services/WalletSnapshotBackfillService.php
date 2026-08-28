<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletSnapshotBackfillService
{
    public function backfill(): array
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasTable('customers')) {
            return [
                'customers_processed' => 0,
                'wallet_rows_updated' => 0,
                'transaction_logs_updated' => 0,
            ];
        }

        $customerIds = DB::table('wallets')
            ->select('customer_id')
            ->distinct()
            ->orderBy('customer_id')
            ->pluck('customer_id');

        $stats = [
            'customers_processed' => 0,
            'wallet_rows_updated' => 0,
            'transaction_logs_updated' => 0,
        ];

        foreach ($customerIds as $customerId) {
            $customerBalance = $this->floatOrNull(DB::table('customers')->where('id', $customerId)->value('wallet'));

            if ($customerBalance === null) {
                continue;
            }

            $rows = DB::table('wallets')
                ->where('customer_id', $customerId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $stats['customers_processed']++;

            $transactionLogs = DB::table('transaction_logs')
                ->whereIn('transaction_id', $rows->pluck('transaction_id')->filter()->unique()->all())
                ->get()
                ->keyBy('transaction_id');

            $runningAfter = $customerBalance;

            for ($index = $rows->count() - 1; $index >= 0; $index--) {
                $row = $rows[$index];
                $type = strtolower((string) ($row->type ?? ''));
                $amount = $this->floatOrNull($row->amount);
                $before = $this->floatOrNull($row->balance_before);
                $after = $this->floatOrNull($row->balance_after);
                $transactionLog = $row->transaction_id ? $transactionLogs->get($row->transaction_id) : null;

                $logBefore = $transactionLog ? $this->floatOrNull($transactionLog->balance_before) : null;
                $logAfter = $transactionLog ? $this->floatOrNull($transactionLog->balance_after) : null;

                if ($after === null) {
                    $after = $logAfter ?? $runningAfter;
                }

                if ($before === null) {
                    if ($logBefore !== null) {
                        $before = $logBefore;
                    } elseif ($after !== null && $amount !== null) {
                        $before = $this->beforeFromAfter($type, $after, $amount);
                    }
                }

                if ($after === null && $before !== null && $amount !== null) {
                    $after = $this->afterFromBefore($type, $before, $amount);
                }

                if ($before === null && $after !== null && $amount !== null) {
                    $before = $this->beforeFromAfter($type, $after, $amount);
                }

                $walletUpdates = [];
                if ($row->balance_before === null && $before !== null) {
                    $walletUpdates['balance_before'] = $before;
                }
                if ($row->balance_after === null && $after !== null) {
                    $walletUpdates['balance_after'] = $after;
                }

                if (! empty($walletUpdates)) {
                    DB::table('wallets')->where('id', $row->id)->update($walletUpdates);
                    $stats['wallet_rows_updated']++;
                }

                if ($transactionLog) {
                    $logUpdates = [];

                    if ($transactionLog->balance_before === null && $before !== null) {
                        $logUpdates['balance_before'] = $before;
                    }

                    if ($transactionLog->balance_after === null && $after !== null) {
                        $logUpdates['balance_after'] = $after;
                    }

                    if (! empty($logUpdates)) {
                        DB::table('transaction_logs')->where('id', $transactionLog->id)->update($logUpdates);
                        $stats['transaction_logs_updated']++;
                    }
                }

                if ($before !== null) {
                    $runningAfter = $before;
                } elseif ($after !== null) {
                    $runningAfter = $after;
                }
            }
        }

        return $stats;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function afterFromBefore(string $type, float $before, float $amount): float
    {
        return $type === 'debit' ? $before - $amount : $before + $amount;
    }

    private function beforeFromAfter(string $type, float $after, float $amount): float
    {
        return $type === 'debit' ? $after + $amount : $after - $amount;
    }
}
