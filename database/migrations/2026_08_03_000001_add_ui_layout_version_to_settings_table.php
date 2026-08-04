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
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'admin_layout')) {
                $table->string('admin_layout')->default('legacy');
            }
            if (!Schema::hasColumn('settings', 'customer_layout')) {
                $table->string('customer_layout')->default('legacy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $columns = collect(['admin_layout', 'customer_layout'])
            ->filter(fn (string $column) => Schema::hasColumn('settings', $column))
            ->all();

        if ($columns) {
            Schema::table('settings', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
