@extends('sneat.layouts.app')

@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .a2c-workspace { --a2c-green: #00a86b; --a2c-ink: #263446; position: relative; isolation: isolate; }
        .a2c-workspace::before { content: ''; position: absolute; z-index: -1; inset: -2rem -1.5rem auto; height: 360px; border-radius: 2.25rem; background: radial-gradient(circle at 8% 8%, rgba(0,168,107,.18), transparent 38%), radial-gradient(circle at 88% 2%, rgba(3,195,236,.16), transparent 34%); pointer-events: none; }
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
        [data-bs-theme="dark"] .a2c-card, [data-bs-theme="dark"] .a2c-mode-deck, [data-bs-theme="dark"] .conversion-mode-option, [data-bs-theme="dark"] .a2c-network-option, [data-bs-theme="dark"] .a2c-action-bar { background-color: rgba(43,44,64,.94); }
        [data-bs-theme="dark"] .a2c-rate-box { background: rgba(43,44,64,.72); }
        @media (max-width: 991.98px) { .a2c-instruction-card { position: static; } }
        @media (max-width: 767.98px) { .a2c-network-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .a2c-mode-heading { align-items: flex-start; flex-direction: column; } }
        @media (max-width: 575.98px) { .a2c-workspace::before { inset-inline: -.5rem; } .a2c-card { border-radius: .95rem; } .a2c-mode-deck, .a2c-form-body, .a2c-calculator { padding: 1rem; } .conversion-mode-option { min-height: 92px; padding: .8rem; } .a2c-network-option { min-height: 102px; } .a2c-stage-summary { grid-template-columns: 1fr; } .a2c-secure-flow { padding: 1.25rem !important; } .a2c-action-bar { align-items: stretch; flex-direction: column; } .a2c-action-bar .purchase-submit { width: 100%; min-width: 0; } }
    </style>

@endsection

@php
    $manualProducts = $category->products->where('manual_status', 'active')->count();
    $autoShareProducts = $category->products->where('auto_share_status', 'active')->count();
    $defaultTransferMode = old('transfer_mode', $manualProducts ? 'manual' : 'auto_share');
    $autoTransferInstruction = $category->products->firstWhere('auto_share_status', 'active')?->auto_share_instruction;
@endphp

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Conversion',
        'title' => 'Airtime to Cash',
        'subtitle' => 'Choose your network, enter the amount, and complete the conversion.',
    ])

    @include('sneat.layouts.alerts')

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
                <div class="row g-3 conversion-mode-options">
                    <div class="col-md-6">
                        <input class="btn-check" type="radio" name="transfer_mode" id="transfer-mode-manual" value="manual" autocomplete="off" @checked($defaultTransferMode === 'manual') @disabled(!$manualProducts)>
                        <label class="conversion-mode-option" for="transfer-mode-manual">
                            <span class="conversion-mode-icon bg-label-success"><i class="bx bx-hand"></i></span>
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
                                        <option value="{{ $item->id }}" data-manual_status="{{ $item->manual_status }}" data-auto_share_status="{{ $item->auto_share_status }}" data-manual_rate="{{ $item->manual_discounted_rate }}" data-auto_share_rate="{{ $item->auto_share_discounted_rate }}" data-min="{{ $item->min }}" data-max="{{ $item->max }}" data-image="{{ asset($item->image) }}" data-name="{{ $item->name }}" data-manual_instruction="{{ $item->instruction }}" data-auto_share_instruction="{{ $item->auto_share_instruction }}" data-description="{{ $item->description }}">{{ $item->display_name }}</option>
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
                        <span class="a2c-action-note"><i class="bx bx-lock-alt fs-5 text-success"></i>Your conversion details are transmitted securely.</span>
                        <button id="buy-button" class="purchase-submit btn btn-primary" type="submit" onclick="return submitForm()">
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
                                <label class="conversion-agreement" for="agreement" id="agreement-panel">
                                    <input type="checkbox" class="form-check-input conversion-agreement-check" id="agreement" name="agreement" value="1" required>
                                    <span class="conversion-agreement-icon"><i class="bx bx-check-shield"></i></span>
                                    <span class="conversion-agreement-copy"><strong>I have read and agree to these instructions</strong><small>Confirm before submitting this conversion.</small></span>
                                </label>
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
        function submitForm() {
            return window.handleAirtimeToCashSubmit ? window.handleAirtimeToCashSubmit() : false;
        }

        $(document).ready(function () {
            $('.modern-select2').each(function () {
                const select = $(this);
                select.select2({
                    width: '100%',
                    placeholder: select.data('placeholder'),
                    minimumResultsForSearch: 0
                });
            });

            const productSelect = $('#product');
            const allProductOptions = productSelect.find('option').clone();
            const defaultAutoInstruction = @json($autoTransferInstruction);
            const currency = @json(html_entity_decode(strip_tags(getSettings()['currency'])));
            const csrfToken = @json(csrf_token());
            const autoUrls = {
                initiate: @json(route('airtime2cash.auto.initiate')),
                complete: @json(route('airtime2cash.auto.complete')),
                resend: @json(route('airtime2cash.auto.resend-otp'))
            };
            let autoStage = 'details';
            let autoTransactionId = null;

            function isAutoTransfer() {
                return $('input[name="transfer_mode"]:checked').val() === 'auto_share';
            }

            function setActionButton(label, disabled, icon) {
                $('#buy-button').prop('disabled', disabled).html(`<i class="bx ${icon} me-1"></i><span>${label}</span>`);
            }

            function showFlowError(message) {
                $('#auto-flow-error').text(message).toggle(Boolean(message));
            }

            function firstError(data) {
                if (data.errors) {
                    const errors = Object.values(data.errors).flat();
                    if (errors.length) return errors[0];
                }
                return data.message || 'The request could not be completed. Please try again.';
            }

            async function postAuto(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload)
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(firstError(data));
                return data;
            }

            function autoPayload() {
                return {
                    product: $('#product').val(),
                    transfer_mode: 'auto_share',
                    amount: $('#amount').val(),
                    phone: $('#phone').val().replace(/\s+/g, ''),
                    email: $('#email').val(),
                    payment_method: 'Transfer to Wallet',
                    agreement: $('#agreement').prop('checked') ? 1 : 0
                };
            }

            function validateDetails() {
                if (isAutoTransfer()) {
                    $('#payment_method').val('Transfer to Wallet').trigger('change.select2');
                }

                // Ensure hidden select has a value before running validity check
                if (!productSelect.val()) {
                    showFlowError('Please select a network to continue.');
                    $('html, body').animate({ scrollTop: productSelect.offset().top - 100 }, 200);
                    return false;
                }

                const form = document.getElementById('initialize');
                if (!form.reportValidity()) return false;

                if (!$('#agreement').prop('checked')) {
                    $('#agreement-panel').addClass('is-required');
                    $('#agreement').trigger('focus');
                    return false;
                }
                return true;
            }

            function openPinStage() {
                const selected = productSelect.find(':selected');
                $('#auto-pin-phone').text($('#phone').val());
                $('#auto-summary-network').text(selected.data('name') || selected.text());
                $('#auto-summary-amount').text(currency + Number($('#amount').val()).toLocaleString());
                $('#auto-summary-payout').text(currency + Number($('#receive').val()).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#conversion-details-panel').hide();
                $('#auto-secure-flow, #auto-pin-stage').show();
                $('#auto-otp-stage').hide();
                $('#share-pin, #auto-otp').val('');
                showFlowError('');

                const pinVal = $('#share-pin').val();
                const isValidPin = /^\d{4,8}$/.test(pinVal);
                setActionButton('Submit PIN and enter OTP', !isValidPin, 'bx-lock-open-alt');
                document.getElementById('auto-secure-flow').scrollIntoView({ behavior: 'smooth', block: 'center' });
                autoStage = 'pin';
                $('#share-pin').trigger('focus');
            }

            function openOtpStage(data) {
                autoTransactionId = data.transaction_id || data.data?.transaction?.reference;
                autoStage = 'otp';
                $('#auto-pin-stage').hide();
                $('#auto-otp-stage').show();
                $('#auto-otp-phone').text(data.phone || $('#phone').val());

                const otpVal = $('#auto-otp').val();
                const isValidOtp = /^\d{4,10}$/.test(otpVal);
                setActionButton('Share Airtime', !isValidOtp, 'bx-bolt-circle');
                $('#auto-otp').trigger('focus');
            }

            window.handleAirtimeToCashSubmit = function () {
                if (!isAutoTransfer()) {
                    if (!validateDetails()) return false;
                    setActionButton('Processing...', true, 'bx-loader-alt bx-spin');
                    document.getElementById('initialize').submit();
                    return false;
                }

                if (autoStage === 'details') {
                    if (validateDetails()) openPinStage();
                    return false;
                }

                if (autoStage === 'pin') {
                    const pin = $('#share-pin').val();
                    if (!/^\d{4,8}$/.test(pin)) return false;
                    setActionButton('Sending OTP...', true, 'bx-loader-alt bx-spin');
                    showFlowError('');

                    postAuto(autoUrls.initiate, { ...autoPayload(), share_pin: pin })
                        .then(res => openOtpStage(res))
                        .catch(error => {
                            showFlowError(error.message);
                            setActionButton('Submit PIN and enter OTP', false, 'bx-lock-open-alt');
                        });
                    return false;
                }

                const otp = $('#auto-otp').val();
                if (!/^\d{4,10}$/.test(otp)) return false;
                setActionButton('Sharing airtime...', true, 'bx-loader-alt bx-spin');
                showFlowError('');

                postAuto(autoUrls.complete, { transaction_id: autoTransactionId, otp })
                    .then(data => {
                        window.location.href = data.redirect_url || data.redirect || window.location.href;
                    })
                    .catch(error => {
                        showFlowError(error.message);
                        setActionButton('Share Airtime', false, 'bx-bolt-circle');
                    });
                return false;
            };

            $('#share-pin').on('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 8);
                if (autoStage === 'pin') {
                    setActionButton('Submit PIN and enter OTP', !/^\d{4,8}$/.test(this.value), 'bx-lock-open-alt');
                }
            });

            $('#auto-otp').on('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
                if (autoStage === 'otp') {
                    setActionButton('Share Airtime', !/^\d{4,10}$/.test(this.value), 'bx-bolt-circle');
                }
            });

            $('#edit-auto-details').on('click', function () {
                autoStage = 'details';
                $('#auto-secure-flow').hide();
                $('#conversion-details-panel').show();
                setActionButton('Initiate Auto Transfer', false, 'bx-bolt-circle');
            });

            $('#resend-auto-otp').on('click', function () {
                if (!autoTransactionId) return;
                const button = $(this);
                button.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...');
                showFlowError('');
                postAuto(autoUrls.resend, { transaction_id: autoTransactionId })
                    .then(data => {
                        button.html('<i class="bx bx-check me-1"></i>OTP sent');
                        setTimeout(() => button.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Resend OTP'), 2500);
                    })
                    .catch(error => {
                        showFlowError(error.message);
                        button.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Resend OTP');
                    });
            });

            function showInstruction(instruction) {
                if (!instruction) {
                    $('#instruction-div').hide();
                    $('#instruction-empty').show();
                    return;
                }
                $('#instruction').html(instruction);
                $('#instruction-div').show();
                $('#instruction-empty').hide();
            }

            function refreshNetworks() {
                const transferMode = $('input[name="transfer_mode"]:checked').val();
                const statusKey = transferMode === 'auto_share' ? 'auto_share_status' : 'manual_status';

                productSelect.empty();
                productSelect.append('<option value="">Select a network</option>');

                allProductOptions.each(function () {
                    const option = $(this);
                    if (option.val() && option.data(statusKey) === 'active') {
                        productSelect.append(option.clone());
                    }
                });

                productSelect.val('').trigger('change');
            }

            $('input[name="transfer_mode"]').on('change', function () {
                const isAutoTransfer = this.value === 'auto_share';
                autoStage = 'details';
                autoTransactionId = null;
                $('#auto-secure-flow').hide();
                $('#conversion-details-panel').show();
                $('#payment_method option[value="Transfer to Bank Account"]').prop('disabled', isAutoTransfer);
                $('#instruction-context').text(isAutoTransfer
                    ? 'Follow these steps to complete an automatic airtime transfer.'
                    : 'Instructions depend on the selected network.');
                setActionButton(isAutoTransfer ? 'Initiate Auto Transfer' : 'Submit conversion', false, isAutoTransfer ? 'bx-bolt-circle' : 'bx-transfer');
                // refresh the native select options
                refreshNetworks();

                // show/hide the network tiles to match the selected mode
                const statusAttr = isAutoTransfer ? 'data-auto-share-status' : 'data-manual-status';
                $('.a2c-network-option').each(function () {
                    const status = $(this).attr(statusAttr);
                    if (status === 'active') {
                        $(this).removeClass('d-none');
                        // small entrance transition
                        $(this).css('opacity', 0).animate({ opacity: 1 }, 220);
                    } else {
                        $(this).addClass('d-none');
                    }
                });

                // reset selection state
                $('.a2c-network-option').removeClass('is-active');
                productSelect.val('').trigger('change');
            });

            $('#agreement').on('change', function () {
                $('#agreement-panel').removeClass('is-required');
            });

            $(document).on('click', '.a2c-network-option', function () {
                $('.a2c-network-option').removeClass('is-active');
                $(this).addClass('is-active');
                const productId = $(this).data('product-id');
                productSelect.val(productId).trigger('change');
            });

            productSelect.on('change', function () {
                const selected = $(this).find(':selected');
                const product = selected.val();
                const transferMode = $('input[name="transfer_mode"]:checked').val();

                if (product) {
                    $('.a2c-network-option').removeClass('is-active');
                    $(`.a2c-network-option[data-product-id="${product}"]`).addClass('is-active');
                }

                $('#agreement').prop('checked', false);
                $('#amount').val('');
                $('#receive').val('');
                $('#payment_method').val('').trigger('change');
                $('#receive-div, #payment-div, #bank-details-div').hide();

                if (!product) {
                    $('#rate').val('');
                    $('#rate-display').text('0');
                    $('#rate-text, #amount-div').hide();
                    showInstruction(transferMode === 'auto_share' ? defaultAutoInstruction : null);
                    return;
                }

                // read raw data-attribute (use attr to avoid data normalization issues)
                const rawRate = transferMode === 'auto_share'
                    ? (selected.attr('data-auto_share_rate') ?? selected.data('auto_share_rate'))
                    : (selected.attr('data-manual_rate') ?? selected.data('manual_rate'));
                const discountedRate = parseFloat(rawRate) || 0;
                const max = parseFloat(selected.data('max'));
                const min = parseFloat(selected.data('min'));
                const instruction = transferMode === 'auto_share'
                    ? (selected.attr('data-auto_share_instruction') || selected.data('auto_share_instruction') || defaultAutoInstruction)
                    : (selected.attr('data-manual_instruction') || selected.data('manual_instruction'));

                showInstruction(instruction);
                $('#rate').val(discountedRate);
                $('#rate-display').text(discountedRate);

                if (Number.isFinite(min)) {
                    $('#amount').attr('min', min);
                } else {
                    $('#amount').removeAttr('min');
                }

                if (Number.isFinite(max)) {
                    $('#amount').attr('max', max);
                } else {
                    $('#amount').removeAttr('max');
                }

                if (Number.isFinite(min) && Number.isFinite(max)) {
                    $('#airtime-range').text(`Allowed range: ${currency}${min.toLocaleString()} - ${currency}${max.toLocaleString()}`).show();
                } else {
                    $('#airtime-range').hide();
                }

                $('#rate-text, #amount-div').show();
            });

            $('input[name="transfer_mode"]:checked').trigger('change');

            $('#payment_method').on('change', function () {
                const useBank = this.value === 'Transfer to Bank Account';
                $('#bank-details-div').toggle(useBank);
                $('#bank, #account_number, #account_name').prop('required', useBank);

                if (!useBank) {
                    $('#bank').val('').trigger('change.select2');
                    $('#account_number, #account_name').val('');
                }
            });

            $('#amount').on('input', function () {
                const rate = parseFloat($('#rate').val());
                const amount = parseFloat(this.value);
                const min = parseFloat(this.min);
                const max = parseFloat(this.max);
                const isValidAmount = Number.isFinite(amount)
                    && Number.isFinite(rate)
                    && (!Number.isFinite(min) || amount >= min)
                    && (!Number.isFinite(max) || amount <= max);

                if (isValidAmount) {
                    const receive = amount - ((rate / 100) * amount);
                    $('#receive').val(receive.toFixed(2));
                    $('#receive-div, #payment-div').show();
                    if (isAutoTransfer()) {
                        $('#payment_method').val('Transfer to Wallet').trigger('change');
                    }
                } else {
                    $('#receive').val('');
                    $('#payment_method').val('').trigger('change');
                    $('#receive-div, #payment-div, #bank-details-div').hide();
                }
            });
        });
    </script>
@endsection
