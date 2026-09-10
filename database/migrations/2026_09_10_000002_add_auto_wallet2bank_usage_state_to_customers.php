<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedInteger('auto_wallet2bank_usage_count')->default(0)->after('auto_wallet2bank_usage_window_minutes');
            $table->dateTime('auto_wallet2bank_usage_reset_at')->nullable()->after('auto_wallet2bank_usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'auto_wallet2bank_usage_count',
                'auto_wallet2bank_usage_reset_at',
            ]);
        });
    }
};
