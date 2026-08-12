@extends('layouts.app')
@section('content')
@php
    $isEdit = isset($api) && $api && $api->exists;
    $pageTitle = $isEdit ? 'Edit ' . $api->name : 'Add API Provider';
    $cardTitle = $isEdit ? 'Edit ' . $api->name : 'Add Provider';
    $submitLabel = $isEdit ? 'Update' : 'Submit';
    $formAction = $isEdit ? route('api.update', $api->id) : route('api.store');
    $pricingDataValue = old('pricing_data', $isEdit ? json_encode($api->pricing_data ?? []) : '[]');
    $pricingDataValue = is_string($pricingDataValue) ? $pricingDataValue : json_encode($pricingDataValue ?? []);
    $pricingDataArray = json_decode($pricingDataValue, true);
    $pricingDataArray = is_array($pricingDataArray) ? $pricingDataArray : [];
    $extraChargesValue = old('extra_charges', $isEdit ? json_encode($api->extra_charges ?? []) : '[]');
    $extraChargesValue = is_string($extraChargesValue) ? $extraChargesValue : json_encode($extraChargesValue ?? []);
    $extraChargesArray = json_decode($extraChargesValue, true);
    $extraChargesArray = is_array($extraChargesArray) ? $extraChargesArray : [];
    $pricingDataEnabled = filter_var(old('pricing_data_status', $isEdit ? ($api->pricing_data_status ? '1' : '0') : '0'), FILTER_VALIDATE_BOOL);
    $currencySymbol = getSettings()->currency ?? '&#8358;';
    $canPullBanks = $isEdit && (bool) ($api->is_bank_transfer || $api->is_bank_verification);
