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
        if (Schema::hasTable('a_p_is') && !Schema::hasColumn('a_p_is', 'pricing_data_status')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->boolean('pricing_data_status')->default(false)->after('pricing_data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('a_p_is') && Schema::hasColumn('a_p_is', 'pricing_data_status')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->dropColumn('pricing_data_status');
            });
        }
    }
};
