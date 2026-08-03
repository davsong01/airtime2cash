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
        if (Schema::hasTable('customer_info')) {
            Schema::create('customer_infos', function (Blueprint $table) {
                $table->id();
                $table->integer('customer_id');
                $table->string('sex')->nullable();
                $table->string('alternate_email')->nullable();
                $table->string('alternate_phone')->nullable();
                $table->string('home_address')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('local_government_area')->nullable();
                $table->string('identity_card')->nullable();
                $table->string('date_of_birth')->nullable();
                $table->string('gender')->nullable();
                $table->string('app_version')->nullable();
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::dropIfExists('customer_infos');
        }

    }
};
