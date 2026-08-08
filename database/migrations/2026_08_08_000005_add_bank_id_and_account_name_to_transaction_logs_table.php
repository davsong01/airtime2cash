<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('transaction_logs') && !Schema::hasColumn('transaction_logs', 'bank_id')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('bank_id')->nullable()->after('unique_element');
            });
        }

        if (Schema::hasTable('transaction_logs') && !Schema::hasColumn('transaction_logs', 'account_name')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->string('account_name')->nullable()->after('bank_id');
            });
        }

        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'account_number') && Schema::hasColumn('transaction_logs', 'account_name')) {
            $driver = DB::getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE transaction_logs MODIFY account_number VARCHAR(255) NULL AFTER account_name');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'account_number') && Schema::hasColumn('transaction_logs', 'bank_id')) {
            $driver = DB::getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE transaction_logs MODIFY account_number VARCHAR(255) NULL AFTER unique_element');
            }
        }

        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'account_name')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('account_name');
            });
        }

        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'bank_id')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('bank_id');
            });
        }
    }
};
