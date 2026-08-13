<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'manual_profit_percentage')) {
                    $table->decimal('manual_profit_percentage', 10, 2)->nullable()->after('rate');
                }

                if (! Schema::hasColumn('products', 'auto_share_profit_percentage')) {
                    $table->decimal('auto_share_profit_percentage', 10, 2)->nullable()->after('auto_share_rate');
                }
            });
        }

        if (Schema::hasTable('airtime2_cash_transactions')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('airtime2_cash_transactions', 'profit_percentage')) {
                    $table->decimal('profit_percentage', 10, 2)->nullable()->after('charge_rate');
                }

                if (! Schema::hasColumn('airtime2_cash_transactions', 'profit')) {
                    $table->decimal('profit', 12, 2)->nullable()->after('profit_percentage');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'auto_share_profit_percentage')) {
                    $table->dropColumn('auto_share_profit_percentage');
                }

                if (Schema::hasColumn('products', 'manual_profit_percentage')) {
                    $table->dropColumn('manual_profit_percentage');
                }
            });
        }

        if (Schema::hasTable('airtime2_cash_transactions')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('airtime2_cash_transactions', 'profit')) {
                    $table->dropColumn('profit');
                }

                if (Schema::hasColumn('airtime2_cash_transactions', 'profit_percentage')) {
                    $table->dropColumn('profit_percentage');
                }
            });
        }
    }
};
