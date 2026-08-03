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
        if (!Schema::hasColumn('payment_gateways', 'reserved_account_payment_charge')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->double('reserved_account_payment_charge')->nullable()->after('charge');
            });
        }

        if (!Schema::hasColumn('transaction_logs', 'account_number')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->string('account_number')->nullable()->after('unique_element');
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
