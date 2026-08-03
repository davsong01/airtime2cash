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
        Schema::create('biller_logs', function (Blueprint $table) {
            $table->id();
            $table->string('billers_code')->nullable();
            $table->text('raw_data')->nullable();
            $table->text('refined_data')->nullable();
            $table->string('service_id')->nullable();
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biller_logs');
    }
};
