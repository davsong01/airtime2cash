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
        if (!Schema::hasColumn('settings', 'referral_system_status')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('referral_system_status')->nullable()->before('created_at');
            });
        }

        if (!Schema::hasColumn('settings', 'referral_percentage')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->double('referral_percentage')->nullable()->before('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
