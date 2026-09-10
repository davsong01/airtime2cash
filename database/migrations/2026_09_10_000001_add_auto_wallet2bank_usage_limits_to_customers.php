<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedInteger('auto_wallet2bank_usage_limit')->default(3)->after('can_access_w2bank_auto');
            $table->unsignedInteger('auto_wallet2bank_usage_window_minutes')->default(1440)->after('auto_wallet2bank_usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'auto_wallet2bank_usage_limit',
                'auto_wallet2bank_usage_window_minutes',
            ]);
        });
    }
};
