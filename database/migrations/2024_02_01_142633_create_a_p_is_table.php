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
        if (!Schema::hasTable('a_p_is')){
            Schema::create('a_p_is', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->nullable();
                $table->string('api_key')->nullable();
                $table->string('secret_key')->nullable();
                $table->string('public_key')->nullable();
                $table->string('slug')->nullable();
                $table->string('warning_threshold')->nullable();
                $table->string('warning_threshold_status')->nullable();
                $table->string('live_base_url')->nullable();
                $table->string('sandbox_base_url')->nullable();
                $table->string('file_name')->nullable();
                $table->double('balance')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('a_p_is')) {
            Schema::dropIfExists('a_p_is');
        }

    }
};
