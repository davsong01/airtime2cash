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
                $table->string('menu_text_color')->default('#F4FAF9');
                $table->string('menu_background_color')->default('#123F43');
                $table->string('active_color')->default('#0B7D4F');
                $table->string('text_header_color')->default('#286F70');
                $table->string('dasboard_customer_details_color')->default('#F4E85A');
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
