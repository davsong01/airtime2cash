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
        if (!Schema::hasColumn('reserved_account_callbacks', 'payment_method')) {
            Schema::table('reserved_account_callbacks', function (Blueprint $table) {
                $table->string('payment_method')->nullable()->after('account_number');
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
