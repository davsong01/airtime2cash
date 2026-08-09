<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('a_p_is') && ! Schema::hasTable('apis')) {
            Schema::rename('a_p_is', 'apis');
        }

        if (Schema::hasTable('banks') && ! Schema::hasColumn('banks', 'api_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->foreignId('api_id')->nullable()->after('id')->index();
            });
        }

        if (Schema::hasTable('reserved_account_numbers') && Schema::hasTable('apis')) {
            $monnifyId = DB::table('apis')->where('slug', 'monnify')->value('id');

            if ($monnifyId) {
                $column = Schema::hasColumn('reserved_account_numbers', 'api_id')
                    ? 'api_id'
                    : 'paymentgateway_id';

                DB::table('reserved_account_numbers')->update([$column => $monnifyId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('banks') && Schema::hasColumn('banks', 'api_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->dropIndex(['api_id']);
                $table->dropColumn('api_id');
            });
        }

        if (Schema::hasTable('apis') && ! Schema::hasTable('a_p_is')) {
            Schema::rename('apis', 'a_p_is');
        }
    }
};
