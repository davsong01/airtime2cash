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
        if (!Schema::hasColumns('settings', ['referral_settings'])) {
            Schema::table('settings', function (Blueprint $table) {
                $table->json('referral_settings')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('settings', ['referral_settings'])) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('referral_settings');
            });
        }

    }
};
