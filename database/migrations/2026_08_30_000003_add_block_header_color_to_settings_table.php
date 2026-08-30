<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! Schema::hasColumn('settings', 'block_header_color')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('block_header_color')->default('#286F70')->after('active_color');
            });
        }

        if (Schema::hasColumn('settings', 'text_header_color') && Schema::hasColumn('settings', 'block_header_color')) {
            DB::table('settings')
                ->whereNull('block_header_color')
                ->update([
                    'block_header_color' => DB::raw("COALESCE(text_header_color, '#286F70')"),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'block_header_color')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('block_header_color');
            });
        }
    }
};
