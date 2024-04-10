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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->integer('category_id');
                $table->string('status')->default('inactive');
                $table->string('name')->nullable();
                $table->string('slug')->unique();
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->text('seo_keywords')->nullable();
                $table->string('display_name')->nullable();
                $table->string('image')->nullable();
                $table->text('description')->nullable();
                $table->string('has_variations')->default('no');
                $table->text('instruction')->nullable(); // Used for airtime to cash products
                $table->double('rate')->nullable(); // Used for airtime to cash products
                $table->string('type')->default('general');
                $table->string('api_id');
                $table->string('allow_meter_validation')->default('no');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) Schema::dropIfExists('products');
    }
};
