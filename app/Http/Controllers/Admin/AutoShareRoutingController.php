<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Settings;
use App\Services\AutoShareRoutingService;
use Illuminate\Http\Request;
use RuntimeException;

class AutoShareRoutingController extends Controller
{
    public function __construct(private readonly AutoShareRoutingService $routingService)
    {
    }

    public function index()
    {
        return view('admin.auto-share-routing.verify', [
            'networks' => $this->networks(),
            'result' => null,
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'network' => ['required', 'integer', 'exists:products,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $network = Product::query()
            ->whereKey($validated['network'])
            ->where('type', 'airtime2cash')
            ->where('status', 'active')
            ->first();

        if (! $network) {
            return back()->withInput()->withErrors([
                'network' => 'Select an active Airtime to Cash network.',
            ]);
        }

        try {
            $conversionRate = (float) ($network->auto_share_rate ?? $network->rate ?? 0);
            $conversionCharge = round(((float) $validated['amount'] * $conversionRate) / 100, 2);
            $decision = $this->routingService->selectProvider(
                (float) $validated['amount'],
                $network->auto_share_product_code ?: $network->slug,
                true,
                $conversionCharge
            );

            $providerCharges = collect(data_get($decision, 'meta.selected_charges', []))
                ->filter(fn ($charge) => is_array($charge))
                ->values()
                ->all();
            $providerRoutingFee = round(collect($providerCharges)->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)), 2);

            $result = [
                'request' => [
                    'network_id' => $network->id,
                    'network' => $network->name,
                    'product_code' => $network->auto_share_product_code,
                    'amount' => (float) $validated['amount'],
                ],
                'routing_mode' => $decision['mode'],
                'selected_provider' => [
                    'id' => $decision['provider_id'],
                    'name' => $decision['provider']->name,
                    'slug' => $decision['provider']->slug,
                    'status' => $decision['provider']->status,
                ],
                'reason' => $decision['reason'],
                'routing' => $decision['meta'],
                'charges' => [
                    'product' => [
                        'label' => 'Airtime conversion charge',
                        'rate' => $conversionRate,
                        'amount' => $conversionCharge,
                    ],
                    'provider' => $providerCharges,
                    'provider_routing_fee' => $providerRoutingFee,
                    'visible_charge_total' => round($conversionCharge + $providerRoutingFee, 2),
                ],
            ];

            return view('admin.auto-share-routing.verify', [
                'networks' => $this->networks(),
                'result' => $result,
            ]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors([
                'amount' => $exception->getMessage(),
            ]);
        }
    }

    public function switchToAuto()
    {
        $settings = Settings::first();

        if (! $settings) {
            return back()->with('error', 'Application settings could not be found.');
        }

        $settings->update(['auto_share_routing_mode' => 'auto']);

        return redirect()
            ->route('admin.auto-share.routing.verify')
            ->with('message', 'Auto Share routing mode is now set to Auto.');
    }

    private function networks()
    {
        return Product::query()
            ->where('type', 'airtime2cash')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'auto_share_product_code']);
    }
}
