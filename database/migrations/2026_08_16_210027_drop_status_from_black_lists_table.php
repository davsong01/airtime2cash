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
        if (Schema::hasTable('black_lists') && Schema::hasColumn('black_lists', 'status')) {
            Schema::table('black_lists', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('black_lists') && !Schema::hasColumn('black_lists', 'status')) {
            Schema::table('black_lists', function (Blueprint $table) {
                $table->string('status')->default('active')->comment('active|in-active');
            });
        }
    }
};
