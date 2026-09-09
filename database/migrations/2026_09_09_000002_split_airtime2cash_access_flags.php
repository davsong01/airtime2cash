<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'can_access_a2c_auto')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('can_access_a2c_auto')->default(false)->after('can_access_a2c');
            });
        }

        if (! Schema::hasColumn('customers', 'can_access_a2c_manual')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('can_access_a2c_manual')->default(false)->after('can_access_a2c_auto');
            });
        }

        DB::table('customers')
            ->where('can_access_a2c', true)
            ->update([
                'can_access_a2c_auto' => false,
                'can_access_a2c_manual' => true,
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'can_access_a2c_manual')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('can_access_a2c_manual');
            });
        }

        if (Schema::hasColumn('customers', 'can_access_a2c_auto')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('can_access_a2c_auto');
            });
        }
    }
};
