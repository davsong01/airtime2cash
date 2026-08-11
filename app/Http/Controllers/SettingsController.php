<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use App\Models\API;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Settings $settings)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Settings $settings)
    {
        $settings = Settings::first();
        if (!$settings) {
            $settings = Settings::create([
                'logo' => '',
                'favicon' => '',
                'currency' => '&#8358;',
                'admin_layout' => 'legacy',
                'customer_layout' => 'legacy',
                'official_email' => '',
                'whatsapp_number' => '',
                'google_ad_code' => '',
                'google_dashboard_ad_enabled' => true,
                'show_provider_status_on_customer_pages' => true,
                'seo_title' => '',
                'seo_description' => '',
                'support_link' => '',
                'telegram_number' => '',
                'a2cash_chat_engine' => '',
            ]);
        }

        $currencies = [
            '₦',
            '$'
        ];

        $paymentGatewayProviders = API::query()
            ->where('is_payment_gateway', true)
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'slug']);
        $autoShareProviders = API::query()
            ->where('is_auto_share', true)
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'slug']);
        $bankTransferProviders = API::query()
            ->where('is_bank_transfer', true)
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'slug']);
        $bankVerificationProviders = API::query()
            ->where('is_bank_verification', true)
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'slug']);

        return view('admin.settings', compact('settings', 'currencies', 'paymentGatewayProviders', 'autoShareProviders', 'bankTransferProviders', 'bankVerificationProviders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Settings $settings)
    {
        $settings = Settings::first();
        $colorFields = [
            'menu_text_color',
            'menu_background_color',
            'active_color',
            'block_header_color',
            'dasboard_customer_details_color',
        ];

        $request->validate(collect($colorFields)
            ->mapWithKeys(fn (string $field) => [$field => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']])
            ->all() + [
                'auto_share_provider_id' => ['nullable', 'integer', 'exists:apis,id'],
                'bank_transfer_provider_id' => ['required', 'integer', 'exists:apis,id'],
                'bank_verification_provider_id' => ['nullable', 'integer', 'exists:apis,id'],
                'show_provider_status_on_customer_pages' => ['nullable', 'boolean'],

            ]);

        // captcha settings
        $captcha_settings = [
            'captcha_settings_status' => $request->captcha_settings_status,
            'captcha_settings_provider' => $request->captcha_settings_provider,
            'google' => [
                'RECAPTCHA_SITE_KEY' => $request->RECAPTCHA_SITE_KEY,
                'RECAPTCHA_SECRET_KEY' => $request->RECAPTCHA_SECRET_KEY
            ],
        ];

        $data = $request->except(['_token', 'logo', 'favicon', 'ip','captcha_settings_status', 'captcha_settings_provider', 'RECAPTCHA_SITE_KEY', 'RECAPTCHA_SECRET_KEY']);

        $adminLayout = $request->input('admin_layout', $settings->admin_layout ?? 'legacy');
        $customerLayout = $request->input('customer_layout', $settings->customer_layout ?? 'legacy');

        $data['admin_layout'] = in_array($adminLayout, ['legacy'], true) ? $adminLayout : 'legacy';
        $data['customer_layout'] = in_array($customerLayout, ['legacy', 'modern'], true) ? $customerLayout : 'legacy';
        $data['google_dashboard_ad_enabled'] = $request->boolean('google_dashboard_ad_enabled');
        $data['show_provider_status_on_customer_pages'] = $request->boolean('show_provider_status_on_customer_pages');

        foreach ($colorFields as $field) {
            $data[$field] = strtoupper($request->string($field)->toString());
        }

        $data['captcha_settings'] = $captcha_settings;

        if (!empty($request->logo)) {
            $data['logo'] = $this->uploadFile($request->logo, 'site');
        }

        if (!empty($request->dashboard_logo)) {
            $data['dashboard_logo'] = $this->uploadFile($request->dashboard_logo, 'site');
        }

        if (!empty($request->favicon)) {
            $data['favicon'] = $this->uploadFile($request->favicon, 'site');
        }

        $settings->update($data);

        return back()->with('message', 'Operation successful');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Settings $settings)
    {
        //
    }
}
