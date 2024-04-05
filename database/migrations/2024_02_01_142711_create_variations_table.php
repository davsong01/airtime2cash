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
        if (!Schema::hasTable('variations')) {
            Schema::create('variations', function (Blueprint $table) {
                $table->id();
                $table->integer('product_id');
                $table->integer('category_id');
                $table->integer('api_id');
                $table->string('api_name')->nullable();
                $table->string('status')->default('active');
                $table->string('slug')->unique();
                $table->string('system_name')->nullable();
                $table->string('fixed_price')->nullable();
                $table->double('api_price', 11,2)->nullable();
                $table->double('system_price', 11,2)->nullable();
                $table->string('unique_element')->nullable();
                // $table->string('network')->nullable();
                $table->string('verifiable')->default('no');
                $table->integer('min')->nullable();
                $table->integer('max')->nullable();
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('variations')) {
            Schema::dropIfExists('variations');
        }
    }
};
