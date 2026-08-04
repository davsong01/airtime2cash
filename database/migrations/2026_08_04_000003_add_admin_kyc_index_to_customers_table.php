<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')
            || !Schema::hasColumns('customers', ['kyc_status', 'updated_at', 'user_id'])
            || Schema::hasIndex('customers', 'customers_kyc_queue_index')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->index(
                ['kyc_status', 'updated_at', 'user_id'],
                'customers_kyc_queue_index'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasIndex('customers', 'customers_kyc_queue_index')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_kyc_queue_index');
        });
    }
};
