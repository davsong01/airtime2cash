<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_levels') && !Schema::hasColumn('customer_levels', 'status')) {
            Schema::table('customer_levels', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('upgrade_amount');
            });
        }

        if (Schema::hasTable('customer_levels') && Schema::hasColumn('customer_levels', 'status')) {
            DB::table('customer_levels')->whereNull('status')->update(['status' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_levels') && Schema::hasColumn('customer_levels', 'status')) {
            Schema::table('customer_levels', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
