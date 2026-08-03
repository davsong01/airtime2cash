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
        if (!Schema::hasColumn('products', 'min', 'max', 'api_price', 'system_price', 'fixed_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('min')->nullable()->after('name');
                $table->integer('max')->nullable()->after('min');
                $table->double('api_price')->nullable()->after('max');
                $table->double('system_price')->nullable()->after('api_price');
                $table->double('fixed_price')->nullable()->after('system_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
