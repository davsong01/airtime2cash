<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'kyc_status')) {
            return;
        }

        if (!Schema::hasColumn('customers', 'kyc_review_priority')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedTinyInteger('kyc_review_priority')
                    ->storedAs("CASE WHEN kyc_status IN ('awaiting-approval', 'pending') THEN 0 ELSE 1 END");
            });
        }

        if (Schema::hasColumns('customers', ['kyc_review_priority', 'updated_at', 'user_id'])
            && !Schema::hasIndex('customers', 'customers_kyc_review_queue_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(
                    ['kyc_review_priority', 'updated_at', 'user_id'],
                    'customers_kyc_review_queue_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        if (Schema::hasIndex('customers', 'customers_kyc_review_queue_index')) {
            Schema::table('customers', fn (Blueprint $table) => $table->dropIndex('customers_kyc_review_queue_index'));
        }

        if (Schema::hasColumn('customers', 'kyc_review_priority')) {
            Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('kyc_review_priority'));
        }
    }
};
