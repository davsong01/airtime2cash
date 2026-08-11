@extends('sneat.layouts.app')

@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .a2c-workspace { --a2c-green: #00a86b; --a2c-ink: #263446; position: relative; isolation: isolate; }
        .a2c-workspace::before { content: ''; position: absolute; z-index: -1; inset: -2rem -1.5rem auto; height: 360px; border-radius: 2.25rem; pointer-events: none; }
        .a2c-card { border: 1px solid rgba(67,89,113,.1); border-radius: 1.15rem; background: rgba(255,255,255,.92); box-shadow: 0 1rem 2.6rem rgba(67,89,113,.09); overflow: hidden; backdrop-filter: blur(12px); }
        .a2c-mode-deck { padding: 1.35rem; }
        .a2c-mode-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .a2c-kicker { display: inline-flex; align-items: center; gap: .4rem; color: var(--a2c-green); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .conversion-mode-option { min-height: 104px; padding: 1rem 1.1rem; border: 1px solid rgba(67,89,113,.13); border-radius: .9rem; background: rgba(255,255,255,.76); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease; }
        .conversion-mode-option:hover { transform: translateY(-2px); border-color: rgba(0,168,107,.42); box-shadow: 0 .75rem 1.5rem rgba(67,89,113,.09); }
        .btn-check:checked + .conversion-mode-option { border-color: var(--a2c-green); background: linear-gradient(135deg, rgba(0,168,107,.1), rgba(3,195,236,.04)); box-shadow: 0 0 0 .2rem rgba(0,168,107,.09); }
        .a2c-section-head { display: flex; align-items: center; gap: .85rem; padding: 1.25rem 1.35rem; border-bottom: 1px solid rgba(67,89,113,.08); }
        .a2c-section-number { display: inline-flex; flex: 0 0 36px; width: 36px; height: 36px; align-items: center; justify-content: center; border-radius: 12px; color: #fff; background: linear-gradient(145deg, var(--a2c-green), #008a64); font-weight: 800; box-shadow: 0 .5rem 1rem rgba(0,168,107,.2); }
        .a2c-section-head h5 { margin: 0 0 .15rem; color: var(--a2c-ink); }
        .a2c-section-head small { color: var(--bs-secondary-color); }
        .a2c-network-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; }
        .a2c-network-option { position: relative; display: flex; min-width: 0; min-height: 112px; padding: .9rem .65rem; align-items: center; justify-content: center; flex-direction: column; gap: .5rem; border: 1px solid rgba(67,89,113,.13); border-radius: .9rem; color: var(--a2c-ink); background: var(--bs-card-bg); transition: .2s ease; }
        .a2c-network-option:hover { transform: translateY(-2px); border-color: rgba(0,168,107,.4); box-shadow: 0 .65rem 1.35rem rgba(67,89,113,.09); }
        .a2c-network-option.is-active { border-color: var(--a2c-green); background: rgba(0,168,107,.055); box-shadow: 0 0 0 .18rem rgba(0,168,107,.09); }
        .a2c-network-option.is-active::after { content: '\2713'; position: absolute; top: .45rem; right: .5rem; display: grid; width: 21px; height: 21px; place-items: center; border-radius: 50%; color: #fff; background: var(--a2c-green); font-size: .72rem; font-weight: 800; }
        .a2c-network-logo { display: grid; width: 45px; height: 45px; place-items: center; overflow: hidden; border-radius: 14px; background: #fff; box-shadow: 0 .35rem .8rem rgba(67,89,113,.1); }
        .a2c-network-logo img { width: 100%; height: 100%; object-fit: contain; padding: .25rem; }
        .a2c-network-fallback { display: none; width: 100%; height: 100%; align-items: center; justify-content: center; color: var(--a2c-green); font-weight: 800; }
        .a2c-network-option strong { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .86rem; }
        /* unify calculator and form body styling */
        .a2c-calculator, .a2c-form-body { padding: 1.35rem; background: linear-gradient(145deg, rgba(0,168,107,.055), rgba(3,195,236,.035)); border-bottom: 1px solid rgba(67,89,113,.08); }
        .a2c-calculator .form-control, .a2c-calculator .input-group-text, .a2c-form-body .form-control, .a2c-form-body .input-group-text { min-height: 46px; }
        .a2c-calculator .form-control, .a2c-form-body .form-control { font-size: 1rem; font-weight: 600; }
        .a2c-rate-box { height: 100%; min-height: 46px; padding: .5rem .85rem; border: 1px solid rgba(67,89,113,.11); border-radius: .55rem; background: rgba(255,255,255,.8); display: flex; align-items: center; justify-content: space-between; }
        .a2c-rate-box small { color: var(--bs-secondary-color); }
        .a2c-rate-box strong { color: var(--a2c-green); font-size: 1rem; }
        .a2c-form-body { padding: 1.35rem; }
        .a2c-form-body .form-control, .a2c-form-body .form-select, .a2c-form-body .select2-selection { min-height: 46px; }
        .a2c-form-body .input-group-text { min-height: 46px; }
        .a2c-instruction-card { position: sticky; top: 1.25rem; }
        .a2c-instruction-card .card-header { background: linear-gradient(145deg, rgba(3,195,236,.08), transparent); }
        .a2c-instruction-card #instruction { line-height: 1.72; }
        .a2c-instruction-card #instruction ol, .a2c-instruction-card #instruction ul { padding-left: 1.2rem; }
        .a2c-trust-strip { display: flex; padding: .85rem 1rem; gap: .75rem; align-items: center; border-top: 1px solid rgba(67,89,113,.08); color: var(--bs-secondary-color); background: rgba(67,89,113,.025); font-size: .78rem; }
        .a2c-action-bar { display: flex; padding: 1rem 1.25rem; gap: 1rem; align-items: center; justify-content: space-between; border-top: 1px solid rgba(67,89,113,.08); background: rgba(255,255,255,.94); }
        .a2c-action-stack { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
        .a2c-action-note { display: flex; align-items: center; gap: .55rem; color: var(--bs-secondary-color); font-size: .8rem; }
        .a2c-action-bar .purchase-submit { min-width: 230px; min-height: 48px; }
        .a2c-secure-flow { background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(243,250,248,.94)); }
        .a2c-secure-shell { max-width: 620px; margin: 0 auto; }
        .a2c-stage-mark { display: inline-flex; width: 54px; height: 54px; align-items: center; justify-content: center; border-radius: 18px; color: #fff; background: linear-gradient(145deg, #00a86b, #008c68); box-shadow: 0 .65rem 1.4rem rgba(0,168,107,.24); }
        .a2c-stage-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; padding: 1rem; border: 1px solid rgba(67,89,113,.1); border-radius: 1rem; background: rgba(255,255,255,.78); }
        .a2c-stage-summary small, .a2c-stage-summary strong { display: block; }
        .a2c-stage-summary small { color: var(--bs-secondary-color); margin-bottom: .2rem; }
    .a2c-stage-summary strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .a2c-secret-input { min-height: 52px; font-size: 1.15rem; font-weight: 700; letter-spacing: .28em; text-align: center; }
    .a2c-flow-error { border: 0; border-left: 4px solid var(--bs-danger); }
    .a2c-step-pill { display: inline-flex; gap: .4rem; align-items: center; padding: .38rem .7rem; border-radius: 999px; color: #00875a; background: rgba(0,168,107,.1); font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
    .a2c-native-select { position: absolute !important; width: 1px !important; height: 1px !important; opacity: 0 !important; pointer-events: none !important; }
    .a2c-provider-health { display:flex; margin: 0 0 1rem; padding: .9rem 1rem; align-items:center; justify-content:space-between; gap: 1rem; border: 1px solid rgba(67,89,113,.12); border-radius: 1rem; background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(242,247,251,.94)); box-shadow: 0 .6rem 1.4rem rgba(67,89,113,.08); }
    .a2c-provider-health strong, .a2c-provider-health small { display:block; }
    .a2c-provider-health-label { display:inline-flex; margin-bottom:.2rem; color: var(--bs-secondary-color); font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .a2c-provider-health small { color: var(--bs-secondary-color); }
    .a2c-provider-health-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .72rem; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; white-space:nowrap; }
    .a2c-provider-health-badge.unstable { color:#991b1b; background:rgba(239,68,68,.14); }
    .a2c-provider-health-badge.degraded { color:#111827; background:rgba(17,24,39,.12); }
    .a2c-provider-health-badge.stable { color:#9a6700; background:rgba(245,158,11,.15); }
    .a2c-provider-health-badge.healthy { color:#166534; background:rgba(34,197,94,.14); }
    [data-bs-theme="dark"] .a2c-provider-health { border-color: rgba(67,89,113,.18); background: rgba(43,44,64,.94); }
    [data-bs-theme="dark"] .a2c-provider-health-badge.degraded { color:#e5e7eb; background: rgba(75,85,99,.4); }
    [data-bs-theme="dark"] .a2c-card, [data-bs-theme="dark"] .a2c-mode-deck, [data-bs-theme="dark"] .conversion-mode-option, [data-bs-theme="dark"] .a2c-network-option, [data-bs-theme="dark"] .a2c-action-bar { background-color: rgba(43,44,64,.94); }
    [data-bs-theme="dark"] .a2c-rate-box { background: rgba(43,44,64,.72); }
        @media (max-width: 1199.98px) { .a2c-action-bar { align-items: stretch; flex-direction: column; } .a2c-action-stack { width: 100%; } .a2c-action-bar .purchase-submit { width: 100%; min-width: 0; } }
        @media (max-width: 991.98px) { .a2c-instruction-card { position: static; } }
        @media (max-width: 767.98px) { .a2c-network-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .a2c-mode-heading { align-items: flex-start; flex-direction: column; } }
        @media (max-width: 575.98px) { .a2c-workspace::before { inset-inline: -.5rem; } .a2c-card { border-radius: .95rem; } .a2c-mode-deck, .a2c-form-body, .a2c-calculator { padding: 1rem; } .conversion-mode-option { min-height: 92px; padding: .8rem; } .a2c-network-option { min-height: 102px; } .a2c-stage-summary { grid-template-columns: 1fr; } .a2c-secure-flow { padding: 1.25rem !important; } }


    </style>

@endsection

@php
    $manualProducts = $category->products->where('manual_status', 'active')->count();
    $autoShareProducts = $category->products->where('auto_share_status', 'active')->count();
    $defaultTransferMode = old('transfer_mode', $manualProducts ? 'manual' : 'auto_share');
    $autoTransferInstruction = $category->products->firstWhere('auto_share_status', 'active')?->auto_share_instruction;
    $activeProviderAvailability = $activeProvider?->availability_status_class;
    $activeProviderAvailabilityLabel = $activeProvider?->availability_status_label;
@endphp

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Conversion',
        'title' => 'Airtime to Cash',
        'subtitle' => 'Choose your network, enter the amount, and complete the conversion.',
    ])

    @include('sneat.layouts.alerts')
    <div
        id="pending-transaction-alert"
        class="alert alert-warning"
        style="display:none"
    ></div>
    <div class="a2c-workspace">
        <form action="{{ route('initialize.airtime2cashtransaction') }}" method="POST" id="initialize" class="purchase-form">
            @csrf

            <section class="a2c-card a2c-mode-deck mb-4">
                <div class="a2c-mode-heading">
                    <div>
                        <span class="a2c-kicker"><i class="bx bx-transfer-alt"></i> Transfer method</span>
                        <h4 class="mb-0 mt-1">How would you like to transfer?</h4>
                    </div>
                    <span class="badge bg-label-success rounded-pill"><i class="bx bx-shield-quarter me-1"></i>Secure conversion</span>
                </div>
                @if($activeProvider && $activeProviderAvailability && $activeProvider->availability_checked_at)
                    <div class="a2c-provider-health">
                        <div>
                            <span class="a2c-provider-health-label">Auto Transfer Status</span>
                            <small>Checked {{ $activeProvider->availability_checked_at->diffForHumans() }}</small>
                            @if($activeProviderAvailability === 'unstable')
                                <small class="text-danger d-block mt-1">Auto transfer looks unstable right now, so manual processing may be the safer option.</small>
                            @endif
                        </div>
                        <span class="a2c-provider-health-badge {{ $activeProviderAvailability }}">
                            <i class="bx bx-pulse"></i>
                            {{ $activeProviderAvailabilityLabel }}
                        </span>
                    </div>
                @endif
                <div class="row g-3 conversion-mode-options">
                    <div class="col-md-6">
                        <input class="btn-check" type="radio" name="transfer_mode" id="transfer-mode-manual" value="manual" autocomplete="off" @checked($defaultTransferMode === 'manual') @disabled(!$manualProducts)>
                        <label class="conversion-mode-option" for="transfer-mode-manual">
                            <span class="conversion-mode-icon bg-label-success"><i class="bx bx-transfer"></i></span>
                            <span><strong>Manual Transfer</strong><small>Send airtime to the provided destination number</small></span>
                            <i class="bx bx-check-circle conversion-mode-check"></i>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <input class="btn-check" type="radio" name="transfer_mode" id="transfer-mode-auto" value="auto_share" autocomplete="off" @checked($defaultTransferMode === 'auto_share') @disabled(!$autoShareProducts)>
                        <label class="conversion-mode-option" for="transfer-mode-auto">
                            <span class="conversion-mode-icon bg-label-warning"><i class="bx bx-bolt-circle"></i></span>
                            <span><strong>Auto Transfer</strong><small>Authorize transfer securely with SIM PIN and OTP</small></span>
                            <i class="bx bx-check-circle conversion-mode-check"></i>
                        </label>
                    </div>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div id="conversion-details-panel">
                        <section class="a2c-card mb-4">
                            <div class="a2c-section-head">
                                <span class="a2c-section-number">1</span>
                                <div><h5>Select network</h5><small>Choose the network holding the airtime.</small></div>
                            </div>
                            <div class="a2c-form-body">
                                <div class="a2c-network-grid">
                                    @foreach ($category->products as $item)
                                        @php
                                            $availableForDefaultMode = $defaultTransferMode === 'auto_share'
                                                ? $item->auto_share_status === 'active'
                                                : $item->manual_status === 'active';
                                        @endphp
                                        <button type="button" class="a2c-network-option {{ $availableForDefaultMode ? '' : 'd-none' }}" data-product-id="{{ $item->id }}" data-manual-status="{{ $item->manual_status }}" data-auto-share-status="{{ $item->auto_share_status }}">
                                            <span class="a2c-network-logo">
                                                <img src="{{ asset($item->image) }}" alt="{{ $item->display_name }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                <span class="a2c-network-fallback">{{ strtoupper(substr($item->display_name, 0, 2)) }}</span>
                                            </span>
                                            <strong>{{ $item->display_name }}</strong>
                                        </button>
                                    @endforeach
                                </div>
                                <label for="product" class="visually-hidden">Network</label>

                                <select class="a2c-native-select" name="product" id="product" required tabindex="-1">
                                    <option value="">Select a network</option>

                                    @foreach ($category->products as $item)
                                        <option
                                            value="{{ $item->id }}"
                                            data-manual-status="{{ $item->manual_status }}"
                                            data-auto-share-status="{{ $item->auto_share_status }}"
                                            data-manual-rate="{{ $item->manual_discounted_rate }}"
                                            data-auto-share-rate="{{ $item->auto_share_discounted_rate }}"
                                            data-min="{{ $item->min }}"
                                            data-max="{{ $item->max }}"
                                            data-name="{{ $item->display_name }}"
                                            data-manual-instruction="{{ $item->instruction }}"
                                            data-auto-share-instruction="{{ $item->auto_share_instruction }}"
                                        >
                                            {{ $item->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </section>

                        <section class="a2c-card mb-4" id="amount-div" style="display:none">
                            <div class="a2c-section-head">
                                <span class="a2c-section-number">2</span>
                                <div><h5>Enter conversion details</h5><small>See your estimated payout before continuing.</small></div>
                            </div>
                            <div class="a2c-calculator">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="amount" class="form-label">Airtime amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ getSettings()['currency'] }}</span>
                                            <input class="form-control" id="amount" name="amount" placeholder="0.00" type="number" required>
                                        </div>
                                        <div class="form-text" id="airtime-range"></div>
                                    </div>
                                    <div class="col-md-6" id="receive-div" style="display:none">
                                        <label for="receive" class="form-label">You will receive</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ getSettings()['currency'] }}</span>
                                            <input class="form-control text-success" id="receive" name="receive" type="number" disabled>
                                        </div>
                                    </div>
                                    <!-- charge rate now shown under the airtime amount -->
                                    <small class="form-text text-muted mt-2" id="rate-text" style="display:none">Charge rate: <strong class="text-success"><span id="rate-display">0</span>%</strong></small>
                                    <input id="rate" name="rate" type="hidden">
                                </div>
                            </div>
                            <div class="a2c-form-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone number sending airtime</label>
                                        <div class="input-group"><span class="input-group-text"><i class="bx bx-mobile-alt"></i></span><input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" placeholder="0800 000 0000" inputmode="tel" required></div>
                                        <div class="form-text">Use the number that currently holds the airtime.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group"><span class="input-group-text"><i class="bx bx-envelope"></i></span><input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email ?? old('email') }}" required></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="a2c-card mb-4" id="payment-div" style="display:none">
                            <div class="a2c-section-head">
                                <span class="a2c-section-number">3</span>
                                <div><h5>Choose payout destination</h5><small>Auto Transfer is settled directly to your wallet.</small></div>
                            </div>
                            <div class="a2c-form-body">
                                <label for="payment_method" class="form-label">Payout destination</label>
                                <select class="form-select modern-select2" name="payment_method" id="payment_method" data-placeholder="Select payout destination" required>
                                    <option value="">Select payout destination</option>
                                    <option value="Transfer to Bank Account">Bank account</option>
                                    <option value="Transfer to Wallet">Airtime2Cash wallet</option>
                                </select>
                                <div id="bank-details-div" class="mt-4" style="display:none">
                                    <div class="rounded border bg-body-tertiary p-3 p-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-3"><i class="bx bx-building-house text-primary fs-5"></i><h6 class="mb-0">Bank account details</h6></div>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label for="bank" class="form-label">Bank</label><select class="form-select modern-select2" name="bank" id="bank" data-placeholder="Search banks"><option value="">Select a bank</option>@foreach($banks as $bank)<option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>@endforeach</select></div>
                                            <div class="col-md-4"><label for="account_number" class="form-label">Account number</label><input class="form-control" id="account_number" name="account_number" type="text" inputmode="numeric"></div>
                                            <div class="col-md-4"><label for="account_name" class="form-label">Account name</label><input class="form-control" id="account_name" name="account_name" type="text"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="a2c-card a2c-secure-flow mb-4 p-4 p-md-5" id="auto-secure-flow" style="display:none">
                        <div class="a2c-secure-shell">
                            <div class="alert alert-danger a2c-flow-error" id="auto-flow-error" style="display:none"></div>
                            <div id="auto-pin-stage">
                                <div class="text-center mb-4">
                                    <span class="a2c-stage-mark mb-3"><i class="bx bx-lock-alt fs-3"></i></span>
                                    <div><span class="a2c-step-pill"><i class="bx bx-shield-quarter"></i> Secure authorization</span></div>
                                    <h3 class="mt-3 mb-1">Enter SIM PIN</h3>
                                    <p class="text-muted mb-0"><strong id="auto-pin-phone" class="text-body"></strong><br>Enter your airtime share PIN to authorize the transfer.</p>
                                </div>
                                <div class="a2c-stage-summary mb-4">
                                    <span><small>Network</small><strong id="auto-summary-network">-</strong></span>
                                    <span><small>Airtime</small><strong id="auto-summary-amount">-</strong></span>
                                    <span><small>To wallet</small><strong id="auto-summary-payout">-</strong></span>
                                </div>
                                <div class="mb-3">
                                    <label for="share-pin" class="form-label">Airtime Share PIN</label>
                                    <input type="password" class="form-control a2c-secret-input" id="share-pin" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8" autocomplete="off" placeholder="----">
                                    <div class="form-text text-center">The PIN you use to share or transfer airtime from your SIM.</div>
                                </div>
                                <button type="button" class="btn btn-link text-secondary px-0" id="edit-auto-details"><i class="bx bx-left-arrow-alt me-1"></i>Edit conversion details</button>
                            </div>
                            <div id="auto-otp-stage" style="display:none">
                                <div class="text-center mb-4">
                                    <span class="a2c-stage-mark mb-3"><i class="bx bx-message-rounded-dots fs-3"></i></span>
                                    <div><span class="a2c-step-pill"><i class="bx bx-check-shield"></i> Final confirmation</span></div>
                                    <h3 class="mt-3 mb-1">Enter OTP</h3>
                                    <p class="text-muted mb-0">OTP sent to <strong id="auto-otp-phone" class="text-body"></strong><br>Enter the OTP to authorize the airtime transfer.</p>
                                </div>
                                <div class="mb-3">
                                    <label for="auto-otp" class="form-label">OTP Code</label>
                                    <input type="text" class="form-control a2c-secret-input" id="auto-otp" inputmode="numeric" pattern="[0-9]*" maxlength="10" autocomplete="one-time-code" placeholder="------">
                                </div>
                                <div class="d-flex justify-content-center"><button type="button" class="btn btn-sm btn-label-secondary" id="resend-auto-otp"><i class="bx bx-refresh me-1"></i>Resend OTP</button></div>
                            </div>
                        </div>
                    </section>

                    <section class="a2c-card a2c-action-bar">
                        <div class="a2c-action-stack">
                            <span class="a2c-action-note"><i class="bx bx-lock-alt fs-5 text-success"></i>Your conversion details are transmitted securely.</span>
                            <label class="conversion-agreement" for="agreement" id="agreement-panel">
                                <input type="checkbox" class="form-check-input conversion-agreement-check" id="agreement" name="agreement" value="1" required>
                                <span class="conversion-agreement-icon"><i class="bx bx-check-shield"></i></span>
                                <span class="conversion-agreement-copy"><strong>I have read and agree to the instructions</strong><small>Confirm before submitting this conversion.</small></span>
                            </label>
                        </div>
                        <button id="buy-button" class="purchase-submit btn btn-primary" type="submit">
                            <i class="bx bx-transfer me-1"></i>
                            <span>Submit conversion</span>
                        </button>
                    </section>
                </div>

                <div class="col-lg-4">
                    <aside class="a2c-card a2c-instruction-card">
                        <div class="card-header d-flex align-items-center gap-3 border-bottom p-4">
                            <span class="purchase-heading-icon bg-label-info"><i class="bx bx-info-circle fs-4"></i></span>
                            <div><h5 class="mb-1">Transfer instructions</h5><small class="text-muted" id="instruction-context">Instructions depend on the selected network.</small></div>
                        </div>
                        <div class="card-body p-4">
                            <div id="instruction-empty" class="text-center py-4">
                                <span class="purchase-heading-icon bg-label-secondary mx-auto mb-3"><i class="bx bx-mobile-alt fs-4"></i></span>
                                <h6>Select a network</h6>
                                <p class="text-muted small mb-0">The correct transfer instructions will appear here.</p>
                            </div>
                            <div id="instruction-div" style="display:none">
                                <div id="instruction" class="text-body mb-4"></div>
                            </div>
                        </div>
                        <div class="a2c-trust-strip"><i class="bx bx-shield-quarter fs-5 text-success"></i><span>Never share your SIM PIN or OTP with a person. Enter it only in the secure Auto Transfer step.</span></div>
                    </aside>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>

    <script>
        $(function () {
            'use strict';

            /*
            |--------------------------------------------------------------------------
            | Elements
            |--------------------------------------------------------------------------
            */

            const form = document.getElementById('initialize');

            if (!form) {
                return;
            }

            const $form = $(form);
            const $product = $('#product');
            const $amount = $('#amount');
            const $receive = $('#receive');
            const $phone = $('#phone');
            const $email = $('#email');
            const $rate = $('#rate');
            const $rateDisplay = $('#rate-display');
            const $rateText = $('#rate-text');
            const $airtimeRange = $('#airtime-range');

            const $paymentMethod = $('#payment_method');
            const $bank = $('#bank');
            const $accountNumber = $('#account_number');
            const $accountName = $('#account_name');

            const $amountDiv = $('#amount-div');
            const $receiveDiv = $('#receive-div');
            const $paymentDiv = $('#payment-div');
            const $bankDetailsDiv = $('#bank-details-div');

            const $conversionDetailsPanel = $('#conversion-details-panel');
            const $autoSecureFlow = $('#auto-secure-flow');
            const $autoPinStage = $('#auto-pin-stage');
            const $autoOtpStage = $('#auto-otp-stage');
            const $autoFlowError = $('#auto-flow-error');

            const $sharePin = $('#share-pin');
            const $autoOtp = $('#auto-otp');
            const $buyButton = $('#buy-button');
            const $resendOtpButton = $('#resend-auto-otp');

            const $agreement = $('#agreement');
            const $agreementPanel = $('#agreement-panel');

            const productOptions = $product.find('option[value!=""]').clone();
            /*
            |--------------------------------------------------------------------------
            | Server values
            |--------------------------------------------------------------------------
            */

            const currency = @json(
                html_entity_decode(
                    strip_tags(getSettings()['currency'])
                )
            );

            const defaultAutoInstruction = @json($autoTransferInstruction);
            const csrfToken = @json(csrf_token());

            const endpoints = {
                initiate: @json(route('initialize.airtime2cashtransaction')),
                complete: @json(route('airtime2cash.auto.complete')),
                resend: @json(route('airtime2cash.auto.resend-otp'))
            };

            /*
            |--------------------------------------------------------------------------
            | State
            |--------------------------------------------------------------------------
            */

            const AUTO_STAGE_DETAILS = 'details';
            const AUTO_STAGE_PIN = 'pin';
            const AUTO_STAGE_OTP = 'otp';

            let autoStage = AUTO_STAGE_DETAILS;
            let autoTransactionId = null;
            let requestInProgress = false;

            const originalProductOptions = $product
                .find('option')
                .filter(function () {
                    return this.value !== '';
                })
                .clone();

            /*
            |--------------------------------------------------------------------------
            | Select2
            |--------------------------------------------------------------------------
            */

            $('.modern-select2').each(function () {
                const $select = $(this);

                $select.select2({
                    width: '100%',
                    placeholder: $select.data('placeholder') || '',
                    minimumResultsForSearch: 0
                });
            });

            /*
            |--------------------------------------------------------------------------
            | General helpers
            |--------------------------------------------------------------------------
            */

            function isAutoTransfer() {
                return $('input[name="transfer_mode"]:checked').val()
                    === 'auto_share';
            }

            function normalizePhone(value) {
                return String(value || '').replace(/\s+/g, '');
            }

            function formatMoney(value) {
                const amount = Number(value);

                if (!Number.isFinite(amount)) {
                    return currency + '0.00';
                }

                return currency + amount.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function setActionButton(
                label,
                disabled = false,
                icon = 'bx-transfer'
            ) {
                $buyButton
                    .prop('disabled', disabled)
                    .html(
                        `<i class="bx ${icon} me-1"></i>` +
                        `<span>${label}</span>`
                    );
            }

            function setRequestInProgress(value) {
                requestInProgress = value;
            }

            function showAutoError(message) {
                const normalizedMessage = String(message || '').trim();

                $autoFlowError
                    .text(normalizedMessage)
                    .toggle(normalizedMessage !== '');

                if (normalizedMessage !== '' && $autoSecureFlow.is(':visible')) {
                    $autoFlowError[0]?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }

            function clearAutoError() {
                showAutoError('');
            }

            function extractFirstError(data, fallbackMessage) {
                if (
                    data
                    && typeof data === 'object'
                    && data.errors
                    && typeof data.errors === 'object'
                ) {
                    const messages = Object.values(data.errors).flat();

                    if (messages.length > 0) {
                        return String(messages[0]);
                    }
                }

                if (
                    data
                    && typeof data === 'object'
                    && typeof data.message === 'string'
                    && data.message.trim() !== ''
                ) {
                    return data.message;
                }

                return fallbackMessage;
            }

            async function parseResponse(response) {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    return response.json().catch(function () {
                        return {};
                    });
                }

                const text = await response.text();

                return {
                    message: text
                };
            }

            async function postJson(url, payload) {
                let response;

                try {
                    response = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                } catch (error) {
                    throw new Error(
                        'Unable to connect to the server. Check your internet connection and try again.'
                    );
                }

                const data = await parseResponse(response);

                if (!response.ok) {
                    throw new Error(
                        extractFirstError(
                            data,
                            'The request could not be completed. Please try again.'
                        )
                    );
                }

                return data;
            }

            function getResponseData(response) {
                if (
                    response
                    && typeof response === 'object'
                    && response.data
                    && typeof response.data === 'object'
                ) {
                    return response.data;
                }

                return response || {};
            }

            /*
            |--------------------------------------------------------------------------
            | Instruction helpers
            |--------------------------------------------------------------------------
            */

            function showInstruction(instruction) {
                if (!instruction) {
                    $('#instruction').empty();
                    $('#instruction-div').hide();
                    $('#instruction-empty').show();

                    return;
                }

                $('#instruction').html(instruction);
                $('#instruction-div').show();
                $('#instruction-empty').hide();
            }


            function resetBankDetails() {
                $bankDetailsDiv.hide();

                $bank
                    .prop('required', false)
                    .val('')
                    .trigger('change');

                $accountNumber
                    .prop('required', false)
                    .val('');

                $accountName
                    .prop('required', false)
                    .val('');
            }

            function resetSelectedProductDetails() {
                $('.a2c-network-option').removeClass('is-active');

                $agreement.prop('checked', false);
                $agreementPanel.removeClass('is-required');

                $amount
                    .val('')
                    .removeAttr('min')
                    .removeAttr('max');

                $receive.val('');
                $rate.val('');
                $rateDisplay.text('0');
                $airtimeRange.empty().hide();

                $paymentMethod
                    .val('')
                    .trigger('change');

                $rateText.hide();
                $amountDiv.hide();
                $receiveDiv.hide();
                $paymentDiv.hide();

                resetBankDetails();
            }

            function resetAutoFlow() {
                autoStage = AUTO_STAGE_DETAILS;
                autoTransactionId = null;
                setRequestInProgress(false);

                $sharePin.val('');
                $autoOtp.val('');

                $autoSecureFlow.hide();
                $autoPinStage.show();
                $autoOtpStage.hide();
                $conversionDetailsPanel.show();

                clearAutoError();
            }

            /*
            |--------------------------------------------------------------------------
            | Product selection
            |--------------------------------------------------------------------------
            */

            function updateSelectedProduct() {
                const $selected = $product.find(':selected');
                const productId = String($selected.val() || '');

                resetSelectedProductDetails();

                if (productId === '') {
                    showInstruction(
                        isAutoTransfer()
                            ? defaultAutoInstruction
                            : null
                    );

                    return;
                }

                $(
                    `.a2c-network-option[data-product-id="${productId}"]`
                ).addClass('is-active');

                const rawRate = isAutoTransfer()
                    ? $selected.attr('data-auto_share_rate')
                        ?? $selected.attr('data-auto-share-rate')
                    : $selected.attr('data-manual_rate')
                        ?? $selected.attr('data-manual-rate');

                const rawInstruction = isAutoTransfer()
                    ? $selected.attr('data-auto_share_instruction')
                        ?? $selected.attr('data-auto-share-instruction')
                    : $selected.attr('data-manual_instruction')
                        ?? $selected.attr('data-manual-instruction');

                const selectedInstruction = isAutoTransfer()
                    ? rawInstruction || defaultAutoInstruction
                    : rawInstruction;

                const discountedRate = Number.parseFloat(rawRate);
                const minimum = Number.parseFloat(
                    $selected.attr('data-min')
                );
                const maximum = Number.parseFloat(
                    $selected.attr('data-max')
                );

                const normalizedRate = Number.isFinite(discountedRate)
                    ? discountedRate
                    : 0;

                $rate.val(normalizedRate);
                $rateDisplay.text(normalizedRate);

                if (Number.isFinite(minimum)) {
                    $amount.attr('min', minimum);
                }

                if (Number.isFinite(maximum)) {
                    $amount.attr('max', maximum);
                }

                if (
                    Number.isFinite(minimum)
                    && Number.isFinite(maximum)
                ) {
                    $airtimeRange
                        .text(
                            `Allowed range: ${currency}` +
                            `${minimum.toLocaleString()} - ` +
                            `${currency}${maximum.toLocaleString()}`
                        )
                        .show();
                }

                showInstruction(selectedInstruction);

                $rateText.show();
                $amountDiv.show();
            }

            /*
            |--------------------------------------------------------------------------
            | Amount calculation
            |--------------------------------------------------------------------------
            */

            function recalculatePayout() {
                const amount = Number.parseFloat($amount.val());
                const rate = Number.parseFloat($rate.val());
                const minimum = Number.parseFloat($amount.attr('min'));
                const maximum = Number.parseFloat($amount.attr('max'));

                const amountIsValid = Number.isFinite(amount)
                    && amount > 0
                    && Number.isFinite(rate)
                    && (
                        !Number.isFinite(minimum)
                        || amount >= minimum
                    )
                    && (
                        !Number.isFinite(maximum)
                        || amount <= maximum
                    );

                if (!amountIsValid) {
                    $receive.val('');
                    $receiveDiv.hide();
                    $paymentDiv.hide();

                    $paymentMethod
                        .val('')
                        .trigger('change');

                    resetBankDetails();

                    return;
                }

                const charge = (rate / 100) * amount;
                const payout = amount - charge;

                $receive.val(payout.toFixed(2));
                $receiveDiv.show();
                $paymentDiv.show();

                if (isAutoTransfer()) {
                    $paymentMethod
                        .val('Transfer to Wallet')
                        .trigger('change');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Detail validation
            |--------------------------------------------------------------------------
            */

            function validateConversionDetails() {
                clearAutoError();
                $agreementPanel.removeClass('is-required');

                if (!$product.val()) {
                    showAutoError('Please select a network to continue.');

                    document
                        .querySelector('.a2c-network-grid')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                    return false;
                }

                if (isAutoTransfer()) {
                    $paymentMethod
                        .val('Transfer to Wallet')
                        .trigger('change');
                }

                if (!form.reportValidity()) {
                    return false;
                }

                if (!$agreement.prop('checked')) {
                    $agreementPanel.addClass('is-required');
                    $agreement.trigger('focus');

                    return false;
                }

                return true;
            }

            /*
            |--------------------------------------------------------------------------
            | Auto-transfer payloads
            |--------------------------------------------------------------------------
            */

            function buildAutoInitiationPayload() {
                return {
                    product: $product.val(),
                    transfer_mode: 'auto_share',
                    amount: $amount.val(),
                    phone: normalizePhone($phone.val()),
                    email: $email.val(),
                    payment_method: 'Transfer to Wallet',
                    agreement: $agreement.prop('checked') ? 1 : 0,
                    share_pin: $sharePin.val()
                };
            }

            function extractTransactionId(response) {
                const data = getResponseData(response);

                return data.transaction_id
                    || data.transaction?.id
                    || data.transaction?.reference
                    || response?.transaction_id
                    || response?.transaction?.id
                    || response?.transaction?.reference
                    || null;
            }

            /*
            |--------------------------------------------------------------------------
            | Auto-transfer stage rendering
            |--------------------------------------------------------------------------
            */

            function openPinStage() {
                const $selected = $product.find(':selected');

                autoStage = AUTO_STAGE_PIN;
                autoTransactionId = null;

                $('#auto-pin-phone').text($phone.val());

                $('#auto-summary-network').text(
                    $selected.attr('data-name')
                    || $selected.text()
                );

                $('#auto-summary-amount').text(
                    formatMoney($amount.val())
                );

                $('#auto-summary-payout').text(
                    formatMoney($receive.val())
                );

                $sharePin.val('');
                $autoOtp.val('');

                clearAutoError();

                $conversionDetailsPanel.hide();
                $autoSecureFlow.show();
                $autoPinStage.show();
                $autoOtpStage.hide();

                setActionButton(
                    'Submit PIN and send OTP',
                    true,
                    'bx-lock-open-alt'
                );

                $autoSecureFlow[0]?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                window.setTimeout(function () {
                    $sharePin.trigger('focus');
                }, 250);
            }

            function openOtpStage(response) {
                const data = getResponseData(response);
                const transactionId = extractTransactionId(response);

                if (!transactionId) {
                    throw new Error(
                        'The PIN was accepted, but the server did not return a transaction reference.'
                    );
                }

                autoTransactionId = transactionId;
                autoStage = AUTO_STAGE_OTP;

                $autoPinStage.hide();
                $autoOtpStage.show();

                $('#auto-otp-phone').text(
                    data.phone
                    || response.phone
                    || $phone.val()
                );

                $autoOtp.val('');
                clearAutoError();

                setActionButton(
                    'Verify OTP and share airtime',
                    true,
                    'bx-bolt-circle'
                );

                window.setTimeout(function () {
                    $autoOtp.trigger('focus');
                }, 250);
            }

            function resetPendingFlow(message) {
                autoStage = AUTO_STAGE_DETAILS;
                autoTransactionId = null;

                $sharePin.val('');
                $autoOtp.val('');

                $autoSecureFlow.hide();
                $autoPinStage.show();
                $autoOtpStage.hide();
                $conversionDetailsPanel.show();

                $('#pending-transaction-alert')
                    .html(
                        '<strong>Transaction pending.</strong> '
                        + message
                        + ' Please do not submit another request while this transaction is being processed.'
                    )
                    .show();

                setActionButton(
                    'Continue to secure transfer',
                    false,
                    'bx-bolt-circle'
                );

                document
                    .getElementById('pending-transaction-alert')
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | Submit SIM share PIN
            |--------------------------------------------------------------------------
            */

            async function submitSharePin() {
                const pin = String($sharePin.val() || '');

                if (!/^\d{4,8}$/.test(pin)) {
                    showAutoError(
                        'Enter a valid airtime share PIN containing 4 to 8 digits.'
                    );

                    $sharePin.trigger('focus');

                    return;
                }

                if (requestInProgress) {
                    return;
                }

                setRequestInProgress(true);
                clearAutoError();

                setActionButton(
                    'Submitting PIN...',
                    true,
                    'bx-loader-alt bx-spin'
                );

                try {
                    const response = await postJson(
                        endpoints.initiate,
                        buildAutoInitiationPayload()
                    );

                    /*
                     * The OTP stage is opened only when the PIN request
                     * succeeds and returns a valid transaction reference.
                     */
                    openOtpStage(response);
                } catch (error) {
                    /*
                     * Stop the process at the PIN stage.
                     * Do not open the OTP stage.
                     */
                    autoStage = AUTO_STAGE_PIN;
                    autoTransactionId = null;

                    $autoPinStage.show();
                    $autoOtpStage.hide();

                    showAutoError(
                        error.message
                        || 'The PIN could not be submitted. Please check it and try again.'
                    );

                    setActionButton(
                        'Submit PIN and send OTP',
                        false,
                        'bx-lock-open-alt'
                    );

                    $sharePin.trigger('focus');
                } finally {
                    setRequestInProgress(false);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Submit OTP
            |--------------------------------------------------------------------------
            */

            // async function submitOtp() {
            //     const otp = String($autoOtp.val() || '');

            //     if (!/^\d{4,10}$/.test(otp)) {
            //         showAutoError(
            //             'Enter a valid OTP containing 4 to 10 digits.'
            //         );

            //         $autoOtp.trigger('focus');

            //         return;
            //     }

            //     if (!autoTransactionId) {
            //         showAutoError(
            //             'The transaction reference is missing. Return to the previous step and submit your PIN again.'
            //         );

            //         return;
            //     }

            //     if (requestInProgress) {
            //         return;
            //     }

            //     setRequestInProgress(true);
            //     clearAutoError();

            //     setActionButton(
            //         'Verifying OTP...',
            //         true,
            //         'bx-loader-alt bx-spin'
            //     );

            //     try {
            //         const response = await postJson(
            //             endpoints.complete,
            //             {
            //                 transaction_id: autoTransactionId,
            //                 otp: otp
            //             }
            //         );

            //         const data = getResponseData(response);

            //         const redirectUrl = data.redirect_url
            //             || data.redirect
            //             || response.redirect_url
            //             || response.redirect;

            //         if (redirectUrl) {
            //             window.location.assign(redirectUrl);

            //             return;
            //         }

            //         window.location.reload();
            //     } catch (error) {
            //         autoStage = AUTO_STAGE_OTP;

            //         showAutoError(
            //             error.message
            //             || 'The OTP could not be verified. Please check the code and try again.'
            //         );

            //         setActionButton(
            //             'Verify OTP and share airtime',
            //             false,
            //             'bx-bolt-circle'
            //         );

            //         $autoOtp.trigger('focus');
            //     } finally {
            //         setRequestInProgress(false);
            //     }
            // }
            async function submitOtp() {
                const otp = String($autoOtp.val() || '');

                if (!/^\d{4,10}$/.test(otp)) {
                    showAutoError('Enter a valid OTP.');
                    return;
                }

                if (requestInProgress) {
                    return;
                }

                setRequestInProgress(true);
                clearAutoError();

                setActionButton('Verifying OTP...', true, 'bx-loader-alt bx-spin');

                try {
                    const response = await postJson(endpoints.complete, {
                        transaction_id: autoTransactionId,
                        otp: otp
                    });

                    if (response.transaction_status === 'successful') {
                        window.location.href = response.redirect
                            || '{{ route('customer.airtime2cash.transaction.history') }}';

                        return;
                    }

                    if (response.transaction_status === 'pending') {
                        resetPendingFlow(
                            response.message
                            || 'Your transaction is pending. Please do not retry.'
                        );

                        return;
                    }

                    throw new Error(
                        response.message
                        || 'The airtime conversion could not be completed.'
                    );
                } catch (error) {
                    showAutoError(error.message);

                    setActionButton(
                        'Verify OTP and share airtime',
                        false,
                        'bx-bolt-circle'
                    );
                } finally {
                    setRequestInProgress(false);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Resend OTP
            |--------------------------------------------------------------------------
            */

            async function resendOtp() {
                if (!autoTransactionId || requestInProgress) {
                    return;
                }

                setRequestInProgress(true);
                clearAutoError();

                $resendOtpButton
                    .prop('disabled', true)
                    .html(
                        '<i class="bx bx-loader-alt bx-spin me-1"></i>' +
                        'Sending...'
                    );

                try {
                    await postJson(
                        endpoints.resend,
                        {
                            transaction_id: autoTransactionId
                        }
                    );

                    $resendOtpButton.html(
                        '<i class="bx bx-check me-1"></i>OTP sent'
                    );

                    window.setTimeout(function () {
                        $resendOtpButton
                            .prop('disabled', false)
                            .html(
                                '<i class="bx bx-refresh me-1"></i>' +
                                'Resend OTP'
                            );
                    }, 2500);
                } catch (error) {
                    showAutoError(
                        error.message
                        || 'The OTP could not be resent. Please try again.'
                    );

                    $resendOtpButton
                        .prop('disabled', false)
                        .html(
                            '<i class="bx bx-refresh me-1"></i>' +
                            'Resend OTP'
                        );
                } finally {
                    setRequestInProgress(false);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Main form submission
            |--------------------------------------------------------------------------
            */

            $form.on('submit', function (event) {
                event.preventDefault();

                if (requestInProgress) {
                    return;
                }

                /*
                 * Manual transfer uses the regular browser form submission.
                 */
                if (!isAutoTransfer()) {
                    if (!validateConversionDetails()) {
                        return;
                    }

                    setRequestInProgress(true);

                    setActionButton(
                        'Processing...',
                        true,
                        'bx-loader-alt bx-spin'
                    );

                    form.submit();

                    return;
                }

                /*
                 * Auto-transfer stage 1:
                 * validate details and reveal the PIN screen.
                 */
                if (autoStage === AUTO_STAGE_DETAILS) {
                    if (validateConversionDetails()) {
                        openPinStage();
                    }

                    return;
                }

                /*
                 * Auto-transfer stage 2:
                 * submit share PIN.
                 *
                 * OTP stage is opened only after a successful response.
                 */
                if (autoStage === AUTO_STAGE_PIN) {
                    submitSharePin();

                    return;
                }

                /*
                 * Auto-transfer stage 3:
                 * submit OTP.
                 */
                if (autoStage === AUTO_STAGE_OTP) {
                    submitOtp();
                }
            });


            $('input[name="transfer_mode"]').on('change', function () {
                const autoMode = this.value === 'auto_share';

                resetAutoFlow();
                refreshAvailableNetworks();

                $product.val('');
                resetSelectedProductDetails();

                $paymentMethod
                    .find('option[value="Transfer to Bank Account"]')
                    .prop('disabled', autoMode);

                $('#instruction-context').text(
                    autoMode
                        ? 'Follow these steps to complete an automatic airtime transfer.'
                        : 'Instructions depend on the selected network.'
                );

                showInstruction(
                    autoMode
                        ? defaultAutoInstruction
                        : null
                );

                setActionButton(
                    autoMode
                        ? 'Continue to secure transfer'
                        : 'Submit conversion',
                    false,
                    autoMode
                        ? 'bx-bolt-circle'
                        : 'bx-transfer'
                );
            });

            function isAutoTransfer() { return $('input[name="transfer_mode"]:checked').val() === 'auto_share'; }

            function refreshAvailableNetworks() {
                const statusKey = isAutoTransfer() ? 'data-auto-share-status' : 'data-manual-status';

                $product.html('<option value="">Select a network</option>');

                productOptions.each(function () {
                    const $option = $(this);

                    if ($option.attr(statusKey) === 'active') {
                        $product.append($option.clone());
                    }
                });

                $('.a2c-network-option').each(function () {
                    $(this).toggleClass('d-none', $(this).attr(statusKey) !== 'active');
                });

                $product.val('');
                $('.a2c-network-option').removeClass('is-active');
            }

            $(document).on('click', '.a2c-network-option:not(.d-none)', function () {
                const productId = String($(this).data('product-id'));
                const exists = $product.find(`option[value="${productId}"]`).length > 0;

                if (!exists) {
                    showAutoError('This network is unavailable for the selected transfer method.');
                    return;
                }

                $('.a2c-network-option').removeClass('is-active');
                $(this).addClass('is-active');
                $product.val(productId).trigger('change');
            });

            $product.on('change', updateSelectedProduct);

            $paymentMethod.on('change', function () {
                const useBank = this.value
                    === 'Transfer to Bank Account';

                $bankDetailsDiv.toggle(useBank);

                $bank.prop('required', useBank);
                $accountNumber.prop('required', useBank);
                $accountName.prop('required', useBank);

                if (!useBank) {
                    resetBankDetails();
                }
            });

            $amount.on('input', recalculatePayout);

            $sharePin.on('input', function () {
                this.value = this.value
                    .replace(/\D/g, '')
                    .slice(0, 8);

                if (autoStage === AUTO_STAGE_PIN) {
                    const valid = /^\d{4,8}$/.test(this.value);

                    setActionButton(
                        'Submit PIN and send OTP',
                        !valid || requestInProgress,
                        'bx-lock-open-alt'
                    );
                }

                clearAutoError();
            });

            $autoOtp.on('input', function () {
                this.value = this.value
                    .replace(/\D/g, '')
                    .slice(0, 10);

                if (autoStage === AUTO_STAGE_OTP) {
                    const valid = /^\d{4,10}$/.test(this.value);

                    setActionButton(
                        'Verify OTP and share airtime',
                        !valid || requestInProgress,
                        'bx-bolt-circle'
                    );
                }

                clearAutoError();
            });

            /*
            |--------------------------------------------------------------------------
            | Agreement
            |--------------------------------------------------------------------------
            */

            $agreement.on('change', function () {
                if (this.checked) {
                    $agreementPanel.removeClass('is-required');
                }
            });

            /*
            |--------------------------------------------------------------------------
            | Return to details
            |--------------------------------------------------------------------------
            */

            $('#edit-auto-details').on('click', function () {
                if (requestInProgress) {
                    return;
                }

                resetAutoFlow();

                setActionButton(
                    'Continue to secure transfer',
                    false,
                    'bx-bolt-circle'
                );

                $conversionDetailsPanel[0]?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Resend OTP
            |--------------------------------------------------------------------------
            */

            $resendOtpButton.on('click', resendOtp);

            /*
            |--------------------------------------------------------------------------
            | Initialize
            |--------------------------------------------------------------------------
            */

            $('input[name="transfer_mode"]:checked')
                .trigger('change');
        });
    </script>
@endsection
