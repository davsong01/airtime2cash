<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')
            || Schema::hasColumn('airtime2_cash_transactions', 'provider_session_id')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            $table->text('provider_session_id')->nullable()->after('provider_response');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')
            || ! Schema::hasColumn('airtime2_cash_transactions', 'provider_session_id')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            $table->dropColumn('provider_session_id');
        });
    }
};
