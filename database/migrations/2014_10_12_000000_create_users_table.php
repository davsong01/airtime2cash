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
        if (!Schema::hasTable('users')){
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('firstname')->nullable();
                $table->string('type')->default('customer');
                $table->string('middlename')->nullable();
                $table->string('lastname')->nullable();
                $table->string('avatar')->nullable();
                $table->string('phone')->nullable();
                $table->string('status')->nullable();
                $table->string('wallet_lock')->nullable();
                $table->string('api_key')->nullable();
                $table->string('secret_key')->nullable();
                $table->string('public_key')->nullable();
                $table->string('transaction_pin')->nullable();
                $table->string('app_version')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')){Schema::dropIfExists('users');}
    }
};
