<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Customer::query()
            ->whereNotNull('bvn_data')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    $bvnData = (array) ($customer->bvn_data ?? []);

                    if (blank(data_get($bvnData, 'verification_mode'))) {
                        $bvnData['verification_mode'] = 'manual';

                        DB::table('customers')
                            ->where('id', $customer->id)
                            ->update([
                                'bvn_data' => json_encode($bvnData),
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Customer::query()
            ->whereNotNull('bvn_data')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    $bvnData = (array) ($customer->bvn_data ?? []);

                    if (array_key_exists('verification_mode', $bvnData) && $bvnData['verification_mode'] === 'manual') {
                        unset($bvnData['verification_mode']);

                        DB::table('customers')
                            ->where('id', $customer->id)
                            ->update([
                                'bvn_data' => json_encode($bvnData),
                            ]);
                    }
                }
            });
    }
};
