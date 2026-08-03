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
        if (!Schema::hasColumn('transaction_logs', 'user_status')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->string('user_status')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transaction_logs', 'discount_type')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn("user_status");
            }); 
        }
    }
};
