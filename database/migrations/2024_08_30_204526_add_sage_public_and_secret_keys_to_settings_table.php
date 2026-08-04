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
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'sage_public_key')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('sage_public_key')->nullable();
            });
        }

        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'sage_secret_key')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('sage_secret_key')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'sage_public_key')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('sage_public_key');
            });
        }

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'sage_secret_key')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('sage_secret_key');
            });
        }

    }
};
