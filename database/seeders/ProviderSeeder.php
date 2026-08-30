<?php

namespace Database\Seeders;

use App\Models\API;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Monnify',
                'slug' => 'monnify',
                'sandbox_base_url' => 'https://sandbox.monnify.com',
                'live_base_url' => 'https://api.monnify.com',
                'is_bank_transfer' => true,
                'is_bank_verification' => true,
                'is_bvn_verification' => true,
                'is_payment_gateway' => true,
            ],
            [
                'name' => 'Paystack',
                'slug' => 'paystack',
                'sandbox_base_url' => 'https://api.paystack.co',
                'live_base_url' => 'https://api.paystack.co',
                'is_bank_transfer' => true,
                'is_bank_verification' => true,
                'is_payment_gateway' => true,
            ],
            [
                'name' => 'Kora',
                'slug' => 'kora',
                'sandbox_base_url' => 'https://api.korapay.com/merchant',
                'live_base_url' => 'https://api.korapay.com/merchant',
                'is_bank_transfer' => true,
                'is_bank_verification' => true,
            ],
            [
                'name' => 'SageCloud',
                'slug' => 'sagecloud',
                'sandbox_base_url' => env('SAGE_BASE_URL'),
                'live_base_url' => env('SAGE_BASE_URL'),
                'is_bank_transfer' => true,
                'is_bank_verification' => true,
            ],
            [
                'name' => 'AutoSync',
                'slug' => 'autosync',
                'sandbox_base_url' => 'https://autosyncng.com/api/v1',
                'live_base_url' => 'https://autosyncng.com/api/v1',
                'is_auto_share' => true,
            ],
        ];

        foreach ($providers as $provider) {
            API::updateOrCreate(
                ['slug' => $provider['slug']],
                array_merge($provider, [
                    'status' => 'active',
                    'warning_threshold_status' => 'inactive',
                    'balance' => 0,
                ])
            );
        }
    }
}
