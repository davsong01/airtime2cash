<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! Schema::hasColumn('settings', 'bank_verification_provider_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('bank_verification_provider_id')->nullable()->after('bank_transfer_provider_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'bank_verification_provider_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('bank_verification_provider_id');
            });
        }
    }
};
