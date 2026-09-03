<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillApprovedGeneralKycStatusSeeder extends Seeder
{
    /**
     * Mark older customers as KYC verified without changing
     * any individual KYC field records.
     */
    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $statusColumn = 'kyc_status';

        if (! Schema::hasColumn('customers', $statusColumn)) {
            $this->command?->warn("Skipping backfill: customers.{$statusColumn} does not exist.");

            return;
        }

        DB::table('customers')
            ->whereNotNull('created_at')
            ->where('created_at', '<', now()->subDays(2))
            ->update([
                $statusColumn => 'verified',
            ]);
    }
}
