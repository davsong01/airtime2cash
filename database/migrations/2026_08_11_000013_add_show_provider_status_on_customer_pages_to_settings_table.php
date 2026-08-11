<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! Schema::hasColumn('settings', 'show_provider_status_on_customer_pages')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('show_provider_status_on_customer_pages')
                    ->default(true)
                    ->after('google_dashboard_ad_enabled');
            });
        }

        DB::table('settings')->update([
            'show_provider_status_on_customer_pages' => true,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'show_provider_status_on_customer_pages')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('show_provider_status_on_customer_pages');
            });
        }
    }
};
