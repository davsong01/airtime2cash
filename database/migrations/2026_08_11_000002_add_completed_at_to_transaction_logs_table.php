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
        Schema::table('transaction_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_logs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('provider_discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_logs', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_logs', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });
    }
};
