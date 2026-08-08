<?php

namespace Database\Seeders;

use App\Http\Controllers\Controller;
use App\Models\API;
use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $provider = API::query()
            ->where('slug', 'sagecloud')
            ->where('status', 'active')
            ->first();

        if (! $provider) {
            $this->command?->warn('SageCloud provider not found. Bank seeding skipped.');

            return;
        }

        $loginUrl = rtrim($provider->live_base_url, '/').'/merchant/authorization';

        $control = new Controller();

        $login = $control->basicApiCall(
            $loginUrl,
            [
                'email' => $provider->api_key,
                'password' => $provider->secret_key,
            ],
            []
        );

        if (empty($login) || ($login['success'] ?? false) !== true) {
            $this->command?->warn('Unable to authenticate with SageCloud. Bank seeding skipped.');

            return;
        }

        $token = data_get($login, 'data.token.access_token');

        if (blank($token)) {
            $this->command?->warn('SageCloud did not return an access token.');

            return;
        }

        $url = rtrim($provider->live_base_url, '/').'/transfer/get-transfer-data';

        $response = $control->basicApiCall(
            $url,
            [],
            [
                'Content-Type: application/json',
                'Authorization: Bearer '.$token,
            ],
            'GET'
        );

        if (empty($response) || ($response['success'] ?? false) !== true) {
            $this->command?->warn('Unable to fetch banks from SageCloud.');

            return;
        }

        foreach ($response['banks'] ?? [] as $bank) {
            Bank::updateOrCreate(
                ['cbn_code' => $bank['cbn_code']],
                ['bank_name' => $bank['bank_name']]
            );
        }

        $this->command?->info('Banks seeded successfully.');
    }
}
