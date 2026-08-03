<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'captcha_settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->json('captcha_settings')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'captcha_settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('captcha_settings');
            });
        }
    }
};
