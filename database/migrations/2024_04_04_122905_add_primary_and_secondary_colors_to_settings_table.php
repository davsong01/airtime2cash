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
        if (!Schema::hasColumns('settings', ['primary_color', 'secondary_color', 'active_color','block_header_color','dasboard_customer_details_color', 'active_hover_color'])) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('primary_color')->default('#fff'); // Text color
                $table->string('secondary_color')->default('#1a233a'); // Background color
                $table->string('active_color')->default('#FFB43C'); // Hover and active color
                $table->string('active_hover_color')->default('#5a8dee'); // Hover and active color
                $table->string('block_header_color')->default('#bac0c7'); // Hover and active color
                $table->string('dasboard_customer_details_color')->default('#fff'); // Hover and active color
                
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
                $table->dropColumn("primary_color");
                $table->dropColumn("secondary_color");
                $table->dropColumn("active_color");
                $table->dropColumn("active_hover_color");
                $table->dropColumn("block_header_color");
                $table->dropColumn("dasboard_customer_details_color");
            }); 
        }
    }
};