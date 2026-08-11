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

        $monnifyApiId = DB::table('apis')
            ->where('slug', 'monnify')
            ->value('id');

        if (! $monnifyApiId) {
            return;
        }

        DB::table('transaction_logs')
            ->where('api_id', 1)
            ->where('unique_element', 'WALLET-FUNDING')
            ->where(function ($query) {
                $query->where('wallet_funding_provider', 1)
                    ->orWhereRaw("LOWER(COALESCE(payment_method, '')) LIKE '%monnify%'");
            })
            ->update([
                'api_id' => $monnifyApiId,
                'wallet_funding_provider' => $monnifyApiId,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs') || ! Schema::hasColumn('transaction_logs', 'api_id')) {
            return;
        }

        $monnifyApiId = DB::table('apis')
            ->where('slug', 'monnify')
            ->value('id');

        if (! $monnifyApiId) {
            return;
        }

        DB::table('transaction_logs')
            ->where('api_id', $monnifyApiId)
            ->where('unique_element', 'WALLET-FUNDING')
            ->where('wallet_funding_provider', $monnifyApiId)
            ->whereRaw("LOWER(COALESCE(payment_method, '')) LIKE '%monnify%'")
            ->update([
                'api_id' => 1,
                'wallet_funding_provider' => 1,
            ]);
    }
};
