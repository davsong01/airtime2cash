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
        Schema::create('bvn_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('bvn', 11)->nullable();
            $table->string('mode')->default('auto');
            $table->string('status')->default('pending');
            $table->string('verification_status')->default('unverified');
            $table->boolean('name_match')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('verified_name')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('provider_slug')->nullable();
            $table->string('provider_name')->nullable();
            $table->json('request_data')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('billing_status')->nullable();
            $table->decimal('billing_amount', 12, 2)->default(0);
            $table->string('billing_transaction_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['customer_id', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bvn_verification_requests');
    }
};
