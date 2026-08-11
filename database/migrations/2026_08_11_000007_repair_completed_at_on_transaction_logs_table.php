<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run a one-off repair for existing transaction log rows that already
     * reached a terminal state before the completed_at rules were added.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'completed_at')) {
            return;
        }

        DB::table('transaction_logs')
            ->whereNull('completed_at')
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN ('success', 'successful', 'completed', 'approved', 'delivered', 'failed', 'declined', 'rejected', 'cancelled', 'canceled')")
            ->update([
                'completed_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
            ]);
    }

    /**
     * Reverse the repair.
     */
    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'completed_at')) {
            return;
        }

        DB::table('transaction_logs')
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN ('success', 'successful', 'completed', 'approved', 'delivered', 'failed', 'declined', 'rejected', 'cancelled', 'canceled')")
            ->whereNotNull('completed_at')
            ->update([
                'completed_at' => null,
            ]);
    }
};
