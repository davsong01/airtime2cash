<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis') || ! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        $autosyncId = DB::table('apis')->where('slug', 'autosync')->value('id');

        if (! $autosyncId || ! Schema::hasColumn('airtime2_cash_transactions', 'provider_id')) {
            return;
        }
        
        $query = DB::table('airtime2_cash_transactions')
            ->where(function ($query): void {
                if (Schema::hasColumn('airtime2_cash_transactions', 'transfer_mode')) {
                    $query->where('transfer_mode', 'auto_share');
                }

                $query->orWhereNotNull('provider_reference')
                    ->orWhereNotNull('provider_request_ref');
            })
            ->orderBy('id');

        $query->chunkById(200, function ($transactions) use ($autosyncId): void {
            foreach ($transactions as $transaction) {
                DB::table('airtime2_cash_transactions')
                    ->where('id', $transaction->id)
                    ->update(['provider_id' => $autosyncId]);

                if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'api_id')) {
                    DB::table('transaction_logs')
                        ->where('transaction_id', $transaction->transaction_id)
                        ->update(['api_id' => $autosyncId]);
                }
            }
        });
    }

    public function down(): void
    {
        // Provider ownership is historical data and should not be nulled on
        // rollback because that would destroy the corrected audit trail.
    }
};
