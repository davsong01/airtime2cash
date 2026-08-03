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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('telegram_username')->nullable()->after('whatsapp_number');
            $table->string('a2cash_chat_engine')->default('whatsapp')->after('telegram_username');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('telegram_username');
            $table->dropColumn('a2cash_chat_engine');
        });
    }
};
