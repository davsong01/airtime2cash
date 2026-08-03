<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('reserved_account_numbers', 'customer_id')) {
            Schema::table('reserved_account_numbers', function (Blueprint $table) {
                $table->integer('customer_id')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('reserved_account_numbers', 'response')) {
            Schema::table('reserved_account_numbers', function (Blueprint $table) {
                $table->longText('response')->nullable()->after('purpose');
            });
        }

        if (!Schema::hasColumn('reserved_account_numbers', 'bank_code')) {
            Schema::table('reserved_account_numbers', function (Blueprint $table) {
                $table->string('bank_code')->nullable()->after('bank_name');
            });
        }

        if (Schema::hasColumn('reserved_account_numbers', 'verification_status')) {
            Schema::table('reserved_account_numbers', function (Blueprint $table) {
                $table->dropColumn('verification_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
