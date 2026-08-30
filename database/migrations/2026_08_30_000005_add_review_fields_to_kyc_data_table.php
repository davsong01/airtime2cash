<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kyc_data')) {
            return;
        }

        Schema::table('kyc_data', function (Blueprint $table) {
            if (! Schema::hasColumn('kyc_data', 'review_note')) {
                $table->text('review_note')->nullable()->after('status');
            }

            if (! Schema::hasColumn('kyc_data', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_note');
            }

            if (! Schema::hasColumn('kyc_data', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kyc_data')) {
            return;
        }

        Schema::table('kyc_data', function (Blueprint $table) {
            if (Schema::hasColumn('kyc_data', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }

            if (Schema::hasColumn('kyc_data', 'reviewed_by')) {
                $table->dropColumn('reviewed_by');
            }

            if (Schema::hasColumn('kyc_data', 'review_note')) {
                $table->dropColumn('review_note');
            }
        });
    }
};
