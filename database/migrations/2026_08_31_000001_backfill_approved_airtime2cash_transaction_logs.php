<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions') || ! Schema::hasTable('transaction_logs')) {
            return;
        }

        $transactionIds = DB::table('transaction_logs as tl')
            ->join('airtime2_cash_transactions as a2c', 'a2c.transaction_id', '=', 'tl.transaction_id')
            ->where('a2c.status', 'approved')
            ->where('a2c.transfer_mode', 'manual')
            ->where('tl.status', 'pending')
            ->distinct()
            ->pluck('tl.transaction_id');

        if ($transactionIds->isEmpty()) {
            return;
        }

        $updates = [
            'status' => 'success',
        ];

        if (Schema::hasColumn('transaction_logs', 'provider_status')) {
            $updates['provider_status'] = 'successful';
        }

        if (Schema::hasColumn('transaction_logs', 'completed_at')) {
            $updates['completed_at'] = DB::raw('COALESCE(completed_at, CURRENT_TIMESTAMP)');
        }

        DB::table('transaction_logs')
            ->whereIn('transaction_id', $transactionIds->all())
            ->update($updates);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
