<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reserved_account_numbers')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            if (! Schema::hasColumn('reserved_account_numbers', 'api_id')) {
                Schema::table('reserved_account_numbers', function (Blueprint $table) {
                    $table->unsignedBigInteger('api_id')->nullable()->after('id');
                });
            }

            if (Schema::hasColumn('reserved_account_numbers', 'paymentgateway_id')) {
                DB::table('reserved_account_numbers')
                    ->whereNull('api_id')
                    ->update(['api_id' => DB::raw('paymentgateway_id')]);
            }

            return;
        }

        if (Schema::hasColumn('reserved_account_numbers', 'paymentgateway_id')) {
            DB::statement('ALTER TABLE reserved_account_numbers CHANGE paymentgateway_id api_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reserved_account_numbers')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            if (! Schema::hasColumn('reserved_account_numbers', 'paymentgateway_id')) {
                Schema::table('reserved_account_numbers', function (Blueprint $table) {
                    $table->integer('paymentgateway_id')->nullable()->after('account_name');
                });
            }

            if (Schema::hasColumn('reserved_account_numbers', 'api_id')) {
                DB::table('reserved_account_numbers')
                    ->whereNull('paymentgateway_id')
                    ->update(['paymentgateway_id' => DB::raw('api_id')]);
            }

            return;
        }

        if (Schema::hasColumn('reserved_account_numbers', 'api_id')) {
            DB::statement('ALTER TABLE reserved_account_numbers CHANGE api_id paymentgateway_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
