<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banks') && ! Schema::hasColumn('banks', 'provider_codes')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->json('provider_codes')->nullable()->after('cbn_code');
            });
        }

        if (Schema::hasTable('banks') && ! Schema::hasColumn('banks', 'provider_meta')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->json('provider_meta')->nullable()->after('provider_codes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('banks') && Schema::hasColumn('banks', 'provider_meta')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->dropColumn('provider_meta');
            });
        }

        if (Schema::hasTable('banks') && Schema::hasColumn('banks', 'provider_codes')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->dropColumn('provider_codes');
            });
        }
    }
};
