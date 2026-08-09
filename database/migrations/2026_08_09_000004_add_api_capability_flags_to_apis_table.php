<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apis')) {
            Schema::table('apis', function (Blueprint $table) {
                if (! Schema::hasColumn('apis', 'is_bank_transfer')) {
                    $table->boolean('is_bank_transfer')->default(false)->after('status');
                }

                if (! Schema::hasColumn('apis', 'is_bank_verification')) {
                    $table->boolean('is_bank_verification')->default(false)->after('is_bank_transfer');
                }

                if (! Schema::hasColumn('apis', 'is_auto_share')) {
                    $table->boolean('is_auto_share')->default(false)->after('is_bank_verification');
                }

                if (! Schema::hasColumn('apis', 'is_payment_gateway')) {
                    $table->boolean('is_payment_gateway')->default(false)->after('is_auto_share');
                }
            });

            DB::table('apis')->whereIn('slug', ['monnify', 'paystack', 'kora', 'sagecloud'])->update([
                'is_bank_transfer' => 1,
                'is_bank_verification' => 1,
            ]);

            DB::table('apis')->whereIn('slug', ['monnify', 'paystack'])->update([
                'is_payment_gateway' => 1,
            ]);

            DB::table('apis')->where('slug', 'autosync')->update([
                'is_auto_share' => 1,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('apis')) {
            Schema::table('apis', function (Blueprint $table) {
                if (Schema::hasColumn('apis', 'is_payment_gateway')) {
                    $table->dropColumn('is_payment_gateway');
                }

                if (Schema::hasColumn('apis', 'is_auto_share')) {
                    $table->dropColumn('is_auto_share');
                }

                if (Schema::hasColumn('apis', 'is_bank_verification')) {
                    $table->dropColumn('is_bank_verification');
                }

                if (Schema::hasColumn('apis', 'is_bank_transfer')) {
                    $table->dropColumn('is_bank_transfer');
                }
            });
        }
    }
};
