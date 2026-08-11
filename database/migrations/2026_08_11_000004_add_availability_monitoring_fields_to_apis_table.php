<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'availability_score')) {
                $table->unsignedTinyInteger('availability_score')->nullable()->after('balance');
            }

            if (! Schema::hasColumn('apis', 'availability_check_transactions_count')) {
                $table->unsignedInteger('availability_check_transactions_count')->nullable()->default(0)->after('availability_score');
            }

            if (! Schema::hasColumn('apis', 'availability_checked_at')) {
                $table->timestamp('availability_checked_at')->nullable()->after('availability_check_transactions_count');
            }

            if (! Schema::hasColumn('apis', 'availability_status')) {
                $table->string('availability_status')->nullable()->after('availability_checked_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'availability_status')) {
                $table->dropColumn('availability_status');
            }

            if (Schema::hasColumn('apis', 'availability_checked_at')) {
                $table->dropColumn('availability_checked_at');
            }

            if (Schema::hasColumn('apis', 'availability_check_transactions_count')) {
                $table->dropColumn('availability_check_transactions_count');
            }

            if (Schema::hasColumn('apis', 'availability_score')) {
                $table->dropColumn('availability_score');
            }
        });
    }
};
