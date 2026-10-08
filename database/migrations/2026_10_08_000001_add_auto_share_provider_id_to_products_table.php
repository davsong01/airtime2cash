<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'auto_share_provider_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('auto_share_provider_id')
                    ->nullable()
                    ->after('api_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'auto_share_provider_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_share_provider_id');
            });
        }
    }
};
