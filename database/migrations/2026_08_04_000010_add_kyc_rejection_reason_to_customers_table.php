<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'kyc_rejection_reason')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->text('kyc_rejection_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'kyc_rejection_reason')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('kyc_rejection_reason');
            });
        }
    }
};
