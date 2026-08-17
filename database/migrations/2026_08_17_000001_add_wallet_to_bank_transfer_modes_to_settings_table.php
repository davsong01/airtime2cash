<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! Schema::hasColumn('settings', 'wallet_to_bank_transfer_auto_status')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('wallet_to_bank_transfer_auto_status')
                    ->nullable()
                    ->after('allow_fund_with_reserved_account');
            });
        }

        if (! Schema::hasColumn('settings', 'wallet_to_bank_transfer_manual_status')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('wallet_to_bank_transfer_manual_status')
                    ->nullable()
                    ->after('wallet_to_bank_transfer_auto_status');
            });
        }

        DB::table('settings')->update([
            'wallet_to_bank_transfer_auto_status' => 'enabled',
            'wallet_to_bank_transfer_manual_status' => 'enabled',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (Schema::hasColumn('settings', 'wallet_to_bank_transfer_manual_status')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('wallet_to_bank_transfer_manual_status');
            });
        }

        if (Schema::hasColumn('settings', 'wallet_to_bank_transfer_auto_status')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('wallet_to_bank_transfer_auto_status');
            });
        }
    }
};