@endphp
@section('page-css')
<style>
    .api-form-shell {
        max-width: 1200px;
        margin-inline: auto;
        color: #1e293b;
    }

    .section-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        background: #ffffff;
        transition: all 0.3s ease;
    }

    .section-card:hover {
        box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.08);
    }

    .section-card .card-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #f1f5f9;
    }

    .section-card .card-body {
        padding: 1.5rem;
    }

    .form-control {
        border-radius: 0.6rem;
        border-color: #cbd5e1;
        padding: 0.65rem 1rem;
        font-size: 0.9rem;
        color: #334155;
        transition: all 0.2s ease-in-out;
    }

    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    label {
        font-weight: 600;
        font-size: 0.825rem;
        color: #475569;
        margin-bottom: 0.4rem;
    }

    .pricing-band-shell {
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .pricing-band-item {
        border: 1px solid #e2e8f0;
        border-radius: 0.8rem;
        padding: 1.25rem;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .pricing-band-item:hover {
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .pricing-band-item__title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 700;
        color: #0f172a;
    }

    .pricing-band-item__index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 999px;
        background: #e0e7ff;
        color: #4f46e5;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .pricing-band-item .form-label {
        margin-bottom: 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .pricing-band-state {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    .pricing-band-shell--disabled {
        opacity: 0.65;
    }

    .pricing-section-block {
        padding-top: 1.25rem;
        margin-top: 1.25rem;
        border-top: 1px dashed #e2e8f0;
    }

    .pricing-toggle-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: nowrap;
    }

    .pricing-toggle-row .custom-control {
        margin-left: 0;
        white-space: nowrap;
    }

    .pricing-toggle-row .custom-control-label {
        padding-top: 0.05rem;
        white-space: nowrap;
    }

    .pricing-empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 0.8rem;
        background: #ffffff;
        color: #64748b;
    }

    .breadcrumb-item a {
        color: #6366f1;
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: #64748b;
    }

    .card-title {
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
        border-radius: 0.6rem;
        padding: 0.65rem 1.5rem;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
        box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
    }

    .pricing-band-item .input-group-text {
        background: #f8fafc;
        color: #475569;
        border-color: #cbd5e1;
        font-weight: 600;
    }
</style>
@endsection
<!-- Content wrapper -->
<div class="app-content content api-form-shell">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-3 mt-1">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb p-0 mb-0 bg-transparent">
                                    <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a>
                                    </li>
                                    <li class="breadcrumb-item"><a href="{{ route('api.index') }}">API Provider</a>
                                    </li>
                                    <li class="breadcrumb-item active">{{ $pageTitle }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Basic Inputs start -->
                <section id="basic-input">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card section-card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap w-100">
                                        <div>
                                            <h4 class="card-title mb-1">{{ $cardTitle }}</h4>
                                            <p class="text-muted mb-0">Manage provider credentials, routing details, and fee bands from one place.</p>
                                        </div>
                                        <span class="badge badge-{{ $isEdit && $api->status === 'active' ? 'light-success' : 'light-secondary' }} pricing-band-state">
                                            <i class="bx bx-shield-quarter font-medium-1"></i>
                                            {{ $isEdit && $api->status === 'active' ? 'Active provider' : 'Draft / inactive' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @if($isEdit)
                                                @method('PATCH')
                                            @endif
                                            @include('layouts.alerts')
                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <div class="section-card border">
                                                        <div class="card-header bg-transparent">
                                                            <h6 class="font-weight-bold mb-0">Provider Details</h6>
                                                            <div class="small mt-0.5">Identity and routing information for this provider.</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="name">Name</label>
                                                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', optional($api)->name) }}" placeholder="Enter name" required>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="slug">Slug</label>
                                                                        <input type="text" class="form-control" name="slug" value="{{ old('slug', optional($api)->slug) }}" placeholder="Enter slug" id="slug" required>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="warning_threshold_status">Warning Threshold Status</label>
                                                                        <select class="form-control" name="warning_threshold_status" id="warning_threshold_status" required>
                                                                            <option value="">Select</option>
                                                                            <option value="active" {{ old('warning_threshold_status', optional($api)->warning_threshold_status) == 'active' ? 'selected' : '' }}>Active</option>
                                                                            <option value="inactive" {{ old('warning_threshold_status', optional($api)->warning_threshold_status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                                        </select>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="warning_threshold">Balance Warning Threshold</label>
                                                                        <input type="number" class="form-control" name="warning_threshold" value="{{ old('warning_threshold', optional($api)->warning_threshold) }}" placeholder="Enter warning threshold" id="warning_threshold">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-0">
                                                                        <label for="sandbox_base_url">Sandbox Base URL</label>
                                                                        <input type="text" class="form-control" name="sandbox_base_url" value="{{ old('sandbox_base_url', optional($api)->sandbox_base_url) }}" placeholder="Enter sandbox base url" id="sandbox_base_url">
                                                                    </fieldset>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <div class="section-card border">
                                                        <div class="card-header bg-transparent">
                                                            <h6 class="font-weight-bold text-dark mb-0">Integration Settings</h6>
                                                            <div class="text-muted small mt-0.5">Control status, credentials, and live endpoints.</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="status">API Status</label>
                                                                        <select class="form-control" name="status" id="status" required>
                                                                            <option value="">Select</option>
                                                                            <option value="active" {{ old('status', optional($api)->status) == 'active' ? 'selected' : '' }}>Active</option>
                                                                            <option value="inactive" {{ old('status', optional($api)->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                                        </select>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="api_key">API Key</label>
                                                                        <input type="text" class="form-control" name="api_key" value="{{ old('api_key', optional($api)->api_key) }}" placeholder="Enter api key" id="api_key">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="secret_key">Secret Key</label>
                                                                        <input type="text" class="form-control" name="secret_key" value="{{ old('secret_key', optional($api)->secret_key) }}" placeholder="Enter secret key" id="secret_key">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="public_key">Public Key</label>
                                                                        <input type="text" class="form-control" name="public_key" value="{{ old('public_key', optional($api)->public_key) }}" placeholder="Enter public key" id="public_key">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="account_number">Account Number</label>
                                                                        <input type="text" class="form-control" name="account_number" value="{{ old('account_number', optional($api)->account_number) }}" placeholder="Enter account number" id="account_number">
                                                                        <small class="text-muted d-block mt-50">Used by providers that need an internal settlement or wallet account number.</small>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="contract_id">Contract ID</label>
                                                                        <input type="text" class="form-control" name="contract_id" value="{{ old('contract_id', optional($api)->contract_id) }}" placeholder="Enter contract id" id="contract_id">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="charge">Card Funding Charge</label>
                                                                        <input type="number" min="0" step="0.01" class="form-control" name="charge" value="{{ old('charge', optional($api)->charge) }}" placeholder="Enter charge" id="charge">
                                                                        <small class="text-muted d-block mt-50">This is the provider charge used for card-based wallet funding. The extra card funding charge in Settings is added separately at checkout.</small>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="reserved_account_payment_charge_type">Reserved Account Charge Type</label>
                                                                        <select class="form-control" name="reserved_account_payment_charge_type" id="reserved_account_payment_charge_type">
                                                                            <option value="flat" @selected(old('reserved_account_payment_charge_type', optional($api)->reserved_account_payment_charge_type ?? 'flat') === 'flat')>Flat</option>
                                                                            <option value="percentage" @selected(old('reserved_account_payment_charge_type', optional($api)->reserved_account_payment_charge_type) === 'percentage')>Percentage</option>
                                                                        </select>
                                                                        <small class="text-muted d-block mt-50">Choose whether the reserved account charge is a fixed amount or percentage.</small>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group mb-3">
                                                                        <label for="reserved_account_payment_charge">Reserved Account Payment Charge</label>
                                                                        <input type="number" min="0" step="0.01" class="form-control" name="reserved_account_payment_charge" value="{{ old('reserved_account_payment_charge', optional($api)->reserved_account_payment_charge) }}" placeholder="Enter reserved account charge" id="reserved_account_payment_charge">
                                                                        <small class="text-muted d-block mt-50">Used for wallet funding via reserved account transfers.</small>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group mb-0">
                                                                        <label for="live_base_url">Live Base URL</label>
                                                                        <input type="text" class="form-control" name="live_base_url" value="{{ old('live_base_url', optional($api)->live_base_url) }}" placeholder="Enter live base url" id="live_base_url">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="pricing-section-block">
                                                                <h6 class="font-weight-bold mb-3">Capabilities</h6>
                                                                <div class="row">
                                                                    <div class="col-sm-6 mb-2">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox" class="custom-control-input" id="is_bank_transfer" name="is_bank_transfer" value="1" @checked(old('is_bank_transfer', optional($api)->is_bank_transfer))>
                                                                            <label class="custom-control-label" for="is_bank_transfer">Use for bank transfer</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6 mb-2">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox" class="custom-control-input" id="is_bank_verification" name="is_bank_verification" value="1" @checked(old('is_bank_verification', optional($api)->is_bank_verification))>
                                                                            <label class="custom-control-label" for="is_bank_verification">Use for bank verification</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6 mb-2">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox" class="custom-control-input" id="is_auto_share" name="is_auto_share" value="1" @checked(old('is_auto_share', optional($api)->is_auto_share))>
                                                                            <label class="custom-control-label" for="is_auto_share">Use for auto share</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6 mb-2">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox" class="custom-control-input" id="is_payment_gateway" name="is_payment_gateway" value="1" @checked(old('is_payment_gateway', optional($api)->is_payment_gateway))>
                                                                            <label class="custom-control-label" for="is_payment_gateway">Use for payment gateway</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted d-block mt-1">These flags control where the provider appears in settings and operations.</small>
                                                            </div>
                                                            @if($canPullBanks)
                                                                <div class="form-group mt-3 mb-0">
                                                                    <button type="button" class="btn btn-outline-info btn-block" id="pull-banks-btn" data-url="{{ route('api.pull.banks', $api) }}">
                                                                        <i class="bx bx-cloud-download mr-1"></i> Pull Bank Details
                                                                    </button>
                                                                    <small class="text-muted d-block mt-50">Refresh the shared bank table from this provider.</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                                <div class="col-12 mb-4">
                                                    <div class="section-card pricing-band-shell border {{ $pricingDataEnabled ? '' : 'pricing-band-shell--disabled' }}" id="pricing-band-shell">
                                                        <div class="card-header bg-transparent">
                                                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                                <div>
                                                                    <h6 class="font-weight-bold mb-0">Pricing Data</h6>
                                                                    <div class="text-muted small mt-0.5">Add transfer fee bands for wallet-to-bank transfers.</div>
                                                                </div>
                                                                <div class="pricing-toggle-row">
                                                                    <span class="badge badge-{{ $pricingDataEnabled ? 'light-success' : 'light-secondary' }} pricing-band-state mr-3" id="pricing-state-badge">
                                                                        {{ $pricingDataEnabled ? 'Enabled' : 'Disabled' }}
                                                                    </span>
                                                                    <div class="custom-control custom-switch">
                                                                        <input class="custom-control-input" type="checkbox" id="pricing_data_status" name="pricing_data_status" value="1" @checked($pricingDataEnabled)>
                                                                        <label class="custom-control-label mb-0" for="pricing_data_status"></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div id="pricing-fields">
                                                                <input type="hidden" name="pricing_data" id="pricing_data" value="{{ $pricingDataValue }}">
                                                                <input type="hidden" name="extra_charges" id="extra_charges" value="{{ $extraChargesValue }}">

                                                                <div class="pricing-section-block">
                                                                    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                                                                        <div>
                                                                            <h6 class="font-weight-bold mb-0">Pricing Bands</h6>
                                                                            <div class="text-muted small mt-0.5">Range-based fees that apply to a matching transfer amount.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="alert alert-dark border rounded-lg p-3 mb-4" style="">
                                                                        <div class="font-weight-bold mb-1"><i class="bx bx-info-circle"></i> How it works:</div>
                                                                        The matching band decides the fee for the amount the customer enters.
                                                                        <div class="small mt-1">Fee = Provider Fee + Our Charge + any band extra charges.</div>
                                                                    </div>
                                                                    <div id="pricing-bands" class="mb-3"></div>
                                                                    <div class="text-right">
                                                                        <button type="button" class="btn btn-sm btn-primary px-3 py-1" id="add-price-band" style="border-radius: 0.5rem;">
                                                                            <i class="bx bx-plus mr-1"></i> Add Price Band
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <div class="pricing-section-block">
                                                                    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                                                                        <div>
                                                                            <h6 class="font-weight-bold mb-0">Additional Charges</h6>
                                                                            <div class="text-muted small mt-0.5">Charges here apply to every transfer on this provider, regardless of band.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="extra-charges" class="mb-3"></div>
                                                                    <div class="text-right">
                                                                        <button type="button" class="btn btn-sm btn-outline-success px-3 py-1" id="add-extra-charge" style="border-radius: 0.5rem;">
                                                                            <i class="bx bx-plus mr-1"></i> Add Charge
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-end">
                                                        <button class="btn btn-primary px-4" type="submit">
                                                            <i class="bx bx-check mr-1"></i> {{ $submitLabel }} Provider & Pricing
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
            </div>
        </div>
    </div>
@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bandsContainer = document.getElementById('pricing-bands');
    const extraChargesContainer = document.getElementById('extra-charges');
    const addBandButton = document.getElementById('add-price-band');
    const addChargeButton = document.getElementById('add-extra-charge');
    const pricingInput = document.getElementById('pricing_data');
    const extraChargesInput = document.getElementById('extra_charges');
    const pricingToggle = document.getElementById('pricing_data_status');
    const pricingShell = document.getElementById('pricing-band-shell');
    const pricingFields = document.getElementById('pricing-fields');
    const pricingStateBadge = document.getElementById('pricing-state-badge');
    const pullBanksButton = document.getElementById('pull-banks-btn');
    const initialBands = @json($pricingDataArray);
    const initialExtraCharges = @json($extraChargesArray);
    const currencySymbol = {!! json_encode($currencySymbol) !!};

    if (!bandsContainer || !extraChargesContainer || !addBandButton || !addChargeButton || !pricingInput || !extraChargesInput || !pricingToggle || !pricingShell || !pricingFields || !pricingStateBadge) {
        return;
    }

    let bands = Array.isArray(initialBands) ? initialBands : [];
    let extraCharges = Array.isArray(initialExtraCharges) ? initialExtraCharges : [];
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    if (!bands.length) {
        bands = [{
            band_name: '',
            min_amount: '',
            max_amount: '',
            provider_fee: '',
            extra_charge: '',
            extra_charges: [],
        }];
    }

    const normalizeBand = (band = {}) => ({
        band_name: band.band_name ?? band.name ?? '',
        min_amount: band.min_amount ?? '',
        max_amount: band.max_amount ?? '',
        provider_fee: band.provider_fee ?? '',
        extra_charge: band.extra_charge ?? '',
        extra_charges: Array.isArray(band.extra_charges)
            ? band.extra_charges
            : (Array.isArray(band.charges) ? band.charges : []),
    });

    const normalizeCharge = (charge = {}) => ({
        charge_name: charge.charge_name ?? charge.name ?? '',
        value: charge.value ?? '',
    });

    const syncInput = () => {
        pricingInput.value = JSON.stringify(bands.map(normalizeBand));
        extraChargesInput.value = JSON.stringify(extraCharges.map(normalizeCharge));
    };

    const syncPricingState = () => {
        const enabled = pricingToggle.checked;
        pricingShell.classList.toggle('pricing-band-shell--disabled', !enabled);
        pricingFields.style.display = enabled ? '' : 'none';
        addBandButton.disabled = !enabled;
        addChargeButton.disabled = !enabled;
        pricingStateBadge.textContent = enabled ? 'Enabled' : 'Disabled';
        pricingStateBadge.className = enabled
            ? 'badge badge-light-success pricing-band-state mr-3'
            : 'badge badge-light-secondary pricing-band-state mr-3';
    };

    const renderExtraCharges = () => {
        if (!extraCharges.length) {
            extraChargesContainer.innerHTML = `
                <div class="pricing-empty-state p-3">
                    No provider-wide additional charges added yet. Use the button below to add one.
                </div>
            `;
            syncInput();
            return;
        }

        extraChargesContainer.innerHTML = extraCharges.map((charge, index) => {
            const normalized = normalizeCharge(charge);

            return `
                <div class="pricing-band-item mb-3" data-global-charge-index="${index}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="pricing-band-item__title"><span class="pricing-band-item__index">${index + 1}</span><strong>Charge ${index + 1}</strong></div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-global-charge="${index}" style="border-radius: 0.5rem;">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <label class="form-label">Charge Name</label>
                            <input type="text" class="form-control" data-global-charge-field="charge_name" data-global-charge-index="${index}" value="${escapeHtml(normalized.charge_name)}" placeholder="e.g. Stamp charge">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Value</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-global-charge-field="value" data-global-charge-index="${index}" value="${escapeHtml(normalized.value)}" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        syncInput();
    };

    const renderChargeRows = (charges, bandIndex) => {
        if (!charges.length) {
            return `
                <div class="pricing-empty-state p-3 mb-3">
                    No extra charges added to this band yet. Use the button below to add one.
                </div>
            `;
        }

        return charges.map((charge, chargeIndex) => {
            const normalized = normalizeCharge(charge);

            return `
                <div class="pricing-band-item mb-2" data-band-charge="${bandIndex}:${chargeIndex}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="pricing-band-item__title">
                            <span class="pricing-band-item__index">${chargeIndex + 1}</span>
                            <strong>Charge ${chargeIndex + 1}</strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-band-charge="${bandIndex}" data-band-charge-index="${chargeIndex}" style="border-radius: 0.5rem;">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <label class="form-label">Charge Name</label>
                            <input type="text" class="form-control" data-charge-field="charge_name" data-band-index="${bandIndex}" data-charge-index="${chargeIndex}" value="${escapeHtml(normalized.charge_name)}" placeholder="e.g. Stamp charge">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Value</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-charge-field="value" data-band-index="${bandIndex}" data-charge-index="${chargeIndex}" value="${escapeHtml(normalized.value)}" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    const renderBands = () => {
        bandsContainer.innerHTML = bands.map((band, index) => {
            const normalized = normalizeBand(band);
            const nestedCharges = Array.isArray(normalized.extra_charges) ? normalized.extra_charges : [];

            return `
                <div class="pricing-band-item mb-3" data-band-index="${index}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="pricing-band-item__title"><span class="pricing-band-item__index">${index + 1}</span><strong>Price Band ${index + 1}</strong></div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-band="${index}" style="border-radius: 0.5rem;">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Band Name</label>
                            <input type="text" class="form-control" data-band-field="band_name" data-band-index="${index}" value="${escapeHtml(normalized.band_name)}">
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="form-label">Min Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-band-field="min_amount" data-band-index="${index}" value="${escapeHtml(normalized.min_amount)}">
                            </div>
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="form-label">Max Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-band-field="max_amount" data-band-index="${index}" value="${escapeHtml(normalized.max_amount)}">
                            </div>
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="form-label">Provider Fee</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-band-field="provider_fee" data-band-index="${index}" value="${escapeHtml(normalized.provider_fee)}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Our Charge</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">${currencySymbol}</span></div>
                                <input type="number" min="0" step="0.01" class="form-control" data-band-field="extra_charge" data-band-index="${index}" value="${escapeHtml(normalized.extra_charge)}">
                            </div>
                        </div>
                    </div>
                    <div class="pricing-section-block">
                        <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                            <div>
                                <h6 class="font-weight-bold mb-0">Band Extra Charges</h6>
                                <div class="text-muted small mt-0.5">Additional charges that apply when this band matches.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success px-3 py-1" data-add-band-charge="${index}" style="border-radius: 0.5rem;">
                                <i class="bx bx-plus mr-1"></i> Add Charge
                            </button>
                        </div>
                        <div data-band-charge-list="${index}">
                            ${renderChargeRows(nestedCharges, index)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        syncInput();
    };

    addBandButton.addEventListener('click', function () {
        bands.push({
            band_name: '',
            min_amount: '',
            max_amount: '',
            provider_fee: '',
            extra_charge: '',
            extra_charges: [],
        });

        renderBands();
    });

    addChargeButton.addEventListener('click', function () {
        extraCharges.push({
            charge_name: '',
            value: '',
        });

        renderExtraCharges();
    });

    pricingToggle.addEventListener('change', syncPricingState);

    bandsContainer.addEventListener('input', function (event) {
        const field = event.target.getAttribute('data-band-field');
        const index = parseInt(event.target.getAttribute('data-band-index'), 10);

        const chargeField = event.target.getAttribute('data-charge-field');
        const bandIndex = parseInt(event.target.getAttribute('data-band-index'), 10);
        const chargeIndex = parseInt(event.target.getAttribute('data-charge-index'), 10);

        if (field && !Number.isNaN(index) && bands[index]) {
            bands[index][field] = event.target.value;
            syncInput();
            return;
        }

        if (!chargeField || Number.isNaN(bandIndex) || Number.isNaN(chargeIndex) || !bands[bandIndex]) {
            return;
        }

        const band = bands[bandIndex];
        if (!Array.isArray(band.extra_charges)) {
            band.extra_charges = [];
        }

        if (!band.extra_charges[chargeIndex]) {
            band.extra_charges[chargeIndex] = {
                charge_name: '',
                value: '',
            };
        }

        band.extra_charges[chargeIndex][chargeField] = event.target.value;
        syncInput();
    });

    extraChargesContainer.addEventListener('input', function (event) {
        const field = event.target.getAttribute('data-global-charge-field');
        const index = parseInt(event.target.getAttribute('data-global-charge-index'), 10);

        if (!field || Number.isNaN(index) || !extraCharges[index]) {
            return;
        }

        extraCharges[index][field] = event.target.value;
        syncInput();
    });

    extraChargesContainer.addEventListener('click', function (event) {
        const removeIndex = event.target.closest('[data-remove-global-charge]')?.getAttribute('data-remove-global-charge');

        if (removeIndex === null || removeIndex === undefined) {
            return;
        }

        const index = parseInt(removeIndex, 10);

        if (Number.isNaN(index)) {
            return;
        }

        extraCharges.splice(index, 1);
        renderExtraCharges();
    });

    bandsContainer.addEventListener('click', function (event) {
        const addChargeIndex = event.target.closest('[data-add-band-charge]')?.getAttribute('data-add-band-charge');
        if (addChargeIndex !== null && addChargeIndex !== undefined) {
            const index = parseInt(addChargeIndex, 10);

            if (Number.isNaN(index) || !bands[index]) {
                return;
            }

            if (!Array.isArray(bands[index].extra_charges)) {
                bands[index].extra_charges = [];
            }

            bands[index].extra_charges.push({
                charge_name: '',
                value: '',
            });

            renderBands();
            return;
        }

        const removeIndex = event.target.closest('[data-remove-band]')?.getAttribute('data-remove-band');

        if (removeIndex === null || removeIndex === undefined) {
            const removeChargeBandIndex = event.target.closest('[data-remove-band-charge]')?.getAttribute('data-remove-band-charge');
            const removeChargeIndex = event.target.closest('[data-remove-band-charge]')?.getAttribute('data-band-charge-index');

            if (removeChargeBandIndex === null || removeChargeBandIndex === undefined || removeChargeIndex === null || removeChargeIndex === undefined) {
                return;
            }

            const bandIndex = parseInt(removeChargeBandIndex, 10);
            const chargeIndex = parseInt(removeChargeIndex, 10);

            if (Number.isNaN(bandIndex) || Number.isNaN(chargeIndex) || !bands[bandIndex] || !Array.isArray(bands[bandIndex].extra_charges)) {
                return;
            }

            bands[bandIndex].extra_charges.splice(chargeIndex, 1);
            renderBands();
            return;
        }

        const index = parseInt(removeIndex, 10);

        if (Number.isNaN(index)) {
            return;
        }

        bands.splice(index, 1);

        if (!bands.length) {
            bands.push({
                band_name: '',
                min_amount: '',
                max_amount: '',
                provider_fee: '',
                extra_charge: '',
                extra_charges: [],
            });
        }

        renderBands();
    });

    syncPricingState();
    renderBands();
    renderExtraCharges();

    if (pullBanksButton) {
        pullBanksButton.addEventListener('click', async function () {
            const originalHtml = pullBanksButton.innerHTML;
            pullBanksButton.disabled = true;
            pullBanksButton.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Syncing...';

            try {
                const response = await fetch(pullBanksButton.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const payload = await response.json();

                if (!response.ok || !payload.status) {
                    throw new Error(payload.message || 'Unable to sync banks.');
                }

                alert(payload.message || 'Banks synced successfully.');
            } catch (error) {
                alert(error.message || 'Unable to sync banks.');
            } finally {
                pullBanksButton.disabled = false;
                pullBanksButton.innerHTML = originalHtml;
            }
        });
    }
});
</script>
@endsection
