<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'manual_status')) {
                $table->string('manual_status')->nullable()->after('auto_share_rate');
            }

            if (!Schema::hasColumn('products', 'auto_share_status')) {
                $table->string('auto_share_status')->nullable()->after('manual_status');
            }
        });

        DB::table('products')
            ->where('type', 'airtime2cash')
            ->whereNull('manual_status')
            ->update(['manual_status' => 'active']);

        DB::table('products')
            ->where('type', 'airtime2cash')
            ->whereNull('auto_share_status')
            ->update(['auto_share_status' => 'inactive']);

        if (!Schema::hasColumn('airtime2_cash_transactions', 'transfer_mode')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                $table->string('transfer_mode')->default('manual')->after('charge_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('airtime2_cash_transactions', 'transfer_mode')) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) {
                $table->dropColumn('transfer_mode');
            });
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
