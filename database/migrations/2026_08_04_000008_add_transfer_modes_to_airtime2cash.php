<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            if (!Schema::hasColumn('products', 'manual_status')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->string('manual_status')->nullable();
                });
            }

            if (!Schema::hasColumn('products', 'auto_share_status')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->string('auto_share_status')->nullable();
                });
            }
        }

        if (Schema::hasTable('products') && Schema::hasColumns('products', ['type', 'manual_status'])) {
            DB::table('products')
                ->where('type', 'airtime2cash')
                ->whereNull('manual_status')
                ->update(['manual_status' => 'active']);
        }

        if (Schema::hasTable('products') && Schema::hasColumns('products', ['type', 'auto_share_status'])) {
            DB::table('products')
                ->where('type', 'airtime2cash')
                ->whereNull('auto_share_status')
                ->update(['auto_share_status' => 'inactive']);
        }

        if (Schema::hasTable('airtime2_cash_transactions')
            && !Schema::hasColumn('airtime2_cash_transactions', 'transfer_mode')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                $table->string('transfer_mode')->default('manual');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('airtime2_cash_transactions')
            && Schema::hasColumn('airtime2_cash_transactions', 'transfer_mode')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                $table->dropColumn('transfer_mode');
            });
        }

        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $columns = collect(['manual_status', 'auto_share_status'])
                ->filter(fn (string $column) => Schema::hasColumn('products', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
