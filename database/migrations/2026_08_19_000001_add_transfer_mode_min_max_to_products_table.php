<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'manual_min')) {
                $table->decimal('manual_min', 12, 2)->nullable()->after('manual_profit_percentage');
            }

            if (! Schema::hasColumn('products', 'manual_max')) {
                $table->decimal('manual_max', 12, 2)->nullable()->after('manual_min');
            }

            if (! Schema::hasColumn('products', 'auto_share_min')) {
                $table->decimal('auto_share_min', 12, 2)->nullable()->after('auto_share_profit_percentage');
            }

            if (! Schema::hasColumn('products', 'auto_share_max')) {
                $table->decimal('auto_share_max', 12, 2)->nullable()->after('auto_share_min');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $columns = collect(['auto_share_max', 'auto_share_min', 'manual_max', 'manual_min'])
                ->filter(fn (string $column) => Schema::hasColumn('products', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
