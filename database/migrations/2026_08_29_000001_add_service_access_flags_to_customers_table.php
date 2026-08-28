<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'can_access_w2bank')) {
                $table->boolean('can_access_w2bank')->default(false)->after('a2cashwallet');
            }

            if (! Schema::hasColumn('customers', 'can_access_a2c')) {
                $table->boolean('can_access_a2c')->default(false)->after('can_access_w2bank');
            }
        });

        DB::table('customers')
            ->whereNull('can_access_w2bank')
            ->update(['can_access_w2bank' => 0]);

        DB::table('customers')
            ->whereNull('can_access_a2c')
            ->update(['can_access_a2c' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'can_access_a2c')) {
                $table->dropColumn('can_access_a2c');
            }

            if (Schema::hasColumn('customers', 'can_access_w2bank')) {
                $table->dropColumn('can_access_w2bank');
            }
        });
    }
};
