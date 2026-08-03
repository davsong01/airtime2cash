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
        if (!Schema::hasColumn('discounts', 'product_id')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->string('product_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('discounts', 'product_id')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->dropColumn("product_id")->nullable();
            }); 
        }
    }
};
