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
        if (!Schema::hasColumn('categories', 'discount_type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->enum('discount_type', ['flat', 'percentage'])->default('flat');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'discount_type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn("discount_type");
            }); 
        }
    }
};
