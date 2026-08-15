<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'show_in_menu')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('show_in_menu')->default(false)->after('allow_subscription_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'show_in_menu')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('show_in_menu');
            });
        }
    }
};
