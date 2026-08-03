<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('referral_earnings')) {
            Schema::create('referral_earnings', function (Blueprint $table) {
                $table->id();
                $table->integer('customer_id')->comment('customer to earn');
                $table->integer('referred_customer_id');
                $table->double('amount', 11, 2);
                $table->double('balance_before', 11, 2)->nullable();
                $table->double('balance_after', 11, 2)->nullable();
                $table->string('type');
                $table->string('transaction_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('referral_earnings')) {
            Schema::dropIfExists('referral_earnings');
        }
    }
};
