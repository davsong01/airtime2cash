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
            if (! Schema::hasColumn('apis', 'successful_transactions')) {
                $table->unsignedInteger('successful_transactions')->nullable()->default(0)->after('availability_check_transactions_count');
            }

            if (! Schema::hasColumn('apis', 'failed_transactions')) {
                $table->unsignedInteger('failed_transactions')->nullable()->default(0)->after('successful_transactions');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'failed_transactions')) {
                $table->dropColumn('failed_transactions');
            }

            if (Schema::hasColumn('apis', 'successful_transactions')) {
                $table->dropColumn('successful_transactions');
            }
        });
    }
};
