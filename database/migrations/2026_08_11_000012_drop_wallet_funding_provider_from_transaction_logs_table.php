<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_logs')) {
            return;
        }

        if (Schema::hasIndex('transaction_logs', 'transaction_logs_wallet_provider_created_at_index')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropIndex('transaction_logs_wallet_provider_created_at_index');
            });
        }

        if (Schema::hasColumn('transaction_logs', 'wallet_funding_provider')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('wallet_funding_provider');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs')) {
            return;
        }

        if (! Schema::hasColumn('transaction_logs', 'wallet_funding_provider')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->integer('wallet_funding_provider')->nullable()->after('id');
            });
        }

        if (! Schema::hasIndex('transaction_logs', 'transaction_logs_wallet_provider_created_at_index')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->index(['wallet_funding_provider', 'created_at'], 'transaction_logs_wallet_provider_created_at_index');
            });
        }
    }
};
