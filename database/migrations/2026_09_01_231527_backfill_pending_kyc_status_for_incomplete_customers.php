<?php

use App\Models\Customer;
use App\Models\KycData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $reviewableFields = ['FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'DOB', 'BVN', 'IDCARDTYPE', 'IDCARD', 'PHONE_NUMBER'];

        Customer::query()
            ->select(['id', 'kyc_status', 'kyc_rejection_reason'])
            ->whereIn('kyc_status', ['verified', 'awaiting-approval', 'pending'])
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($reviewableFields) {
                $kycRows = KycData::query()
                    ->whereIn('customer_id', $customers->pluck('id'))
                    ->whereIn('key', $reviewableFields)
                    ->get()
                    ->groupBy('customer_id');

                foreach ($customers as $customer) {
                    $customerRows = $kycRows->get($customer->id, collect());

                    $hasIncompleteField = collect($reviewableFields)->contains(function (string $field) use ($customerRows): bool {
                        $fieldRow = $customerRows->firstWhere('key', $field);

                        return ! $fieldRow || data_get($fieldRow, 'status') !== 'verified';
                    });

                    if (! $hasIncompleteField) {
                        continue;
                    }

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'kyc_status' => 'pending',
                            'kyc_rejection_reason' => null,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // This migration only backfills production state and cannot be
        // reversed safely without a full history snapshot.
    }
};
