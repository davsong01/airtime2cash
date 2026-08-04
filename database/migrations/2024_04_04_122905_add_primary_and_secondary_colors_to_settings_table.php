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
            if (!Schema::hasColumn('settings', 'menu_text_color')) {
                $table->string('menu_text_color')->default('#F4FAF9');
            }
            if (!Schema::hasColumn('settings', 'menu_background_color')) {
                $table->string('menu_background_color')->default('#123F43');
            }
            if (!Schema::hasColumn('settings', 'active_color')) {
                $table->string('active_color')->default('#0B7D4F');
            }
            if (!Schema::hasColumn('settings', 'text_header_color')) {
                $table->string('text_header_color')->default('#286F70');
            }
            if (!Schema::hasColumn('settings', 'dasboard_customer_details_color')) {
                $table->string('dasboard_customer_details_color')->default('#F4E85A');
            }
            if (!Schema::hasColumn('settings', 'active_hover_color')) {
                $table->string('active_hover_color')->default('#5a8dee');
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

        $columns = collect([
            'menu_text_color',
            'menu_background_color',
            'active_color',
            'text_header_color',
            'dasboard_customer_details_color',
            'active_hover_color',
        ])->filter(fn (string $column) => Schema::hasColumn('settings', $column))->all();

        if ($columns) {
            Schema::table('settings', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
