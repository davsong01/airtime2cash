<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! Schema::hasColumn('settings', 'bvn_verification_charge')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->decimal('bvn_verification_charge', 12, 2)->default(0)->after('bvn_verification_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'bvn_verification_charge')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('bvn_verification_charge');
            });
        }
    }
};
