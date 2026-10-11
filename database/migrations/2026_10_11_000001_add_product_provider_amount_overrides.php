<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_share_product_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('auto_share_product_providers', 'min_amount_override')) {
                $table->decimal('min_amount_override', 15, 2)->nullable()->after('api_id');
            }

            if (! Schema::hasColumn('auto_share_product_providers', 'max_amount_override')) {
                $table->decimal('max_amount_override', 15, 2)->nullable()->after('min_amount_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auto_share_product_providers', function (Blueprint $table) {
            if (Schema::hasColumn('auto_share_product_providers', 'max_amount_override')) {
                $table->dropColumn('max_amount_override');
            }

            if (Schema::hasColumn('auto_share_product_providers', 'min_amount_override')) {
                $table->dropColumn('min_amount_override');
            }
        });
    }
};
