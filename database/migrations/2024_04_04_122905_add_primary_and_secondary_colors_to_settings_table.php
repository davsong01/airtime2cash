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
        if (!Schema::hasColumns('settings', ['menu_text_color', 'menu_background_color', 'active_color','text_header_color','dasboard_customer_details_color', 'active_hover_color'])) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('menu_text_color')->default('#fffff'); // Text color
                $table->string('menu_background_color')->default('#1a233a'); // Background color
                $table->string('active_color')->default('#FFB43C');
                $table->string('text_header_color')->default('#bac0c7');
                $table->string('dasboard_customer_details_color')->default('#fffff');
                $table->string('active_hover_color')->default('#5a8dee');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('settings', ['primary_color', 'secondary_color', 'active_color', 'dasboard_customer_details_color'])) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn("menu_text_color");
                $table->dropColumn("menu_background_color");
                $table->dropColumn("active_color");
                $table->dropColumn("text_header_color");
                $table->dropColumn("dasboard_customer_details_color");
                $table->dropColumn("active_hover_color");
            }); 
        }
    }
};