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
        if (!Schema::hasColumn('transaction_logs', 'upgrade_level')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->string('upgrade_level')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transaction_logs', 'upgrade_level')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn("upgrade_level");
            }); 
        }
    }
};
