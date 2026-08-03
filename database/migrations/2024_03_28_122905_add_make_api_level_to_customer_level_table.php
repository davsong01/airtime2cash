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
        if (!Schema::hasColumn('customer_levels', 'make_api_level')) {
            Schema::table('customer_levels', function (Blueprint $table) {
                $table->string('make_api_level')->default('no');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customer_levels', 'make_api_level')) {
            Schema::table('customer_levels', function (Blueprint $table) {
                $table->dropColumn("make_api_level")->nullable();
            }); 
        }
    }
};
