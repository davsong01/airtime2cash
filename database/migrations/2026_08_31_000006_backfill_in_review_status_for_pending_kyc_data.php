<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kyc_data')
            ->join('customers', 'customers.id', '=', 'kyc_data.customer_id')
            ->whereIn('customers.kyc_status', ['awaiting-approval', 'pending'])
            ->where('kyc_data.status', 'unverified')
            ->whereNotNull('kyc_data.value')
            ->where('kyc_data.value', '!=', '')
            ->update([
                'kyc_data.status' => 'in-review',
                'kyc_data.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('kyc_data')
            ->join('customers', 'customers.id', '=', 'kyc_data.customer_id')
            ->whereIn('customers.kyc_status', ['awaiting-approval', 'pending'])
            ->where('kyc_data.status', 'in-review')
            ->whereNotNull('kyc_data.value')
            ->where('kyc_data.value', '!=', '')
            ->update([
                'kyc_data.status' => 'unverified',
                'kyc_data.updated_at' => now(),
            ]);
    }
};
