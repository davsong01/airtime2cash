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
        if (! Schema::hasTable('airtime2_cash_transactions') || ! Schema::hasColumn('airtime2_cash_transactions', 'completed_at')) {
            return;
        }

        DB::table('airtime2_cash_transactions')
            ->whereNull('completed_at')
            ->whereIn('status', ['success', 'successful', 'completed', 'approved', 'failed', 'declined'])
            ->update([
                'completed_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions') || ! Schema::hasColumn('airtime2_cash_transactions', 'completed_at')) {
            return;
        }

        DB::table('airtime2_cash_transactions')
            ->whereIn('status', ['success', 'successful', 'completed', 'approved', 'failed', 'declined'])
            ->whereNotNull('completed_at')
            ->update([
                'completed_at' => null,
            ]);
    }
};
