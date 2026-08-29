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
            if (! Schema::hasColumn('customers', 'can_access_w2bank_auto')) {
                $table->boolean('can_access_w2bank_auto')->default(false)->after('can_access_w2bank');
            }
        });

        DB::table('customers')
            ->whereNull('can_access_w2bank_auto')
            ->update(['can_access_w2bank_auto' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'can_access_w2bank_auto')) {
                $table->dropColumn('can_access_w2bank_auto');
            }
        });
    }
};
