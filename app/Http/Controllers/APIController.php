<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Providers\KingsVtuController;
use App\Models\API;
use App\Models\Bank;
use App\Services\ApiAvailabilityMonitorService;
use App\Services\AutoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class APIController extends Controller
{
    public function index()
    {
        $apis = API::withCount('products')->get();
        $availabilityScores = $apis->pluck('availability_score')->filter(fn ($score) => $score !== null);
        $lastCheckedAt = $apis->pluck('availability_checked_at')->filter()->sortDesc()->first();
        $monitorUrl = route('cron.api-availability-monitor', [
            'windowMinutes' => 60,
            'sampleSize' => 20,
        ]);

        $monitorToken = trim((string) env('API_AVAILABILITY_MONITOR_TOKEN', ''));

        if ($monitorToken !== '') {
            $monitorUrl .= (str_contains($monitorUrl, '?') ? '&' : '?') . http_build_query([
                'token' => $monitorToken,
            ]);
        }

        $availabilitySummary = [
            'providers' => $apis->count(),
            'checked_providers' => $apis->whereNotNull('availability_checked_at')->count(),
            'healthy_providers' => $apis->filter(fn (API $api) => in_array($api->availability_status_class, ['stable', 'healthy'], true))->count(),
            'average_score' => $availabilityScores->isNotEmpty() ? (int) round($availabilityScores->avg()) : null,
            'availability_check_transactions_count' => $apis->sum(fn (API $api) => (int) ($api->availability_check_transactions_count ?? 0)),
            'successful_transactions' => $apis->sum(fn (API $api) => (int) ($api->successful_transactions ?? 0)),
            'failed_transactions' => $apis->sum(fn (API $api) => (int) ($api->failed_transactions ?? 0)),
            'last_checked_at' => $lastCheckedAt,
        ];

        return view('admin.api.index', compact('apis', 'availabilitySummary', 'monitorUrl'));
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
            "contract_id" => "nullable|string|max:255",
            "charge" => "nullable|numeric|min:0",
            "reserved_account_payment_charge" => "nullable|numeric|min:0",
            "reserved_account_payment_charge_type" => "nullable|in:flat,percentage",
            "pending_note" => "nullable|string|max:5000",
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
            "contract_id" => $request->contract_id,
            "charge" => $request->charge,
            "reserved_account_payment_charge" => $request->reserved_account_payment_charge,
            "reserved_account_payment_charge_type" => $request->reserved_account_payment_charge_type,
            "pending_note" => $request->pending_note,
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
            "contract_id" => "nullable|string|max:255",
            "charge" => "nullable|numeric|min:0",
            "reserved_account_payment_charge" => "nullable|numeric|min:0",
            "reserved_account_payment_charge_type" => "nullable|in:flat,percentage",
            "pending_note" => "nullable|string|max:5000",
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
            "contract_id" => $request->contract_id,
            "charge" => $request->charge,
            "reserved_account_payment_charge" => $request->reserved_account_payment_charge,
            "reserved_account_payment_charge_type" => $request->reserved_account_payment_charge_type,
            "pending_note" => $request->pending_note,
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
        $banks = data_get($response, 'banks', data_get($response, 'data', []));

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

        $fetchedCount = count($banks);
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

            $providerCodes = is_array($bankData['provider_codes'] ?? null) ? $bankData['provider_codes'] : [];
            $providerCode = $providerCodes[$api->slug] ?? $bankData['provider_code'] ?? $cbnCode;
            $bank = Bank::query()->where('cbn_code', $cbnCode)->first();

            if (! $bank) {
                Bank::create([
                    'bank_name' => $bankData['bank_name'] ?? $bankData['name'] ?? null,
                    'cbn_code' => $cbnCode,
                    'status' => 'active',
                    'provider_codes' => array_filter(array_merge($providerCodes, [
                        $api->slug => $providerCode,
                    ]), fn ($value) => filled($value)),
                    'provider_meta' => $bankData['provider_meta'] ?? [],
                ]);
                $syncedCount++;
                continue;
            }

            $codes = is_array($bank->provider_codes ?? null) ? $bank->provider_codes : [];
            $mergedCodes = array_filter(array_merge($codes, $providerCodes, [
                $api->slug => $providerCode,
            ]), fn ($value) => filled($value));

            if ($mergedCodes !== $codes) {
                $bank->update([
                    'provider_codes' => $mergedCodes,
                ]);
                $syncedCount++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => $fetchedCount . ' bank' . ($fetchedCount === 1 ? '' : 's') . ' pulled',
            'count' => $fetchedCount,
            'synced_count' => $syncedCount,
        ]);
    }

    public function monitorAvailability(Request $request, ApiAvailabilityMonitorService $monitor)
    {
        $expectedToken = (string) env('API_AVAILABILITY_MONITOR_TOKEN', '');
        $providedToken = (string) $request->query('token', '');

        if ($expectedToken !== '' && ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized.',
            ], 403);
        }

        $windowMinutes = max(1, (int) $request->route('windowMinutes', 60));
        $sampleSize = max(1, (int) $request->route('sampleSize', 20));

        try {
            return response()->json(
                $monitor->run($windowMinutes, $sampleSize)
            );
        } catch (Throwable $e) {
            Log::error('API availability monitor failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Unable to complete availability monitor right now.',
            ], 500);
        }
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
