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
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'completed_at')) {
            return;
        }

        DB::table('transaction_logs')
            ->whereNull('completed_at')
            ->whereIn('status', ['success', 'successful', 'completed', 'approved', 'delivered'])
            ->update([
                'completed_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'completed_at')) {
            return;
        }

        DB::table('transaction_logs')
            ->whereIn('status', ['success', 'successful', 'completed', 'approved', 'delivered'])
            ->whereNotNull('completed_at')
            ->update([
                'completed_at' => null,
            ]);
    }
};
