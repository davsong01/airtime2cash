<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (! Schema::hasColumn('settings', 'bvn_verification_provider_id')) {
                    $table->unsignedBigInteger('bvn_verification_provider_id')->nullable()->after('bank_verification_provider_id');
                }

                if (! Schema::hasColumn('settings', 'bvn_verification_mode')) {
                    $table->string('bvn_verification_mode')->default('manual')->after('bvn_verification_provider_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'bvn_verification_mode')) {
                    $table->dropColumn('bvn_verification_mode');
                }

                if (Schema::hasColumn('settings', 'bvn_verification_provider_id')) {
                    $table->dropColumn('bvn_verification_provider_id');
                }
            });
        }
    }
};
