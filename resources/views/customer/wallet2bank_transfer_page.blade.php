<?php
    $settings = getSettings();
    $verifiable = verifiableUniqueElements();
    $wallet2bankAutoEnabled = (($settings->wallet_to_bank_transfer_auto_status ?? 'enabled') === 'enabled');
    $wallet2bankManualEnabled = (($settings->wallet_to_bank_transfer_manual_status ?? 'enabled') === 'enabled');
    $wallet2bankAutoAccess = (bool) (auth()->user()?->customer?->can_access_w2bank_auto ?? false);
    $wallet2bankManualAccess = (bool) (auth()->user()?->customer?->can_access_w2bank ?? true);
    $visibleTransferModes = array_values(array_filter([
        $wallet2bankAutoEnabled ? 'auto_share' : null,
        $wallet2bankManualEnabled ? 'manual' : null,
    ]));
    $availableTransferModes = array_values(array_filter([
        ($wallet2bankAutoEnabled && $wallet2bankAutoAccess) ? 'auto_share' : null,
        ($wallet2bankManualEnabled && $wallet2bankManualAccess) ? 'manual' : null,
    ]));
    $hasSelectableTransferMode = ! empty($availableTransferModes);
    $defaultTransferMode = old('transfer_mode', $availableTransferModes[0] ?? ($visibleTransferModes[0] ?? 'auto_share'));
    $showProviderStatus = (bool) ($settings->show_provider_status_on_customer_pages ?? true);
    $adminWhatsappNumber = preg_replace('/\D+/', '', (string) ($settings->whatsapp_number ?? ''));
    $adminWhatsappLink = filled($adminWhatsappNumber)
        ? 'https://api.whatsapp.com/send?phone=' . $adminWhatsappNumber . '&text=' . urlencode('Wallet to Bank access request from ' . (auth()->user()?->email ?? 'customer') . '. Please enable this service for my account.')
        : null;
