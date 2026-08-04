<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'auto_share_rate')) {
            Schema::table('products', function (Blueprint $table) {
                $table->double('auto_share_rate')->nullable()->after('rate');
            });
        }

        DB::table('products')
            ->where('type', 'airtime2cash')
            ->whereNull('auto_share_rate')
            ->update(['auto_share_rate' => DB::raw('rate')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'auto_share_rate')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_share_rate');
            });
        }
    }
};
