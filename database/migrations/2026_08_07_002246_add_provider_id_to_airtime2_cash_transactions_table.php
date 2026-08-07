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
        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->after('provider_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });
    }
};
