<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discounts') && !Schema::hasColumn('discounts', 'transfer_mode')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->string('transfer_mode')->nullable()->after('variation_id');
            });
        }

        if (Schema::hasTable('discounts') && Schema::hasColumn('discounts', 'price')) {
            DB::statement('ALTER TABLE discounts MODIFY price DOUBLE NULL');
        }

        if (Schema::hasTable('discounts') && Schema::hasTable('products')) {
            $airtimeProductIds = DB::table('products')
                ->where('type', 'airtime2cash')
                ->pluck('id')
                ->all();

            if (!empty($airtimeProductIds)) {
                DB::table('discounts')
                    ->whereIn('product_id', $airtimeProductIds)
                    ->whereNull('transfer_mode')
                    ->update(['transfer_mode' => 'manual']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('discounts') && Schema::hasColumn('discounts', 'transfer_mode')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->dropColumn('transfer_mode');
            });
        }

        if (Schema::hasTable('discounts') && Schema::hasColumn('discounts', 'price')) {
            DB::statement('ALTER TABLE discounts MODIFY price DOUBLE NOT NULL');
        }
    }
};
