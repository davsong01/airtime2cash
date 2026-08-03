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
        Schema::create('reserved_account_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('account_reference')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->integer('paymentgateway_id');
            $table->string('trans_lower_amt')->nullable();
            $table->string('trans_upper_amt')->nullable();
            $table->double('charge')->nullable();
            $table->string('status')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('purpose')->default('Wallet Funding');
            $table->string('bvn');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserved_account_numbers');
    }
};
