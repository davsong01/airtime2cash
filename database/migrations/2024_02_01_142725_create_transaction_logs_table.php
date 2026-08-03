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
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('initiated');
            $table->string('reference_id');
            $table->string('transaction_id');
            $table->string('payment_method')->nullable();
            $table->integer('customer_id');
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('unique_element');
            $table->double('discount', 11,2);
            $table->double('unit_price', 11,2);
            $table->double('amount', 11,2);
            $table->double('total_amount', 11,2);
            $table->double('balance_before', 11, 2)->nullable();
            $table->double('balance_after', 11, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('variation_id')->nullable();
            $table->string('variation_name')->nullable();
            $table->string('category_id')->nullable();
            $table->text('request_data')->nullable();
            $table->longtext('api_response')->nullable();
            $table->string('extras')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('domain_name')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('api_id')->nullable();
            $table->string('descr')->nullable();
            $table->string('app_version')->nullable();
            $table->double('provider_charge',11,2)->nullable();
            $table->double('provider_discount',11,2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
