<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('airtime2_cash_transactions', 'provider_selection_reason')) {
                $table->text('provider_selection_reason')->nullable()->after('provider_id');
            }

            if (! Schema::hasColumn('airtime2_cash_transactions', 'provider_selection_meta')) {
                $table->json('provider_selection_meta')->nullable()->after('provider_selection_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('airtime2_cash_transactions', 'provider_selection_meta')) {
                $table->dropColumn('provider_selection_meta');
            }

            if (Schema::hasColumn('airtime2_cash_transactions', 'provider_selection_reason')) {
                $table->dropColumn('provider_selection_reason');
            }
        });
    }
};
