<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')
            || !Schema::hasColumn('customers', 'user_id')
            || Schema::hasIndex('customers', 'customers_user_id_index')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->index('user_id', 'customers_user_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasIndex('customers', 'customers_user_id_index')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_user_id_index');
        });
    }
};
