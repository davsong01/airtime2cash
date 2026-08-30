<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        if (! Schema::hasColumn('apis', 'is_bvn_verification')) {
            Schema::table('apis', function (Blueprint $table) {
                $table->boolean('is_bvn_verification')->default(false)->after('is_bank_verification');
            });
        }

        DB::table('apis')->where('slug', 'monnify')->update([
            'is_bvn_verification' => 1,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('apis') && Schema::hasColumn('apis', 'is_bvn_verification')) {
            Schema::table('apis', function (Blueprint $table) {
                $table->dropColumn('is_bvn_verification');
            });
        }
    }
};
