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
        Schema::create('airtime2_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->double('amount_charged', 11,2)->nullable();
            $table->double('amount_paid',11, 2)->nullable();
            $table->double('charge_rate',11, 2)->nullable();
            $table->double('total_amount', 11, 2)->nullable();
            $table->string('description')->nullable();
            $table->integer('product_id');
            $table->integer('customer_id');
            $table->string('type')->default('credit');
            $table->string('phone_numbers')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id');
            $table->integer('approved_by')->nullable();
            $table->string('status')->default('pending'); // pending, approved, declined
            $table->text('decline_reason')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airtime2_cash_transactions');
    }
};
