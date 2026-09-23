<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) return;

        foreach ([
            'bank_transfer_destination_auto_status',
            'bank_transfer_destination_manual_status',
            'wallet_transfer_destination_auto_status',
            'wallet_transfer_destination_manual_status',
        ] as $column) {
            if (! Schema::hasColumn('settings', $column)) {
                Schema::table('settings', fn (Blueprint $table) => $table->string($column)->nullable());
            }
        }

        DB::table('settings')->update([
            'bank_transfer_destination_auto_status' => 'enabled',
            'bank_transfer_destination_manual_status' => 'enabled',
            'wallet_transfer_destination_auto_status' => 'enabled',
            'wallet_transfer_destination_manual_status' => 'enabled',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) return;

        $columns = collect([
            'bank_transfer_destination_auto_status',
            'bank_transfer_destination_manual_status',
            'wallet_transfer_destination_auto_status',
            'wallet_transfer_destination_manual_status',
        ])
            ->filter(fn (string $column) => Schema::hasColumn('settings', $column))->all();
        if ($columns) Schema::table('settings', fn (Blueprint $table) => $table->dropColumn($columns));
    }
};
