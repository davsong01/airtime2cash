<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'google_dashboard_ad_enabled')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('google_dashboard_ad_enabled')
                    ->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'google_dashboard_ad_enabled')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('google_dashboard_ad_enabled');
            });
        }
    }
};
