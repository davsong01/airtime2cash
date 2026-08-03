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
        if (!Schema::hasColumn('wallets', 'payment_method')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->string('payment_method')->nullable()->after('type');
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
