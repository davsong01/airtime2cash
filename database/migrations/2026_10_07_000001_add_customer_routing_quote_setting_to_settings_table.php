<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'customer_display_use_auto_share_routing')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('customer_display_use_auto_share_routing')
                    ->default(false)
                    ->after('auto_share_routing_mode');
            });
        }

        DB::table('settings')
            ->whereNull('customer_display_use_auto_share_routing')
            ->update(['customer_display_use_auto_share_routing' => false]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'customer_display_use_auto_share_routing')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('customer_display_use_auto_share_routing');
            });
        }
    }
};
