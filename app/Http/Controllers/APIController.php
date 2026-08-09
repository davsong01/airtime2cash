<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Providers\KingsVtuController;
use App\Models\API;
use App\Models\Bank;
use App\Services\AutoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class APIController extends Controller
{
    public function index()
    {
        $apis = API::withCount('products')->get();
        return view('admin.api.index', compact('apis'));
    }

    public function create()
    {
        return view('admin.api.edit');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "warning_threshold_status" => "nullable",
            "warning_threshold" => "nullable",
            "status" => "required",
            "api_key" => "nullable",
            "sandbox_base_url" => "nullable",
            "live_base_url" => "nullable",
            "secret_key" => "nullable",
            "public_key" => "nullable",
            "account_number" => "nullable|string|max:255",
            "slug" => "required|string|max:100",
            "pricing_data_status" => "nullable|boolean",
            "is_bank_transfer" => "nullable|boolean",
            "is_bank_verification" => "nullable|boolean",
            "is_auto_share" => "nullable|boolean",
            "is_payment_gateway" => "nullable|boolean",
            "pricing_data" => "nullable|string",
            "extra_charges" => "nullable|string",
        ]);

        API::create([
            "name" => $request->name,
            "warning_threshold_status" => $request->warning_threshold_status,
            "warning_threshold" => $request->warning_threshold,
            "status" => $request->status,
            "api_key" => $request->api_key,
            "secret_key" => $request->secret_key,
            "public_key" => $request->public_key,
            "account_number" => $request->account_number,
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "pricing_data_status" => $request->boolean('pricing_data_status'),
            "is_bank_transfer" => $request->boolean('is_bank_transfer'),
            "is_bank_verification" => $request->boolean('is_bank_verification'),
            "is_auto_share" => $request->boolean('is_auto_share'),
            "is_payment_gateway" => $request->boolean('is_payment_gateway'),
            "pricing_data" => $this->decodePricingBands($request->input('pricing_data')),
            "extra_charges" => $this->decodeExtraCharges($request->input('extra_charges')),
        ]);

        return redirect(route('api.index'))->with('message', 'Added successfully');
    }

    public function edit(API $api)
    {
        return view('admin.api.edit', compact('api'));
    }

    public function update(Request $request, API $api)
    {
        $this->validate($request, [
            "name" => "required",
            "warning_threshold_status" => "nullable",
            "warning_threshold" => "nullable",
            "status" => "required",
            "api_key" => "nullable",
            "secret_key" => "nullable",
            "public_key" => "nullable",
            "account_number" => "nullable|string|max:255",
            "sandbox_base_url" => "nullable",
            "live_base_url" => "nullable",
            "slug" => "required|string|max:100",
            "pricing_data_status" => "nullable|boolean",
            "is_bank_transfer" => "nullable|boolean",
            "is_bank_verification" => "nullable|boolean",
            "is_auto_share" => "nullable|boolean",
            "is_payment_gateway" => "nullable|boolean",
            "pricing_data" => "nullable|string",
            "extra_charges" => "nullable|string",
        ]);

        $api->update([
            "name" => $request->name,
            "warning_threshold_status" => $request->warning_threshold_status,
            "warning_threshold" => $request->warning_threshold,
            "status" => $request->status,
            "api_key" => $request->api_key,
            "secret_key" => $request->secret_key,
            "public_key" => $request->public_key,
            "account_number" => $request->account_number,
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "pricing_data_status" => $request->boolean('pricing_data_status'),
            "is_bank_transfer" => $request->boolean('is_bank_transfer'),
            "is_bank_verification" => $request->boolean('is_bank_verification'),
            "is_auto_share" => $request->boolean('is_auto_share'),
            "is_payment_gateway" => $request->boolean('is_payment_gateway'),
            "pricing_data" => $this->decodePricingBands($request->input('pricing_data')),
            "extra_charges" => $this->decodeExtraCharges($request->input('extra_charges')),
        ]);

        return back()->with('message', 'Updated successfully');
    }

    public function getBalance(API $api)
    {
        try {
            $controller = resolveProviderController($api);

            if ($controller && method_exists($controller, 'balance')) {
                $res = $controller->balance();
            } elseif ($api->slug === 'autosync') {
                $res = app(AutoSyncService::class)->balance($api);
            } else {
                $res = app(KingsVtuController::class)->balance(null, $api);
            }

            if (($res['status'] ?? null) === 'success' && array_key_exists('balance', $res)) {
                $api->update(['balance' => $res['balance']]);
            } else {
                Log::warning('Provider balance check failed.', [
                    'api_id' => $api->id,
                    'slug' => $api->slug,
                    'name' => $api->name,
                    'response' => $res,
                ]);
            }

            return response()->json($res);
        } catch (Throwable $e) {
            Log::error('Provider balance check threw an exception.', [
                'api_id' => $api->id,
                'slug' => $api->slug,
                'name' => $api->name,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Unable to fetch provider balance at the moment.',
            ], 422);
        }
    }

    public function pullBanks(API $api)
    {
        if (! $api->is_bank_transfer && ! $api->is_bank_verification) {
            return response()->json([
                'status' => false,
                'message' => 'This provider is not configured for bank syncing.',
            ], 422);
        }

        $controller = resolveProviderController($api);

        if (! $controller || ! method_exists($controller, 'pullBanks')) {
            return response()->json([
                'status' => false,
                'message' => 'This provider does not support bank syncing.',
            ], 422);
        }

        $response = $controller->pullBanks();
        $banks = data_get($response, 'data', data_get($response, 'banks', []));

        if (! is_array($banks) && is_array($response) && array_is_list($response)) {
            $banks = $response;
        }

        if (is_array($banks) && ! array_is_list($banks) && isset($banks['banks']) && is_array($banks['banks'])) {
            $banks = $banks['banks'];
        }

        if (! is_array($banks)) {
            return response()->json([
                'status' => false,
                'message' => 'The provider did not return a valid bank list.',
                'response' => $response,
            ], 422);
        }

        $syncedCount = 0;

        foreach ($banks as $bankData) {
            if (! is_array($bankData)) {
                continue;
            }

            if ($this->bankSupportsTransfer($bankData) === false) {
                continue;
            }

            $cbnCode = $bankData['cbn_code'] ?? $bankData['code'] ?? null;

            if (blank($cbnCode)) {
                continue;
            }

            $providerCode = $bankData['provider_code'] ?? $cbnCode;
            $bank = Bank::query()->where('cbn_code', $cbnCode)->first();

            if (! $bank) {
                Bank::create([
                    'bank_name' => $bankData['bank_name'] ?? $bankData['name'] ?? null,
                    'cbn_code' => $cbnCode,
                    'status' => 'active',
                    'provider_codes' => [
                        $api->slug => $providerCode,
                    ],
                    'provider_meta' => $bankData['provider_meta'] ?? [],
                ]);
                $syncedCount++;
                continue;
            }

            $codes = is_array($bank->provider_codes ?? null) ? $bank->provider_codes : [];

            if (blank($codes[$api->slug] ?? null)) {
                $codes[$api->slug] = $providerCode;
                $bank->update([
                    'provider_codes' => $codes,
                ]);
                $syncedCount++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => $syncedCount . ' bank' . ($syncedCount === 1 ? '' : 's') . ' pulled',
            'count' => $syncedCount,
        ]);
    }

    private function decodePricingBands(?string $pricingData): array
    {
        if (blank($pricingData)) {
            return [];
        }

        $decoded = json_decode($pricingData, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];

        foreach ($decoded as $band) {
            if (!is_array($band)) {
                continue;
            }

            $cleanBand = [
                'band_name' => trim((string) ($band['band_name'] ?? $band['name'] ?? '')),
                'min_amount' => $band['min_amount'] ?? '',
                'max_amount' => $band['max_amount'] ?? '',
                'provider_fee' => $band['provider_fee'] ?? '',
                'extra_charge' => $band['extra_charge'] ?? '',
                'extra_charges' => [],
            ];

            $bandExtraCharges = $band['extra_charges'] ?? [];
            if (is_array($bandExtraCharges)) {
                foreach ($bandExtraCharges as $charge) {
                    if (!is_array($charge)) {
                        continue;
                    }

                    $cleanCharge = [
                        'charge_name' => trim((string) ($charge['charge_name'] ?? $charge['name'] ?? '')),
                        'value' => $charge['value'] ?? '',
                    ];

                    $hasValue = collect($cleanCharge)->contains(fn ($value) => filled($value));

                    if ($hasValue) {
                        $cleanBand['extra_charges'][] = $cleanCharge;
                    }
                }
            }

            $hasValue = collect($cleanBand)->contains(fn ($value) => filled($value));

            if ($hasValue) {
                $normalized[] = $cleanBand;
            }
        }

        return array_values($normalized);
    }

    private function bankSupportsTransfer(array $bankData): ?bool
    {
        $flags = [
            $bankData['supports_transfer'] ?? null,
            $bankData['supportsTransfer'] ?? null,
            $bankData['transfer_enabled'] ?? null,
            $bankData['transferEnabled'] ?? null,
        ];

        foreach ($flags as $flag) {
            if ($flag === null) {
                continue;
            }

            return filter_var($flag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    private function decodeExtraCharges(?string $extraCharges): array
    {
        if (blank($extraCharges)) {
            return [];
        }

        $decoded = json_decode($extraCharges, true);

        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];

        foreach ($decoded as $charge) {
            if (!is_array($charge)) {
                continue;
            }

            $cleanCharge = [
                'charge_name' => trim((string) ($charge['charge_name'] ?? $charge['name'] ?? '')),
                'value' => $charge['value'] ?? '',
            ];

            $hasValue = collect($cleanCharge)->contains(fn ($value) => filled($value));

            if ($hasValue) {
                $normalized[] = $cleanCharge;
            }
        }

        return array_values($normalized);
    }
}
