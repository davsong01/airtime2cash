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
        if (Schema::hasTable('a_p_is') && !Schema::hasColumn('a_p_is', 'extra_charges')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->json('extra_charges')->nullable()->after('pricing_data_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('a_p_is') && Schema::hasColumn('a_p_is', 'extra_charges')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->dropColumn('extra_charges');
            });
        }
    }
};
