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
        if (Schema::hasTable('transaction_logs') && !Schema::hasColumn('transaction_logs', 'charge_breakdown')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->json('charge_breakdown')->nullable()->after('provider_charge');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'charge_breakdown')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('charge_breakdown');
            });
        }
    }
};
