@extends('layouts.app')
@section('page-css')
    <style>
        .settings-image-preview {
            display: flex;
            width: 100%;
            min-width: 0;
            height: 76px;
            padding: .5rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #d9dfe7;
            border-radius: .35rem;
            background: #f8f9fa;
        }

        .settings-image-preview--dashboard {
            height: 96px;
        }

        .settings-image-preview img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .theme-color-control {
            display: flex;
            align-items: stretch;
            gap: .625rem;
        }

        .theme-color-swatch {
            flex: 0 0 48px;
            width: 48px;
            height: 38px;
            padding: .2rem;
            cursor: pointer;
            border: 1px solid #d9dfe7;
            border-radius: .35rem;
            background: #fff;
        }

        .theme-color-control .input-group {
            min-width: 0;
        }

        .theme-color-hex {
            min-width: 0;
            font-family: monospace;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .theme-color-copy {
            min-width: 68px;
        }

        .settings-section-stack > .card {
            margin-bottom: .85rem;
        }

        .settings-section-stack > .card:last-child {
            margin-bottom: 0;
        }

        .settings-section-stack .card-header {
            padding: .95rem 1.25rem .7rem;
        }

        .settings-section-stack .card-body {
            padding: 1rem 1.25rem 1.15rem;
        }

        .settings-section-stack .card-header .card-title {
            margin-bottom: .1rem;
            font-size: 1rem;
            line-height: 1.2;
        }

        .settings-section-stack .card-header small {
            font-size: .8rem;
            line-height: 1.35;
        }

        .settings-save-card .card-body {
            padding: .85rem 1.25rem;
        }

        .settings-save-card .btn {
            padding: .6rem 1.2rem;
            font-size: .92rem;
            border-radius: .45rem;
        }

        .settings-save-card .btn i {
            margin-right: .35rem;
        }

        @media (max-width: 767.98px) {
            .settings-image-preview {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection
@section('content')
<!-- Content wrapper -->
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <!-- Basic Inputs start -->
            <section id="basic-input">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="content-body">
                                <!-- Nav Filled Starts -->
                                <section id="nav-filled">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h4 class="card-title">App Settings</h4>
                                                    @include('layouts.alerts')
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                       <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="settings-section-stack">

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">General Settings</h4>
                                                                    <small class="text-muted">Core platform preferences and general configuration.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="official_email">Official Email</label>
                                                                                <input type="text" class="form-control" id="official_email" name="official_email" value="{{ old('official_email', $settings->official_email) }}" placeholder="Official email">
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="currency">Currency</label>
                                                                                <select name="currency" class="form-control" id="currency" required>
                                                                                    <option value="">Select</option>
                                                                                    @foreach($currencies as $currency)
                                                                                        <option value="{{ $currency }}" @selected($currency == old('currency', $settings->currency))>{!! $currency !!}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="customer_layout">Customer Layout</label>
                                                                                <select name="customer_layout" class="form-control" id="customer_layout">
                                                                                    <option value="legacy" @selected(old('customer_layout', $settings->customer_layout ?? 'legacy') === 'legacy')>Legacy Layout</option>
                                                                                    <option value="modern" @selected(old('customer_layout', $settings->customer_layout ?? 'legacy') === 'modern')>Modern Layout</option>
                                                                                </select>
                                                                                <small class="text-muted d-block mt-50">Customer pages will resolve through the template engine.</small>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="admin_layout">Admin Layout</label>
                                                                                <select name="admin_layout" class="form-control" id="admin_layout">
                                                                                    <option value="legacy" @selected(old('admin_layout', $settings->admin_layout ?? 'legacy') === 'legacy')>Legacy Layout</option>
                                                                                </select>
                                                                                <small class="text-muted d-block mt-50">Admin stays on the legacy shell for now.</small>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="support_link">Support Link</label>
                                                                                <input type="text" class="form-control" id="support_link" name="support_link" value="{{ old('support_link', $settings->support_link) }}" placeholder="Support Link">
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Provider Configuration</h4>
                                                                    <small class="text-muted">Select the providers used for automated platform operations.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="auto_share_provider_id">Auto Share Provider</label>
                                                                                <select name="auto_share_provider_id" class="form-control @error('auto_share_provider_id') is-invalid @enderror" id="auto_share_provider_id">
                                                                                    <option value="">Disable Auto Share integration</option>
                                                                                    @foreach($autoShareProviders as $provider)
                                                                                        <option value="{{ $provider->id }}" @selected((string) old('auto_share_provider_id', $settings->auto_share_provider_id) === (string) $provider->id)>
                                                                                            {{ $provider->name }}{{ $provider->status !== 'active' ? ' (Inactive)' : '' }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                                @error('auto_share_provider_id')
                                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                                @enderror
                                                                                <small class="text-muted d-block mt-50">Provider used for automatic airtime transfers.</small>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="bank_transfer_provider_id">Bank Transfer Provider</label>
                                                                                <select name="bank_transfer_provider_id" class="form-control @error('bank_transfer_provider_id') is-invalid @enderror" id="bank_transfer_provider_id">
                                                                                    <option value="">Select provider</option>
                                                                                    @foreach($bankTransferProviders as $provider)
                                                                                        <option value="{{ $provider->id }}" @selected((string) old('bank_transfer_provider_id', $settings->bank_transfer_provider_id) === (string) $provider->id)>
                                                                                            {{ $provider->name }}{{ $provider->status !== 'active' ? ' (Inactive)' : '' }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                                @error('bank_transfer_provider_id')
                                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                                @enderror
                                                                                <small class="text-muted d-block mt-50">Provider used for admin bank transfers and customer wallet-to-bank transfers.</small>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="bank_verification_provider_id">Bank Verification Provider</label>
                                                                                <select name="bank_verification_provider_id" class="form-control @error('bank_verification_provider_id') is-invalid @enderror" id="bank_verification_provider_id">
                                                                                    <option value="">Use bank transfer provider</option>
                                                                                    @foreach($bankVerificationProviders as $provider)
                                                                                        <option value="{{ $provider->id }}" @selected((string) old('bank_verification_provider_id', $settings->bank_verification_provider_id) === (string) $provider->id)>
                                                                                            {{ $provider->name }}{{ $provider->status !== 'active' ? ' (Inactive)' : '' }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                                @error('bank_verification_provider_id')
                                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                                @enderror
                                                                                <small class="text-muted d-block mt-50">Provider used to verify customer bank details before transfer.</small>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row mt-1">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="payment_gateway">Payment Gateway Provider</label>
                                                                                <select name="payment_gateway" class="form-control" id="payment_gateway" required>
                                                                                    <option value="">Select</option>
                                                                                    @foreach($paymentGatewayProviders as $gateway)
                                                                                        <option value="{{ $gateway->slug }}" @selected((string) $gateway->slug === (string) old('payment_gateway', $settings->payment_gateway))>
                                                                                           {{ $gateway->name }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Wallet & Payments</h4>
                                                                    <small class="text-muted">Configure wallet funding methods and related charges.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="allow_fund_with_card">Allow Wallet Funding with Card</label>
                                                                                <select name="allow_fund_with_card" class="form-control" id="allow_fund_with_card">
                                                                                    <option value="">Select</option>
                                                                                    <option value="yes" @selected(old('allow_fund_with_card', $settings->allow_fund_with_card) === 'yes')>Yes</option>
                                                                                    <option value="no" @selected(old('allow_fund_with_card', $settings->allow_fund_with_card) === 'no')>No</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="card_funding_extra_charge">Card Funding Extra Charge ({!! $settings->currency !!})</label>
                                                                                <input type="number" name="card_funding_extra_charge" id="card_funding_extra_charge" value="{{ old('card_funding_extra_charge', $settings->card_funding_extra_charge) }}" class="form-control">
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="allow_fund_with_reserved_account">Allow Wallet Funding with Reserved Account</label>
                                                                                <select name="allow_fund_with_reserved_account" class="form-control" id="allow_fund_with_reserved_account">
                                                                                    <option value="">Select</option>
                                                                                    <option value="yes" @selected(old('allow_fund_with_reserved_account', $settings->allow_fund_with_reserved_account) === 'yes')>Yes</option>
                                                                                    <option value="no" @selected(old('allow_fund_with_reserved_account', $settings->allow_fund_with_reserved_account) === 'no')>No</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Communication</h4>
                                                                    <small class="text-muted">Configure customer communication and airtime-to-cash support channels.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="a2cash_chat_engine">Airtime2Cash Chat Engine</label>
                                                                                <select name="a2cash_chat_engine" class="form-control" id="a2cash_chat_engine" required>
                                                                                    <option value="">Select</option>
                                                                                    <option value="whatsapp" @selected(old('a2cash_chat_engine', $settings->a2cash_chat_engine) === 'whatsapp')>Whatsapp</option>
                                                                                    <option value="telegram" @selected(old('a2cash_chat_engine', $settings->a2cash_chat_engine) === 'telegram')>Telegram</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="whatsapp_number">Whatsapp Number</label>
                                                                                <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $settings->whatsapp_number) }}" placeholder="Whatsapp number">
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="telegram_username">Telegram Username</label>
                                                                                <input type="text" class="form-control" id="telegram_username" name="telegram_username" value="{{ old('telegram_username', $settings->telegram_username) }}" placeholder="Telegram Username">
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Notifications</h4>
                                                                    <small class="text-muted">Control the emails sent for customer and transaction events.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="login_email_notification">Customer Login Email Notification</label>
                                                                                <select name="login_email_notification" class="form-control" id="login_email_notification" required>
                                                                                    <option value="">Select</option>
                                                                                    <option value="yes" @selected(old('login_email_notification', $settings->login_email_notification) === 'yes')>Yes</option>
                                                                                    <option value="no" @selected(old('login_email_notification', $settings->login_email_notification) === 'no')>No</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="transaction_email_notification">Transaction Email Notification</label>
                                                                                <select name="transaction_email_notification" class="form-control" id="transaction_email_notification" required>
                                                                                    <option value="">Select</option>
                                                                                    <option value="yes" @selected(old('transaction_email_notification', $settings->transaction_email_notification) === 'yes')>Yes</option>
                                                                                    <option value="no" @selected(old('transaction_email_notification', $settings->transaction_email_notification) === 'no')>No</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Security</h4>
                                                                    <small class="text-muted">Captcha and automated form protection settings.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="captcha_settings_status">Allow Security Captcha on Forms</label>
                                                                                <select name="captcha_settings_status" class="form-control" id="captcha_settings_status">
                                                                                    <option value="">Select</option>
                                                                                    <option value="yes" @selected(old('captcha_settings_status', $settings->captcha_settings['captcha_settings_status'] ?? '') === 'yes')>Yes</option>
                                                                                    <option value="no" @selected(old('captcha_settings_status', $settings->captcha_settings['captcha_settings_status'] ?? '') === 'no')>No</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="captcha_settings_provider">Security Captcha Provider</label>
                                                                                <select name="captcha_settings_provider" class="form-control" id="captcha_settings_provider">
                                                                                    <option value="">Select</option>
                                                                                    <option value="simple" @selected(old('captcha_settings_provider', $settings->captcha_settings['captcha_settings_provider'] ?? '') === 'simple')>Simple Captcha</option>
                                                                                    <option value="google" @selected(old('captcha_settings_provider', $settings->captcha_settings['captcha_settings_provider'] ?? '') === 'google')>Google Captcha</option>
                                                                                    <option value="all" @selected(old('captcha_settings_provider', $settings->captcha_settings['captcha_settings_provider'] ?? '') === 'all')>All</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="RECAPTCHA_SITE_KEY">Google Captcha Site Key</label>
                                                                                <input type="text" class="form-control" id="RECAPTCHA_SITE_KEY" name="RECAPTCHA_SITE_KEY" value="{{ old('RECAPTCHA_SITE_KEY', $settings->captcha_settings['google']['RECAPTCHA_SITE_KEY'] ?? '') }}">
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="RECAPTCHA_SECRET_KEY">Google Captcha Secret Key</label>
                                                                                <input type="text" class="form-control" id="RECAPTCHA_SECRET_KEY" name="RECAPTCHA_SECRET_KEY" value="{{ old('RECAPTCHA_SECRET_KEY', $settings->captcha_settings['google']['RECAPTCHA_SECRET_KEY'] ?? '') }}">
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Branding</h4>
                                                                    <small class="text-muted">Manage logos and visual assets used across the application.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="logo">Logo</label>
                                                                                <div class="custom-file">
                                                                                    <input type="file" accept="image/*" class="custom-file-input" id="logo" name="logo">
                                                                                    <label class="custom-file-label" for="logo">Replace Logo</label>
                                                                                </div>

                                                                                @if(!empty($settings->logo))
                                                                                    <div class="settings-image-preview mt-1">
                                                                                        <img src="{{ asset($settings->logo) }}" alt="Current logo">
                                                                                    </div>
                                                                                @endif
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="favicon">Favicon</label>
                                                                                <div class="custom-file">
                                                                                    <input type="file" accept="image/*" class="custom-file-input" id="favicon" name="favicon">
                                                                                    <label class="custom-file-label" for="favicon">Replace Favicon</label>
                                                                                </div>

                                                                                @if(!empty($settings->favicon))
                                                                                    <div class="settings-image-preview mt-1">
                                                                                        <img src="{{ asset($settings->favicon) }}" alt="Current favicon">
                                                                                    </div>
                                                                                @endif
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="dashboard_logo">Dashboard Logo</label>
                                                                                <div class="custom-file">
                                                                                    <input type="file" accept="image/*" class="custom-file-input" id="dashboard_logo" name="dashboard_logo">
                                                                                    <label class="custom-file-label" for="dashboard_logo">Replace Dashboard Logo</label>
                                                                                </div>

                                                                                @if(!empty($settings->dashboard_logo))
                                                                                    <div class="settings-image-preview settings-image-preview--dashboard mt-1">
                                                                                        <img src="{{ asset($settings->dashboard_logo) }}" alt="Current dashboard logo">
                                                                                    </div>
                                                                                @endif
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">SEO & Advertising</h4>
                                                                    <small class="text-muted">Search metadata and advertising configuration.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="seo_title">SEO Title</label>
                                                                                <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ old('seo_title', $settings->seo_title) }}" placeholder="SEO Title">
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="google_dashboard_ad_enabled">Google Dashboard Ad</label>
                                                                                <select class="form-control" id="google_dashboard_ad_enabled" name="google_dashboard_ad_enabled">
                                                                                    <option value="1" @selected((bool) old('google_dashboard_ad_enabled', $settings->google_dashboard_ad_enabled ?? true))>Enabled</option>
                                                                                    <option value="0" @selected(! (bool) old('google_dashboard_ad_enabled', $settings->google_dashboard_ad_enabled ?? true))>Disabled</option>
                                                                                </select>
                                                                                <small class="text-muted d-block mt-50">Controls the dashboard ad in both customer layouts.</small>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="seo_description">SEO Description</label>
                                                                                <textarea class="form-control" id="seo_description" rows="4" name="seo_description" placeholder="SEO Description" required>{{ old('seo_description', $settings->seo_description) }}</textarea>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="google_ad_code">Google Ad Code</label>
                                                                                <textarea class="form-control" id="google_ad_code" rows="4" name="google_ad_code" placeholder="Google ad code">{{ old('google_ad_code', $settings->google_ad_code) }}</textarea>
                                                                            </fieldset>
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <fieldset class="form-group">
                                                                                <label for="google_dashboard_ad_code">Google Dashboard Ad Code</label>
                                                                                <textarea class="form-control" id="google_dashboard_ad_code" rows="5" name="google_dashboard_ad_code" placeholder="Google dashboard ad code">{{ old('google_dashboard_ad_code', $settings->google_dashboard_ad_code) }}</textarea>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @php
                                                                $themeColors = [
                                                                    'menu_text_color' => ['Menu Text Color', '#F4FAF9'],
                                                                    'menu_background_color' => ['Menu Background Color', '#123F43'],
                                                                    'active_color' => ['Menu Active Color', '#0B7D4F'],
                                                                    'block_header_color' => ['Text Header Color', '#286F70'],
                                                                    'dasboard_customer_details_color' => ['Dashboard Customer Details Color', '#F4E85A'],
                                                                ];
                                                            @endphp

                                                            <div class="card mb-2">
                                                                <div class="card-header">
                                                                    <h4 class="card-title mb-25">Theme Settings</h4>
                                                                    <small class="text-muted">Customize the main colors used throughout the application.</small>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        @foreach($themeColors as $field => [$label, $defaultColor])
                                                                            @php
                                                                                $colorValue = old($field, $settings->{$field} ?? $defaultColor);
                                                                                $colorValue = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $colorValue) ? strtoupper($colorValue) : $defaultColor;
                                                                            @endphp

                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group" data-color-control>
                                                                                    <label for="{{ $field }}">{{ $label }}</label>

                                                                                    <div class="theme-color-control">
                                                                                        <input class="theme-color-swatch" type="color" value="{{ $colorValue }}" data-color-picker aria-label="Choose {{ strtolower($label) }}">

                                                                                        <div class="input-group">
                                                                                            <input
                                                                                                class="form-control theme-color-hex"
                                                                                                type="text"
                                                                                                id="{{ $field }}"
                                                                                                name="{{ $field }}"
                                                                                                value="{{ $colorValue }}"
                                                                                                maxlength="7"
                                                                                                pattern="#[0-9A-Fa-f]{6}"
                                                                                                title="Enter a six-digit hex color, for example #123F43"
                                                                                                spellcheck="false"
                                                                                                autocomplete="off"
                                                                                                required
                                                                                                data-color-hex
                                                                                            >

                                                                                            <div class="input-group-append">
                                                                                                <button class="btn btn-outline-secondary theme-color-copy" type="button" data-copy-color>
                                                                                                    Copy
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </fieldset>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card settings-save-card">
                                                                <div class="card-body d-flex justify-content-end">
                                                                    <button class="btn btn-primary" type="submit">
                                                                        <i class="bx bx-save mr-50"></i>
                                                                        Update Settings
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <!-- Nav Filled Ends -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
    <script>
        $('.colorpicker').colorpicker();
    </script>
<script>
    document.querySelectorAll('[data-color-control]').forEach(function (control) {
        var picker = control.querySelector('[data-color-picker]');
        var hexInput = control.querySelector('[data-color-hex]');
        var copyButton = control.querySelector('[data-copy-color]');

        function normalizeHex(value) {
            var hex = value.trim().toUpperCase();

            if (/^[0-9A-F]{6}$/.test(hex)) {
                hex = '#' + hex;
            } else if (/^#[0-9A-F]{3}$/.test(hex)) {
                hex = '#' + hex.slice(1).split('').map(function (character) {
                    return character + character;
                }).join('');
            }

            return /^#[0-9A-F]{6}$/.test(hex) ? hex : null;
        }

        function applyTypedColor() {
            var color = normalizeHex(hexInput.value);

            if (color) {
                hexInput.value = color;
                picker.value = color;
                hexInput.setCustomValidity('');
            } else {
                hexInput.setCustomValidity('Enter a valid six-digit hex color, for example #123F43.');
            }

            return color;
        }

        picker.addEventListener('input', function () {
            hexInput.value = picker.value.toUpperCase();
            hexInput.setCustomValidity('');
        });

        hexInput.addEventListener('input', function () {
            var color = normalizeHex(hexInput.value);
            if (color) {
                picker.value = color;
                hexInput.setCustomValidity('');
            }
        });

        hexInput.addEventListener('blur', applyTypedColor);

        copyButton.addEventListener('click', function () {
            var color = applyTypedColor();
            if (!color) {
                hexInput.reportValidity();
                return;
            }

            var showCopied = function () {
                copyButton.textContent = 'Copied';
                window.setTimeout(function () {
                    copyButton.textContent = 'Copy';
                }, 1400);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(color).then(showCopied);
                return;
            }

            hexInput.select();
            document.execCommand('copy');
            showCopied();
        });
    });
</script>
<script>
    $('#referral_system_status').on('change', function (e) {
        var referral_system_status = $('#referral_system_status').val();

        if (referral_system_status == '' || referral_system_status == 'inactive') {
            $('#referral_percentage_div').hide();
            $("#referral_percentage").attr({
                "required": false,
            });
            return;
        }else if(referral_system_status == 'active'){
            $('#referral_percentage_div').show();

            $("#referral_percentage").attr({
                "required": true,
            });
        }
    });

</script>

@endsection
