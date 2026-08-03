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
        Schema::create('reserved_account_callbacks', function (Blueprint $table) {
            $table->id();
            $table->longText('raw')->nullable();
            $table->string('status')->default('pending');
            $table->integer('provider_id')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamp('paid_on')->nullable();
            $table->string('session_id');
            $table->string('transaction_reference');
            $table->longText('raw_requery')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserved_account_callbacks');
    }
};
