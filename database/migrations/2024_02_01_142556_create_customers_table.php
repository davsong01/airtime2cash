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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('customer_level')->nullable();
                $table->integer('callback_url')->nullable();
                $table->double('referal_wallet', 11,2)->nullable();
                $table->double('wallet', 11,2)->nullable();
                $table->double('a2cashwallet', 11,2)->nullable();
                $table->integer('app_version')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::dropIfExists('customers');
        }

    }
};
