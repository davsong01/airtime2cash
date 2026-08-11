<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'merchant_email')) {
                $table->dropColumn('merchant_email');
            }

            if (Schema::hasColumn('apis', 'password')) {
                $table->dropColumn('password');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'password')) {
                $table->string('password')->nullable()->after('public_key');
            }

            if (! Schema::hasColumn('apis', 'merchant_email')) {
                $table->string('merchant_email')->nullable()->after('contract_id');
            }
        });
    }
};
