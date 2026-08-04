<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedTinyInteger('kyc_review_priority')
                ->storedAs("CASE WHEN kyc_status IN ('awaiting-approval', 'pending') THEN 0 ELSE 1 END");

            $table->index(
                ['kyc_review_priority', 'updated_at', 'user_id'],
                'customers_kyc_review_queue_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_kyc_review_queue_index');
            $table->dropColumn('kyc_review_priority');
        });
    }
};
