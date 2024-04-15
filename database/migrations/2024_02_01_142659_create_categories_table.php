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
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('icon')->nullable();
                $table->string('status')->default('inactive');
                $table->string('slug');
                $table->string('unique_element')->default('phone');
                $table->text('description')->nullable();
                $table->text('seo_description')->nullable();
                $table->text('seo_keywords')->nullable();
                $table->string('display_name')->nullable();
                $table->string('seo_title')->nullable();
                $table->string('order')->nullable();
                $table->string('type')->default('general');
                $table->timestamps();
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categories')) {
            Schema::dropIfExists('categories');
        }
    }
};
