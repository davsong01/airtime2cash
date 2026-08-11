<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'contract_id')) {
                $table->string('contract_id')->nullable()->after('public_key');
            }
        });

        if (! Schema::hasTable('payment_gateways')) {
            return;
        }

        $legacyGateway = DB::table('payment_gateways')->where('id', 1)->first();

        if (! $legacyGateway) {
            return;
        }

        $monnifyApi = DB::table('apis')
            ->where('id', 1)
            ->orWhere('slug', 'monnify')
            ->first();

        if (! $monnifyApi) {
            return;
        }

        DB::table('apis')
            ->where('id', $monnifyApi->id)
            ->update([
                'api_key' => $monnifyApi->api_key ?: ($legacyGateway->api_key ?? null),
                'secret_key' => $monnifyApi->secret_key ?: ($legacyGateway->secret_key ?? null),
                'public_key' => $monnifyApi->public_key ?: ($legacyGateway->public_key ?? null),
                'contract_id' => $monnifyApi->contract_id ?: ($legacyGateway->contract_id ?? null),
                'live_base_url' => $monnifyApi->live_base_url ?: ($legacyGateway->base_url ?? null),
                'sandbox_base_url' => $monnifyApi->sandbox_base_url ?: ($legacyGateway->base_url ?? null),
                'status' => $monnifyApi->status ?: ($legacyGateway->status ?? null),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
        });
    }
};
