<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;

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

        $payment_gateways = PaymentGateway::all();

        return view('admin.settings', compact('settings', 'currencies', 'payment_gateways'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Settings $settings)
    {
        $settings = Settings::first();
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
