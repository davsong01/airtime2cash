<?php

namespace Database\Seeders;

use App\Models\API;
use Illuminate\Database\Seeder;

class AirtimeToCashProviderSeeder extends Seeder
{
    public function run(): void
    {
        $provider = API::firstOrNew(['slug' => 'airtimetocash']);

        $provider->fill([
            'name' => 'Airtime to Cash Automation',
            'slug' => 'airtimetocash',
            'description' => 'Airtime to Cash Automation API integration',
            'sandbox_base_url' => 'https://automation.airtimetocash.com',
            'live_base_url' => 'https://automation.airtimetocash.com',
            'is_auto_share' => true,
        ]);

        if (! $provider->exists) {
            $provider->status = 'active';
            $provider->warning_threshold_status = 'inactive';
            $provider->balance = 0;
        }

        $provider->save();
    }
}
