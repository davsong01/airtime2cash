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

        $approvedTransactionIds = DB::table('airtime2_cash_transactions')
            ->where('status', 'approved')
            ->pluck('transaction_id')
            ->filter()
            ->unique()
            ->values();

        if ($approvedTransactionIds->isEmpty()) {
            return;
        }

        DB::table('transaction_logs')
            ->whereIn('transaction_id', $approvedTransactionIds->all())
            ->update([
                'status' => 'success',
                'provider_status' => 'successful',
                'completed_at' => DB::raw('COALESCE(completed_at, updated_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
