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
        if (!Schema::hasColumns('users', ['referral', 'username']))
        Schema::table('users', function (Blueprint $table) {
            $table->string('username');
            $table->string('referral')->nullable;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('users', ['referral', 'username'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['username', 'referral']);
            });
        }
    }
};
