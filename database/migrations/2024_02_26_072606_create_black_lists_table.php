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
        if (!Schema::hasTable('black_lists')) {
            Schema::create('black_lists', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->string('value');
                $table->string('status')->comment('active|in-active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('black_lists')) {
            Schema::dropIfExists('black_lists');
        }
    }
};
