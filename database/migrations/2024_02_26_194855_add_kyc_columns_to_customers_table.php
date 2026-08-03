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
        if (!Schema::hasColumn('customers', 'kyc_status', 'bvn', 'bvn_verification_status')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('kyc_status')->default('unverified')->after('customer_level');
                $table->string('bvn_verification_status')->default('unverified')->after('customer_level');
                $table->string('bvn')->nullable()->after('customer_level');
                $table->text('bvn_data')->nullable()->after('customer_level');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
