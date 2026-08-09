<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['webhooks', 'autosync_webhooks'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'attempts')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropColumn('attempts');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['webhooks', 'autosync_webhooks'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'attempts')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->unsignedSmallInteger('attempts')->default(0);
                });
            }
        }
    }
};