?>
@extends('layouts.app')
@section('title', $category->seo_title ?? getSettings()->seo_title)
@section('keywords', $category->seo_keywords ?? getSettings()->seo_keywords)
@section('description', $category->seo_description ?? getSettings()->seo_description)
@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
    #verify-link{
        text-transform: capitalize;
        text-decoration: underline;
    }

    .footnote {
        margin-top: 5px;
    }
    label {
        font-weight: 500 !important;
        color: black;
    }

    .transfer-preview-card {
        position: relative;
        overflow: hidden;
        border: 1px solid #d8e4ff;
        border-radius: 1rem;
        background: linear-gradient(180deg, #fbfdff 0%, #eef4ff 100%);
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
        color: #1e293b;
    }

    .transfer-preview-card::before {
        position: absolute;
        top: 0;
        right: 0;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.12), transparent 70%);
        content: "";
        pointer-events: none;
    }

    .transfer-preview-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
    }

    .transfer-preview-muted {
        color: #64748b;
        font-size: 0.85rem;
    }

    .transfer-preview-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .transfer-preview-section {
        margin-top: 0.85rem;
        padding: 0.85rem;
        border-radius: 0.85rem;
    }

    .transfer-preview-section--base {
        background: rgba(37, 99, 235, 0.08);
    }

    .transfer-preview-section--band {
        background: rgba(16, 185, 129, 0.08);
    }

    .transfer-preview-section--additional {
        background: rgba(245, 158, 11, 0.08);
    }

    .transfer-preview-section-label {
        margin-bottom: 0.55rem;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .transfer-preview-line {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.2rem 0;
        color: #334155;
    }

    .transfer-preview-line strong {
        color: #0f172a;
    }

    .transfer-preview-total {
        margin-top: 0.95rem;
        padding-top: 0.9rem;
        border-top: 1px solid rgba(100, 116, 139, 0.22);
        font-weight: 700;
    }

    .transfer-mode-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .transfer-mode-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .transfer-mode-option {
        display: flex;
        min-height: 92px;
        padding: 1rem;
        align-items: center;
        gap: .85rem;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(248,250,252,.95));
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .transfer-mode-option:hover {
        transform: translateY(-2px);
        border-color: rgba(37, 99, 235, .34);
        box-shadow: 0 .9rem 1.6rem rgba(37, 99, 235, .08);
    }

    .transfer-mode-input:checked + .transfer-mode-option {
        border-color: #2563eb;
        background: linear-gradient(135deg, rgba(37, 99, 235, .1), rgba(59, 130, 246, .04));
        box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .08);
    }

    .transfer-mode-badge {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #fff;
        font-size: 1.1rem;
        background: linear-gradient(145deg, #2563eb, #0ea5e9);
    }

    .transfer-mode-option strong,
    .transfer-mode-option small {
        display: block;
    }

    .transfer-mode-option small {
        margin-top: .18rem;
        color: #64748b;
        line-height: 1.45;
    }

    .transfer-mode-locked-copy {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .15rem;
    }

    .transfer-mode-option--locked {
        cursor: not-allowed;
        border-style: dashed;
        opacity: .84;
    }

    .transfer-mode-option--locked:hover {
        transform: none;
        box-shadow: none;
        border-color: rgba(148, 163, 184, .24);
    }

    .manual-resolution-note {
        padding: .9rem 1rem;
        border: 1px solid rgba(245, 158, 11, .28);
        border-radius: .9rem;
        background: rgba(255, 251, 235, .95);
        color: #92400e;
    }
    .provider-health-strip { display:flex; margin-bottom:1rem; padding:.9rem 1rem; align-items:center; justify-content:space-between; gap:1rem; border:1px solid rgba(148,163,184,.18); border-radius:14px; background:linear-gradient(145deg,#fff, #f8fafc); }
    .provider-health-strip strong, .provider-health-strip small { display:block; }
    .provider-health-kicker { display:inline-flex; margin-bottom:.2rem; color:#64748b; font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .provider-health-strip small { color:#64748b; }
    .provider-health-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .7rem; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; white-space:nowrap; }
    .provider-health-badge.unstable { color:#991b1b; background:rgba(239,68,68,.14); }
    .provider-health-badge.degraded { color:#111827; background:rgba(17,24,39,.12); }
    .provider-health-badge.stable { color:#9a6700; background:rgba(245,158,11,.15); }
    .provider-health-badge.healthy { color:#166534; background:rgba(34,197,94,.14); }
    [data-bs-theme="dark"] .provider-health-strip { border-color:rgba(148,163,184,.24); background:rgba(43,44,64,.94); }
    [data-bs-theme="dark"] .provider-health-kicker, [data-bs-theme="dark"] .provider-health-strip small { color:#cbd5e1; }
    [data-bs-theme="dark"] .provider-health-badge.degraded { color:#e5e7eb; background:rgba(75,85,99,.4); }
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
                                                    <h4 class="card-title">Wallet to Bank Transfer</h4>
                                                    @include('layouts.alerts')
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                    @if($showProviderStatus && $activeProvider && $activeProvider->availability_status_class && $activeProvider->availability_checked_at)
                        <div class="provider-health-strip">
                                                                <div>
                                                                    <span class="provider-health-kicker">Auto Transfer Status</span>
                                                                    <small>Checked {{ $activeProvider->availability_checked_at->diffForHumans() }}</small>
                                                                    @if($activeProvider->availability_status_class === 'unstable')
                                                                        <small class="text-danger mt-1">Auto transfer looks unstable right now, so manual processing may be the safer option.</small>
                                                                    @endif
                                                                </div>
                                                                <span class="provider-health-badge {{ $activeProvider->availability_status_class }}">
                                                                    <i class="bx bx-pulse"></i>
                                                                    {{ $activeProvider->availability_status_label }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        <form action="{{route('initialize.wallet2banktransaction', $product->id)}}" method="POST" onsubmit="return confirm('I have entered correct details');">
                                                            @csrf
                                                            @include('layouts.alerts')
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <p></p>
                                                                </div>
                                                                <div class="col-md-12 mb-2">
                                                                    <label class="d-block mb-2">Transfer Method</label>
                                                                    @if(empty($visibleTransferModes))
                                                                        <div class="alert alert-warning mb-0">
                                                                            Wallet to bank transfer is currently unavailable. Please try again later.
                                                                        </div>
                                                                    @else
                                                                        <div class="transfer-mode-grid">
                                                                            @if($wallet2bankAutoEnabled)
                                                                                <div>
                                                                                    @if($wallet2bankAutoAccess)
                                                                                        <input class="transfer-mode-input" type="radio" name="transfer_mode" id="transfer-mode-auto" value="auto_share" @checked($defaultTransferMode === 'auto_share')>
                                                                                        <label class="transfer-mode-option" for="transfer-mode-auto">
                                                                                            <span class="transfer-mode-badge"><i class="bx bx-bolt-circle"></i></span>
                                                                                            <span>
                                                                                                <strong>Auto Transfer</strong>
                                                                                                <small>Use the active transfer provider for a faster settlement.</small>
                                                                                            </span>
                                                                                        </label>
                                                                                    @else
                                                                                        <div class="transfer-mode-option transfer-mode-option--locked">
                                                                                            <span class="transfer-mode-badge"><i class="bx bx-lock-alt"></i></span>
                                                                                            <span class="transfer-mode-locked-copy">
                                                                                                <strong>Auto Transfer</strong>
                                                                                                <small>This transfer mode is currently unavailable for your account.</small>
                                                                                                @if($adminWhatsappLink)
                                                                                                    <a class="btn btn-sm btn-outline-success mt-2 align-self-start transfer-mode-locked-cta" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">Click here to contact admin on WhatsApp</a>
                                                                                                @endif
                                                                                            </span>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                            @if($wallet2bankManualEnabled)
                                                                                <div>
                                                                                    @if($wallet2bankManualAccess)
                                                                                        <input class="transfer-mode-input" type="radio" name="transfer_mode" id="transfer-mode-manual" value="manual" @checked($defaultTransferMode === 'manual')>
                                                                                        <label class="transfer-mode-option" for="transfer-mode-manual">
                                                                                            <span class="transfer-mode-badge"><i class="bx bx-hand"></i></span>
                                                                                            <span>
                                                                                                <strong>Manual Transfer</strong>
                                                                                                <small>Queued for admin review and sent to WhatsApp for manual processing.</small>
                                                                                            </span>
                                                                                        </label>
                                                                                    @else
                                                                                        <div class="transfer-mode-option transfer-mode-option--locked">
                                                                                            <span class="transfer-mode-badge"><i class="bx bx-lock-alt"></i></span>
                                                                                            <span class="transfer-mode-locked-copy">
                                                                                                <strong>Manual Transfer</strong>
                                                                                                <small>This transfer mode is currently unavailable for your account.</small>
                                                                                                @if($adminWhatsappLink)
                                                                                                    <a class="btn btn-sm btn-outline-success mt-2 align-self-start transfer-mode-locked-cta" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">Click here to contact admin on WhatsApp</a>
                                                                                                @endif
                                                                                            </span>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                        @if($hasSelectableTransferMode)
                                                                            @if(count($availableTransferModes) > 1)
                                                                                <div class="manual-resolution-note mt-3" id="manual-resolution-note" style="display:none">
                                                                                    Manual transfers depend on an admin being online and may take a while during busy periods.
                                                                                </div>
                                                                            @else
                                                                                <input type="hidden" name="transfer_mode" value="{{ $defaultTransferMode }}">
                                                                                <div class="alert alert-info mb-0">
                                                                                    @if($defaultTransferMode === 'manual')
                                                                                        Manual resolution depends on an admin being online and may take a while during busy periods.
                                                                                    @else
                                                                                        {{-- Auto Transfer is available for this wallet-to-bank request. --}}
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <div class="alert alert-warning mt-3 mb-0">
                                                                                Wallet to bank access is partially available on this page, but none of the available modes are enabled for your account. Please contact admin to request access.
                                                                            </div>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-12">
                                                                    @php
                                                                        $providerMin  = 60;
                                                                        $walletBal    = walletBalance(auth()->user());
                                                                        $pricingBands = $pricingBands ?? [];
                                                                        $pricingEnabled = (bool) ($pricingEnabled ?? false);
                                                                        $pricingAvailable = (bool) ($pricingAvailable ?? false);
                                                                        $pricingAmountRange = $pricingAmountRange ?? [];
                                                                        $pricingConfigured = $pricingEnabled && $pricingAvailable;
                                                                        $minimumCharge = $pricingConfigured ? getBankTransferChargeDetails($providerMin, $pricingProvider?->id) : ['transfer_fee' => 0];
                                                                        $minimumRequiredBalance = $providerMin + (float) ($minimumCharge['transfer_fee'] ?? 0);
                                                                        $maxAmount = max(0, $walletBal);
                                                                        $validTransferMin = max($providerMin, (float) ($pricingAmountRange['min_amount'] ?? $providerMin));
                                                                        $canWithdraw = $pricingConfigured && $walletBal >= $minimumRequiredBalance;
                                                                    @endphp

                                                                    <fieldset class="form-group">
                                                                        <label for="amount">Amount to withdraw from wallet</label>

                                                                        @if(!$canWithdraw)
                                                                            <div class="alert alert-warning">
                                                                                @if(!$pricingEnabled)
                                                                                    Wallet to bank transfer pricing is not configured yet.
                                                                                @elseif(!$pricingAvailable)
                                                                                    Wallet to bank transfer pricing is not configured for the active provider.
                                                                                @else
                                                                                    You do not have sufficient balance to use this service.
                                                                                    <br>
                                                                                    <small>
                                                                                        Minimum required wallet balance is
                                                                                        <strong>
                                                                                            {!! getSettings()['currency'] !!}{{ number_format($minimumRequiredBalance, 2) }}
                                                                                        </strong>
                                                                                    </small>
                                                                                @endif
                                                                            </div>
                                                                        @else
                                                                            <input
                                                                                class="form-control"
                                                                                id="amount"
                                                                                name="amount"
                                                                                type="number"
                                                                                placeholder="Enter Amount to transfer"
                                                                                required
                                                                                min="{{ $validTransferMin }}"
                                                                                max="{{ $maxAmount }}"
                                                                                data-wallet-balance="{{ $walletBal }}"
                                                                                data-min-transfer="{{ $providerMin }}"
                                                                                data-pricing-bands='@json($pricingBands)'
                                                                                data-global-extra-charges='@json($pricingProvider?->extra_charges ?? [])'
                                                                                data-valid-range-text='@json($pricingAmountRange["range_text"] ?? null)'
                                                                            >
                                                                            @if(!empty($pricingAmountRange['range_text']))
                                                                                <small class="d-block mt-1 text-muted">Valid transfer amount range: <strong>{{ $pricingAmountRange['range_text'] }}</strong></small>
                                                                            @endif

                                                                            <div class="transfer-preview-card mt-2 mb-0 p-3" id="transfer-preview">
                                                                                <div class="d-flex justify-content-between align-items-start">
                                                                                    <div>
                                                                                        <div class="transfer-preview-title">Fee Preview</div>
                                                                                        <div class="transfer-preview-muted">Wallet to bank transfer summary</div>
                                                                                    </div>
                                                                                    <span class="badge badge-primary">Live</span>
                                                                                </div>
                                                                                <div class="transfer-preview-section transfer-preview-section--base">
                                                                                    <div class="transfer-preview-section-label">Transfer Amount</div>
                                                                                    <div class="transfer-preview-row">
                                                                                        <span>Requested amount</span>
                                                                                        <strong id="preview-amount">{!! getSettings()['currency'] !!}0.00</strong>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="transfer-preview-section transfer-preview-section--base">
                                                                                    <div class="transfer-preview-section-label">Base Transfer Charge</div>
                                                                                    <div class="transfer-preview-row">
                                                                                        <span>Base fee only</span>
                                                                                        <strong id="preview-fee">{!! getSettings()['currency'] !!}0.00</strong>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="transfer-preview-section transfer-preview-section--band" id="preview-band-section">
                                                                                    <div class="transfer-preview-section-label">Band Extra Charges</div>
                                                                                    <div id="preview-band-charges"></div>
                                                                                </div>
                                                                                <div class="transfer-preview-section transfer-preview-section--additional" id="preview-additional-section">
                                                                                    <div class="transfer-preview-section-label">Additional Charges</div>
                                                                                    <div id="preview-additional-charges"></div>
                                                                                </div>
                                                                                <div class="transfer-preview-total">
                                                                                    <div class="transfer-preview-row">
                                                                                        <span>Total Debit</span>
                                                                                        <strong id="preview-total">{!! getSettings()['currency'] !!}0.00</strong>
                                                                                    </div>
                                                                                    <small class="d-none mt-2 d-block text-danger" id="preview-shortfall"></small>
                                                                                </div>
                                                                                <small class="d-block mt-2 text-danger d-none" id="preview-error">Valid transfer amount range: {{ $pricingAmountRange['range_text'] ?? 'not available' }}</small>
                                                                            </div>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <script>
                                                                    document.addEventListener('DOMContentLoaded', function () {
                                                                        const amountInput = document.getElementById('amount');
                                                                        const previewAmount = document.getElementById('preview-amount');
                                                                        const previewFee = document.getElementById('preview-fee');
                                                                        const previewTotal = document.getElementById('preview-total');
                                                                        const previewError = document.getElementById('preview-error');
                                                                        const previewShortfall = document.getElementById('preview-shortfall');
                                                                        const previewBandCharges = document.getElementById('preview-band-charges');
                                                                        const previewAdditionalCharges = document.getElementById('preview-additional-charges');
                                                                        const previewBandSection = document.getElementById('preview-band-section');
                                                                        const previewAdditionalSection = document.getElementById('preview-additional-section');
                                                                        const submitButton = document.getElementById('transfer-submit');
                                                                        const verifyBankButton = document.getElementById('verify-bank-details-btn');
                                                                        const verifyBankResult = document.getElementById('bank-verify-result');

                                                                        if (!amountInput || !previewAmount || !previewFee || !previewTotal || !submitButton) return;

                                                                        const walletBalance = parseFloat(amountInput.dataset.walletBalance) || 0;
                                                                        const minimumTransfer = parseFloat(amountInput.dataset.minTransfer) || 0;
                                                                        const pricingBands = JSON.parse(amountInput.dataset.pricingBands || '[]');
                                                                        const globalExtraCharges = JSON.parse(amountInput.dataset.globalExtraCharges || '[]');
                                                                        const validRangeText = amountInput.dataset.validRangeText ? JSON.parse(amountInput.dataset.validRangeText) : null;
                                                                        const currency   = `{!! getSettings()['currency'] !!}`;

                                                                        const normalizeCharge = (charge) => ({
                                                                            charge_name: charge.charge_name ?? charge.name ?? 'Additional Charge',
                                                                            value: parseFloat(charge.value ?? 0) || 0,
                                                                        });

                                                                        const matchesBand = (amount) => pricingBands.find((band) => {
                                                                            const min = band.min_amount !== '' && band.min_amount !== null && band.min_amount !== undefined ? parseFloat(band.min_amount) : null;
                                                                            const max = band.max_amount !== '' && band.max_amount !== null && band.max_amount !== undefined ? parseFloat(band.max_amount) : null;

                                                                            if (min !== null && amount < min) {
                                                                                return false;
                                                                            }

                                                                            if (max !== null && amount > max) {
                                                                                return false;
                                                                            }

                                                                            return true;
                                                                        });

                                                                        const formatMoney = (value) => `${currency}${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                                                                        const renderBreakdown = (band, bandExtraCharges, additionalCharges) => {
                                                                            if (!previewBandCharges || !previewAdditionalCharges || !previewBandSection || !previewAdditionalSection) {
                                                                                return;
                                                                            }

                                                                            if (!band) {
                                                                                previewBandCharges.innerHTML = '';
                                                                                previewAdditionalCharges.innerHTML = '';
                                                                                previewBandSection.style.display = 'none';
                                                                                previewAdditionalSection.style.display = 'none';
                                                                                return;
                                                                            }

                                                                            const bandRows = bandExtraCharges.map(normalizeCharge).filter((charge) => charge.value > 0 || charge.charge_name);
                                                                            const additionalRows = additionalCharges.map(normalizeCharge).filter((charge) => charge.value > 0 || charge.charge_name);

                                                                            previewBandSection.style.display = bandRows.length ? '' : 'none';
                                                                            previewAdditionalSection.style.display = additionalRows.length ? '' : 'none';

                                                                            previewBandCharges.innerHTML = bandRows.length
                                                                                ? bandRows.map((charge) => `
                                                                                    <div class="transfer-preview-line">
                                                                                        <span>${charge.charge_name}</span>
                                                                                        <strong>${formatMoney(charge.value)}</strong>
                                                                                    </div>
                                                                                `).join('')
                                                                                : '';

                                                                            previewAdditionalCharges.innerHTML = additionalRows.length
                                                                                ? additionalRows.map((charge) => `
                                                                                    <div class="transfer-preview-line">
                                                                                        <span>${charge.charge_name}</span>
                                                                                        <strong>${formatMoney(charge.value)}</strong>
                                                                                    </div>
                                                                                `).join('')
                                                                                : '';
                                                                        };

                                                                        amountInput.addEventListener('input', function () {
                                                                            const amount = parseFloat(this.value) || 0;
                                                                            const band = matchesBand(amount);
                                                                            const providerFee = band ? (parseFloat(band.provider_fee) || 0) : 0;
                                                                            const extraCharge = band ? (parseFloat(band.extra_charge) || 0) : 0;
                                                                            const baseTransferCharge = band ? (providerFee + extraCharge) : 0;
                                                                            const bandExtraCharges = Array.isArray(band?.extra_charges) ? band.extra_charges : [];
                                                                            const globalFees = globalExtraCharges.reduce((sum, charge) => sum + (parseFloat(charge.value) || 0), 0);
                                                                            const bandExtraFees = bandExtraCharges.reduce((sum, charge) => sum + (parseFloat(charge.value) || 0), 0);
                                                                            const additionalCharges = [...globalExtraCharges, ...bandExtraCharges];
                                                                            const fee = band ? (baseTransferCharge + globalFees + bandExtraFees) : 0;
                                                                            const total = band ? (amount + fee) : 0;

                                                                            previewAmount.textContent = formatMoney(amount);
                                                                            previewFee.textContent = band ? formatMoney(baseTransferCharge) : 'N/A';
                                                                            previewTotal.textContent = band ? formatMoney(total) : 'N/A';
                                                                            renderBreakdown(band, bandExtraCharges, globalExtraCharges);

                                                                            const previewCard = document.getElementById('transfer-preview');
                                                                            const isValid = Boolean(band) && amount >= minimumTransfer && total <= walletBalance;

                                                                            if (previewError) {
                                                                                if (validRangeText) {
                                                                                    previewError.textContent = `Valid transfer amount range: ${validRangeText}`;
                                                                                }
                                                                                previewError.classList.toggle('d-none', isValid || amount <= 0);
                                                                            }

                                                                            if (previewShortfall) {
                                                                                const shortfall = Math.max(0, total - walletBalance);
                                                                                if (amount > 0 && band && shortfall > 0) {
                                                                                    previewShortfall.textContent = `You need ${formatMoney(shortfall)} more in your wallet to continue.`;
                                                                                    previewShortfall.classList.remove('d-none');
                                                                                } else {
                                                                                    previewShortfall.textContent = '';
                                                                                    previewShortfall.classList.add('d-none');
                                                                                }
                                                                            }

                                                                            previewCard.style.opacity = (amount > 0 && !isValid) ? '0.92' : '1';

                                                                            submitButton.disabled = !isValid;
                                                                        });

                                                                        if (verifyBankButton && verifyBankResult) {
                                                                            verifyBankButton.addEventListener('click', async function () {
                                                                                const bank = document.getElementById('bank');
                                                                                const accountNumber = document.getElementById('account_number');
                                                                                const accountName = document.getElementById('account_name');

                                                                                if (!bank || !accountNumber || !accountName) {
                                                                                    return;
                                                                                }

                                                                                verifyBankButton.disabled = true;
                                                                                verifyBankButton.textContent = 'Verifying...';
                                                                                verifyBankResult.className = 'alert alert-light border';
                                                                                verifyBankResult.textContent = 'Verifying bank details...';
                                                                                verifyBankResult.classList.remove('d-none');

                                                                                try {
                                                                                    const response = await fetch('{{ route('customer.verify.bank.details') }}', {
                                                                                        method: 'POST',
                                                                                        headers: {
                                                                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                                                                            'Accept': 'application/json',
                                                                                            'Content-Type': 'application/json',
                                                                                        },
                                                                                        body: JSON.stringify({
                                                                                            bank_code: bank.value,
                                                                                            account_number: accountNumber.value,
                                                                                            account_name: accountName.value,
                                                                                        }),
                                                                                    });

                                                                                    const payload = await response.json();

                                                                                    if (!response.ok || payload.status === false) {
                                                                                        throw new Error(payload.message || 'Unable to verify bank details right now.');
                                                                                    }

                                                                                    verifyBankResult.className = '';
                                                                                    verifyBankResult.innerHTML = renderVerificationCard(payload, 'Bank details verified successfully');
                                                                                } catch (error) {
                                                                                    verifyBankResult.className = '';
                                                                                    verifyBankResult.innerHTML = renderVerificationCard({
                                                                                        status: 'failed',
                                                                                        message: error.message || 'Unable to verify bank details right now.',
                                                                                        raw_response: {
                                                                                            message: error.message || 'Unable to verify bank details right now.',
                                                                                        },
                                                                                    }, 'Bank details verification failed');
                                                                                } finally {
                                                                                    verifyBankButton.disabled = false;
                                                                                    verifyBankButton.textContent = 'Verify Bank Details';
                                                                                }
                                                                            });
                                                                        }

                                                                        submitButton.disabled = true;
                                                                        amountInput.dispatchEvent(new Event('input'));
                                                                    });
                                                                </script>

                                                                <div class="col-md-12">
                                                                    <fieldset class="form-group">
                                                                        <label for="payment_method">Select Bank </label>
                                                                        <select class="form-control" name="bank" id="bank" required>
                                                                            <option value="">Select</option>
                                                                            @foreach($banks as $bank)
                                                                            <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </fieldset>
                                                                    <fieldset class="form-group">
                                                                        <label for="receive" class="">Account Number</label>
                                                                        <input class="form-control" id="account_number" name="account_number" type="text" maxlength="10" required>
                                                                    </fieldset>
                                                                    <fieldset class="form-group">
                                                                        <label for="receive" class="">Account Name</label>
                                                                        <input class="form-control" id="account_name" name="account_name" type="text" required>
                                                                    </fieldset>
                                                                    <div id="verify-bank-section">
                                                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                                                            <small class="footnote" style="color:red">Please ensure that bank details entered are correct to enable us complete the transaction</small>
                                                                            <button type="button" class="btn btn-outline-info btn-sm mt-2 mt-md-0" id="verify-bank-details-btn">Verify Bank Details</button>
                                                                        </div>
                                                                        <div class="alert alert-light border d-none" id="bank-verify-result"></div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12">
                                                                        <button style="margin-top:4px" class="btn btn-primary" id="transfer-submit" type="submit" @disabled(!$canWithdraw || ! $hasSelectableTransferMode)>PROCEED </button>
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
<div class="modal fade" id="verify-modal" data-bs-backdrop="static" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog">
    <form class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="verify-title"></h5>
    </div>
    <div class="modal-body">
        <div id="verify-details">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
    </div>
    </form>
    </div>
</div>
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>

<script>
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderVerificationCard(payload, title) {
        const response = payload?.raw_response ?? payload?.data ?? payload ?? {};
        const status = String(payload?.status ?? response?.status ?? 'success').toLowerCase();
        const message = payload?.message ?? response?.message ?? title ?? 'Verification completed';
        const rawResponse = JSON.stringify(response, null, 2);
        const cardId = `wallet2bank-verify-raw-${Date.now()}`;
        const bankName = response?.data?.bank_name ?? response?.bank_name ?? '';
        const accountName = response?.data?.account_name ?? response?.account_name ?? '';
        const accountNumber = response?.data?.account_number ?? response?.account_number ?? '';

        return `
            <div class="card border-0 shadow-sm mb-0" style="background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);">
                <div class="card-body py-3">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <div class="small text-uppercase text-muted font-weight-bold">Verification result</div>
                            <h6 class="mb-0">${escapeHtml(title || 'Bank verification')}</h6>
                        </div>
                        <span class="badge badge-${status === 'success' ? 'success' : (status === 'failed' ? 'danger' : 'warning')} text-uppercase px-3 py-2">${escapeHtml(status)}</span>
                    </div>
                    <div class="row">
                        <div class="col-md-7 mb-2">
                            <div class="rounded border bg-white px-3 py-2 h-100">
                                <small class="text-muted d-block text-uppercase mb-1">Message</small>
                                <div class="font-weight-bold text-dark">${escapeHtml(message)}</div>
                            </div>
                        </div>
                        <div class="col-md-5 mb-2">
                            <div class="rounded border bg-white px-3 py-2 h-100">
                                <small class="text-muted d-block text-uppercase mb-1">Status</small>
                                <div class="font-weight-bold text-dark">${escapeHtml(response?.status ?? status)}</div>
                            </div>
                        </div>
                        ${bankName ? `
                            <div class="col-md-4 mb-2">
                                <div class="rounded border bg-white px-3 py-2 h-100">
                                    <small class="text-muted d-block text-uppercase mb-1">Bank</small>
                                    <div class="font-weight-bold text-dark">${escapeHtml(bankName)}</div>
                                </div>
                            </div>
                        ` : ''}
                        ${accountName ? `
                            <div class="col-md-4 mb-2">
                                <div class="rounded border bg-white px-3 py-2 h-100">
                                    <small class="text-muted d-block text-uppercase mb-1">Account name</small>
                                    <div class="font-weight-bold text-dark">${escapeHtml(accountName)}</div>
                                </div>
                            </div>
                        ` : ''}
                        ${accountNumber ? `
                            <div class="col-md-4 mb-2">
                                <div class="rounded border bg-white px-3 py-2 h-100">
                                    <small class="text-muted d-block text-uppercase mb-1">Account number</small>
                                    <div class="font-weight-bold text-dark">${escapeHtml(accountNumber)}</div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="mt-2">
                        <a class="small font-weight-bold text-primary" data-toggle="collapse" href="#${cardId}" role="button" aria-expanded="false" aria-controls="${cardId}">
                            View raw response
                        </a>
                        <div class="collapse mt-2" id="${cardId}">
                            <pre class="bg-light border rounded p-2 mb-0" style="white-space:pre-wrap;max-height:220px;overflow:auto;">${escapeHtml(rawResponse)}</pre>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function submitForm(){
        var inputs = document.getElementById("initialize").getElementsByTagName("input");
        // Loop through each input and perform validation
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            // Check if the input is required and empty
            if (input.hasAttribute("required") && input.value.trim() === "") {
                alert("The " + input.name + " field is required" );
                return;
            }
        }

        $.LoadingOverlay("show");
                                                    document.forms["initialize"].submit();
        }


        $(document).ready(function () {
            const manualResolutionNote = document.getElementById('manual-resolution-note');
            const verifyBankSection = document.getElementById('verify-bank-section');
            const submitButton = document.querySelector('#buy-buttonx');
            const transferModes = document.querySelectorAll('input[name="transfer_mode"]');
            const transferModeFallback = document.querySelector('input[type="hidden"][name="transfer_mode"]');
            const updateTransferModeUi = function () {
                const selectedMode = document.querySelector('input[name="transfer_mode"]:checked')?.value
                    || transferModeFallback?.value
                    || 'auto_share';
                if (manualResolutionNote) {
                    manualResolutionNote.style.display = selectedMode === 'manual' ? 'block' : 'none';
                }
                if (verifyBankSection) {
                    verifyBankSection.style.display = selectedMode === 'manual' ? 'none' : 'block';
                }
                if (submitButton) {
                    submitButton.textContent = selectedMode === 'manual' ? 'PROCEED TO WHATSAPP' : 'PROCEED';
                }
            };

            if (transferModes.length > 0) {
                transferModes.forEach(function (radio) {
                    radio.addEventListener('change', updateTransferModeUi);
                });
            }

            $("#amount").val('');
            $('#product').val('');
            $('#amount-div').hide();
            $('#payment_method').val('');
            updateTransferModeUi();

            $('#product').on('change', function () {
                $('#agreement').prop('checked', false);
            var fixed_price = $('#product').find(':selected').data('fixed_price');
            var system_price = $('#product').find(':selected').data('system_price');
            var discounted_rate = $('#product').find(':selected').data('discounted_rate');
            var max = $('#product').find(':selected').data('max');
            var min = $('#product').find(':selected').data('min');
            var product = $('#product').val();
            var instruction = $('#product').find(':selected').data('instruction');
            $("#amount").val('');
            $('#receive-div').hide()
            $('#payment-div').hide()


            if (product == '') {
                $("#amount").val('');
                $('#rate').val('');
                $('#rate-div').hide();
                $("#instruction-div").hide();
                $('#receive').val('');
                $('#payment_method').val('');
                $('#amount-div').hide()

                return;
            }else{
                var image = $('#product').find(':selected').data('image');
                var title = $('#product').find(':selected').data('name');
                var description = $('#product').find(':selected').data('description');

                $('#product-image-div').show();
                $("#product-image").attr("src", image);
                $("#product-title").html(title);
                $("#product-description").html(description);
                $("#instruction-div").show();
                $("#rate-div").show();
                $("#rate").val(discounted_rate);
                $("#instruction").html(instruction);

                if(min != '' && max != ''){
                    $("#airtime-range").html('The Minimum and Maximum amount for '+title + ' is '+min + ' and ' +max + ' respectively');
                    $("#airtime-range").show();
                }else{
                    $("#airtime-range").hide();
                }

                $("#amount").attr({
                    "max": max,
                    "min": min,
                });
                $('#amount-div').show()

            }
        });

        $('#payment_method').on('change', function () {
            if($('#payment_method').val() == 'Transfer to Bank Account'){
                $('#bank-details-div').show()
                $("#bank").attr({
                    "required": '',
                });
                $("#account_number").attr({
                    "required": '',
                });
                $("#account_name").attr({
                    "required": '',
                });
            }else{
                $('#bank-details-div').hide()
                $("#bank").removeAttr('required');
                $("#account_number").removeAttr('required');
                $("#account_name").removeAttr('required');
            }
            var fixed_price = $('#product').find(':selected').data('fixed_price');
        });

        $("#amount").keyup(function(){
            var rate = parseInt($('#rate').val());
            var amount = parseInt($('#amount').val());
            var min = parseInt($('#amount').attr('min')) ?? 50;
            var max = parseInt($('#amount').attr('max'));

            var receive = 0;
            if(amount > 0 && amount >= min && amount <= max){
                receive = amount - ((rate/100) * amount);
                $('#receive-div').show();
                $('#receive').val(receive);
                $('#payment-div').show();
            }else{
                $('#receive-div').hide();
                $('#payment-div').hide();
                $('#receive').val('');
            }
        });

    });
</script>

@endsection
