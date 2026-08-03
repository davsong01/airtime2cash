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
        if (!Schema::hasColumn('settings', 'allow_fund_with_reserved_account')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('allow_fund_with_reserved_account')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('settings', 'allow_fund_with_card')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('allow_fund_with_card')->nullable()->after('allow_fund_with_reserved_account');
            });
        }

        if (!Schema::hasColumn('settings', 'payment_gateway')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('payment_gateway')->nullable()->after('allow_fund_with_reserved_account');
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
