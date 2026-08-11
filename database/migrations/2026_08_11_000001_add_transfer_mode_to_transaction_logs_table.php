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
        Schema::table('transaction_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_logs', 'transfer_mode')) {
                $table->string('transfer_mode')->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_logs', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_logs', 'transfer_mode')) {
                $table->dropColumn('transfer_mode');
            }
        });
    }
};
