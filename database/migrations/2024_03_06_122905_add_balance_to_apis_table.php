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
        if (!Schema::hasColumn('a_p_is', 'balance')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->double('balance')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('a_p_is', 'balance')) {
            Schema::table('a_p_is', function (Blueprint $table) {
                $table->dropColumn("balance")->nullable();
            }); 
        }
    }
};
