<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_logs') && ! Schema::hasColumn('transaction_logs', 'resolution_date')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->timestamp('resolution_date')->nullable()->after('completed_at');
            });
        }

        if (Schema::hasTable('apis') && ! Schema::hasColumn('apis', 'pending_note')) {
            Schema::table('apis', function (Blueprint $table) {
                $table->text('pending_note')->nullable()->after('live_base_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'resolution_date')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('resolution_date');
            });
        }

        if (Schema::hasTable('apis') && Schema::hasColumn('apis', 'pending_note')) {
            Schema::table('apis', function (Blueprint $table) {
                $table->dropColumn('pending_note');
            });
        }
    }
};
