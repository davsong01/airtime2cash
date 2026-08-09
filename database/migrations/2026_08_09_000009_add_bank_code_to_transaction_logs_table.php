<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_logs') || Schema::hasColumn('transaction_logs', 'bank_code')) {
            return;
        }

        Schema::table('transaction_logs', function (Blueprint $table) {
            $table->string('bank_code')->nullable()->after('bank_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_logs') && Schema::hasColumn('transaction_logs', 'bank_code')) {
            Schema::table('transaction_logs', function (Blueprint $table) {
                $table->dropColumn('bank_code');
            });
        }
    }
};
