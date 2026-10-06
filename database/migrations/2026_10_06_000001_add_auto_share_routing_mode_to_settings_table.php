<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'auto_share_routing_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('auto_share_routing_mode')
                    ->default('manual')
                    ->after('auto_share_provider_id');
            });
        }

        DB::table('settings')
            ->whereNull('auto_share_routing_mode')
            ->update(['auto_share_routing_mode' => 'manual']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'auto_share_routing_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('auto_share_routing_mode');
            });
        }
    }
};
