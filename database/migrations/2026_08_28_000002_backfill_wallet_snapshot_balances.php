<?php

use App\Services\WalletSnapshotBackfillService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(WalletSnapshotBackfillService::class)->backfill();
    }

    public function down(): void
    {
        // Data backfills are intentionally one-way.
    }
};
