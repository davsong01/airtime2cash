<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Providers\KingsVtuController;
use App\Models\API;
use App\Services\AutoSyncService;
use Illuminate\Http\Request;

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
            "slug" => "required|string|max:100",
            "file_name" => "nullable|string|max:255",
            "pricing_data_status" => "nullable|boolean",
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
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "file_name" => $request->file_name,
            "pricing_data_status" => $request->boolean('pricing_data_status'),
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
            "sandbox_base_url" => "nullable",
            "live_base_url" => "nullable",
            "slug" => "required|string|max:100",
            "file_name" => "nullable|string|max:255",
            "pricing_data_status" => "nullable|boolean",
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
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "file_name" => $request->file_name,
            "pricing_data_status" => $request->boolean('pricing_data_status'),
            "pricing_data" => $this->decodePricingBands($request->input('pricing_data')),
            "extra_charges" => $this->decodeExtraCharges($request->input('extra_charges')),
        ]);

        return back()->with('message', 'Updated successfully');
    }

    public function getBalance(API $api)
    {
        if($api->slug == 'autosync'){
            $res = app(AutoSyncService::class)->balance($api);
        }else{
            $res = app(KingsVtuController::class)->balance();
        }

        return response()->json($res);
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
