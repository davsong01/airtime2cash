<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis') || Schema::hasColumn('apis', 'account_number')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('public_key');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('apis') && Schema::hasColumn('apis', 'account_number')) {
            Schema::table('apis', function (Blueprint $table) {
                $table->dropColumn('account_number');
            });
        }
    }
};
