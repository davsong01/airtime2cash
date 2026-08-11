<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'api_id')) {
            return;
        }

        DB::table('transaction_logs')
            ->where('unique_element', 'WALLET-FUNDING')
            ->whereNull('api_id')
            ->update([
                'api_id' => 1,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'api_id')) {
            return;
        }

        DB::table('transaction_logs')
            ->where('unique_element', 'WALLET-FUNDING')
            ->whereNotNull('api_id')
            ->update([
                'api_id' => null,
            ]);
    }
};
