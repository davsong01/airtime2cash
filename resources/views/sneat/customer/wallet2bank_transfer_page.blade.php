@php $verifiable = verifiableUniqueElements(); @endphp
@php $defaultTransferMode = old('transfer_mode', 'auto_share'); @endphp
@php $showProviderStatus = (bool) (getSettings()->show_provider_status_on_customer_pages ?? true); @endphp
@extends('sneat.layouts.app')
@section('title', $category->seo_title ?? 'Wallet to Bank Transfer')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .transfer-preview-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 253, 255, .98), rgba(238, 244, 255, .98));
            box-shadow: 0 12px 28px rgba(37, 99, 235, .08);
            color: var(--bs-body-color);
        }

        .transfer-preview-card::before {
            position: absolute;
            top: 0;
            right: 0;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(59, 130, 246, .12), transparent 70%);
            content: "";
            pointer-events: none;
        }

        .transfer-preview-title {
            color: var(--bs-heading-color);
            font-size: 1rem;
            font-weight: 700;
        }

        .transfer-preview-muted {
            color: var(--bs-secondary-color);
            font-size: .85rem;
        }

        .transfer-preview-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .transfer-preview-section {
            margin-top: .85rem;
            padding: .85rem;
            border-radius: .85rem;
        }

        .transfer-preview-section--base {
            background: rgba(var(--bs-primary-rgb), .08);
        }

        .transfer-preview-section--band {
            background: rgba(16, 185, 129, .08);
        }

        .transfer-preview-section--additional {
            background: rgba(245, 158, 11, .08);
        }

        .transfer-preview-section-label {
            margin-bottom: .55rem;
            color: var(--bs-secondary-color);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .transfer-preview-line {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .2rem 0;
            color: var(--bs-body-color);
        }

        .transfer-preview-line strong {
            color: var(--bs-heading-color);
        }

        .transfer-preview-total {
            margin-top: .95rem;
            padding-top: .9rem;
            border-top: 1px solid rgba(148, 163, 184, .22);
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
            min-height: 94px;
            padding: 1rem;
            align-items: center;
            gap: .85rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .82);
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .transfer-mode-option:hover {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, .34);
            box-shadow: 0 .9rem 1.5rem rgba(37, 99, 235, .08);
        }

        .transfer-mode-input:checked + .transfer-mode-option {
            border-color: rgba(var(--bs-primary-rgb), .8);
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .1), rgba(14, 165, 233, .04));
            box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .08);
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
            color: var(--bs-secondary-color);
            line-height: 1.45;
        }

        .manual-resolution-note {
            padding: .9rem 1rem;
            border: 1px solid rgba(245, 158, 11, .28);
            border-radius: .9rem;
            background: rgba(255, 251, 235, .95);
            color: #92400e;
        }

        .provider-health-strip {
            display: flex;
            margin-bottom: 1rem;
            padding: .9rem 1rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid rgba(67,89,113,.12);
            border-radius: 1rem;
            background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(242,247,251,.94));
            box-shadow: 0 .6rem 1.4rem rgba(67,89,113,.08);
        }

        .provider-health-strip strong,
        .provider-health-strip small {
            display: block;
        }

        .provider-health-kicker {
            display: inline-flex;
            margin-bottom: .2rem;
            color: var(--bs-secondary-color);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .provider-health-strip small {
            color: var(--bs-secondary-color);
        }

        .provider-health-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem .72rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .provider-health-badge.unstable { color: #991b1b; background: rgba(239,68,68,.14); }
        .provider-health-badge.degraded { color: #111827; background: rgba(17,24,39,.12); }
        .provider-health-badge.stable { color: #9a6700; background: rgba(245,158,11,.15); }
        .provider-health-badge.healthy { color: #166534; background: rgba(34,197,94,.14); }

        [data-bs-theme="dark"] .provider-health-strip {
            border-color: rgba(67,89,113,.18);
            background: rgba(43,44,64,.94);
        }

        [data-bs-theme="dark"] .provider-health-badge.degraded {
            color: #e5e7eb;
            background: rgba(75,85,99,.4);
        }

        [data-bs-theme="dark"] .manual-resolution-note {
            color: #fcd34d;
            background: rgba(120, 53, 15, .34);
        }

        @media (max-width: 767.98px) {
            .transfer-mode-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Transfer',
        'title' => 'Wallet to Bank Transfer',
        'subtitle' => 'Move money from your wallet to a bank account.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-building-house fs-4"></i></span>
                    <div><h5 class="mb-1">Transfer details</h5><small class="text-muted">Confirm the destination account before proceeding.</small></div>
                </div>
                <div class="card-body">
                @if($showProviderStatus && $activeProvider && $activeProvider->availability_status_class && $activeProvider->availability_checked_at)
                    <div class="provider-health-strip">
                        <div>
                            <span class="provider-health-kicker">Auto Transfer Status</span>
                            <small>Checked {{ $activeProvider->availability_checked_at->diffForHumans() }}</small>
                            @if($activeProvider->availability_status_class === 'unstable')
                                <small class="text-danger d-block mt-1">Auto transfer looks unstable right now, so manual processing may be the safer option.</small>
                            @endif
                        </div>
                        <span class="provider-health-badge {{ $activeProvider->availability_status_class }}">
                            <i class="bx bx-pulse"></i>
                            {{ $activeProvider->availability_status_label }}
                        </span>
                    </div>
                @endif
                    @php
                        $providerMin = 60;
                        $walletBal = walletBalance(auth()->user());
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

                    <form action="{{ route('initialize.wallet2banktransaction', $product->id) }}" method="POST" onsubmit="return confirm('I have entered correct details');" class="customer-modern-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label d-block mb-2">Transfer method</label>
                                <div class="transfer-mode-grid">
                                    <div>
                                        <input class="transfer-mode-input" type="radio" name="transfer_mode" id="transfer-mode-auto" value="auto_share" @checked($defaultTransferMode === 'auto_share')>
                                        <label class="transfer-mode-option" for="transfer-mode-auto">
                                            <span class="transfer-mode-badge"><i class="bx bx-bolt-circle"></i></span>
                                            <span>
                                                <strong>Auto Transfer</strong>
                                                <small>Use the active provider for a faster settlement.</small>
                                            </span>
                                        </label>
                                    </div>
                                    <div>
                                        <input class="transfer-mode-input" type="radio" name="transfer_mode" id="transfer-mode-manual" value="manual" @checked($defaultTransferMode === 'manual')>
                                        <label class="transfer-mode-option" for="transfer-mode-manual">
                                            <span class="transfer-mode-badge"><i class="bx bx-hand"></i></span>
                                            <span>
                                                <strong>Manual Transfer</strong>
                                                <small>Queued for admin review and sent to WhatsApp for manual processing.</small>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="manual-resolution-note mt-3" id="manual-resolution-note" style="display:none">
                                    Manual transfers depend on an admin being online and can take longer when traffic is high.
                                </div>
                            </div>
                            <div class="col-12">
                                @if(!$canWithdraw)
                                    <div class="alert alert-warning">
                                        @if(!$pricingEnabled)
                                            Wallet to bank transfer pricing is not configured yet.
                                        @elseif(!$pricingAvailable)
                                            Wallet to bank transfer pricing is not configured for the active provider.
                                        @else
                                            You do not have sufficient balance to use this service.
                                            Minimum required balance is <strong>{{ getSettings()['currency'] }}{{ number_format($minimumRequiredBalance, 2) }}</strong>.
                                        @endif
                                    </div>
                                @else
                                    <label for="amount" class="form-label">Amount to withdraw from wallet</label>
                                    <input class="form-control" id="amount" name="amount" type="number" placeholder="Enter amount to transfer" required min="{{ $validTransferMin }}" max="{{ $maxAmount }}" data-wallet-balance="{{ $walletBal }}" data-min-transfer="{{ $providerMin }}" data-pricing-bands='@json($pricingBands)' data-global-extra-charges='@json($pricingProvider?->extra_charges ?? [])' data-valid-range-text='@json($pricingAmountRange["range_text"] ?? null)'>
                                    @if(!empty($pricingAmountRange['range_text']))
                                        <small class="d-block mt-1 text-muted">Valid transfer amount range: <strong>{{ $pricingAmountRange['range_text'] }}</strong></small>
                                    @endif
                                    <div class="transfer-preview-card mt-3 mb-0 p-3" id="transfer-preview">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="transfer-preview-title">Fee Preview</div>
                                                <div class="transfer-preview-muted">Wallet to bank transfer summary</div>
                                            </div>
                                            <span class="badge bg-label-primary">Live</span>
                                        </div>
                                        <div class="transfer-preview-section transfer-preview-section--base">
                                            <div class="transfer-preview-section-label">Transfer Amount</div>
                                            <div class="transfer-preview-row">
                                                <span>Requested amount</span>
                                                <strong id="preview-amount">{{ getSettings()['currency'] }}0.00</strong>
                                            </div>
                                        </div>
                                        <div class="transfer-preview-section transfer-preview-section--base">
                                            <div class="transfer-preview-section-label">Base Transfer Charge</div>
                                            <div class="transfer-preview-row">
                                                <span>Base fee only</span>
                                                <strong id="preview-fee">{{ getSettings()['currency'] }}0.00</strong>
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
                                                <strong id="preview-total">{{ getSettings()['currency'] }}0.00</strong>
                                            </div>
                                            <small class="d-none mt-2 d-block text-danger" id="preview-shortfall"></small>
                                        </div>
                                        <small class="d-block mt-2 text-danger d-none" id="preview-error">Valid transfer amount range: {{ $pricingAmountRange['range_text'] ?? 'not available' }}</small>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label for="bank" class="form-label">Bank</label>
                                <select class="form-select modern-select2" name="bank" id="bank" data-placeholder="Search banks" required>
                                    <option value="">Select</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="account_number" class="form-label">Account number</label>
                                <input class="form-control" id="account_number" name="account_number" type="text" maxlength="10" inputmode="numeric" required>
                            </div>
                            <div class="col-md-4">
                                <label for="account_name" class="form-label">Account name</label>
                                <input class="form-control" id="account_name" name="account_name" type="text" required>
                            </div>
                            <div class="col-12" id="verify-bank-section">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                                    <small class="text-danger">Please ensure the bank details are correct before you proceed.</small>
                                    <button type="button" class="btn btn-outline-info btn-sm" id="verify-bank-details-btn">Verify Bank Details</button>
                                </div>
                                <div class="mt-3 d-none" id="bank-verify-result"></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary customer-form-submit" id="transfer-submit" type="submit" @disabled(!$canWithdraw)><i class="bx bx-right-arrow-alt me-1"></i> Proceed</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('.modern-select2').select2({
        width: '100%',
        placeholder: function () { return $(this).data('placeholder'); }
    });

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
    const verifyBankSection = document.getElementById('verify-bank-section');
    const manualResolutionNote = document.getElementById('manual-resolution-note');
    const transferModes = document.querySelectorAll('input[name="transfer_mode"]');
    const updateTransferModeUi = () => {
        const selectedMode = document.querySelector('input[name="transfer_mode"]:checked')?.value || 'auto_share';
        if (manualResolutionNote) {
            manualResolutionNote.style.display = selectedMode === 'manual' ? 'block' : 'none';
        }
        if (verifyBankSection) {
            verifyBankSection.style.display = selectedMode === 'manual' ? 'none' : 'block';
        }
        if (submitButton) {
            submitButton.innerHTML = selectedMode === 'manual'
                ? '<i class="bx bx-right-arrow-alt me-1"></i> Proceed to WhatsApp'
                : '<i class="bx bx-right-arrow-alt me-1"></i> Proceed';
        }
    };
    if (!amountInput || !previewAmount || !previewFee || !previewTotal || !submitButton) return;
    const walletBalance = parseFloat(amountInput.dataset.walletBalance) || 0;
    const minimumTransfer = parseFloat(amountInput.dataset.minTransfer) || 0;
    const pricingBands = JSON.parse(amountInput.dataset.pricingBands || '[]');
    const globalExtraCharges = JSON.parse(amountInput.dataset.globalExtraCharges || '[]');
    const validRangeText = amountInput.dataset.validRangeText ? JSON.parse(amountInput.dataset.validRangeText) : null;
    const currency = `{!! getSettings()['currency'] !!}`;

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

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderVerificationCard = (payload, title) => {
        const response = payload?.raw_response ?? payload?.data ?? payload ?? {};
        const status = String(payload?.status ?? response?.status ?? 'success').toLowerCase();
        const refined = payload?.data?.refined_data ?? response?.refined_data ?? {};
        const bankName = refined['Bank Name'] ?? response?.data?.bank_name ?? response?.bank_name ?? '';
        const accountName = refined['Account Name'] ?? response?.data?.account_name ?? response?.account_name ?? '';
        const accountNumber = refined['Account Number'] ?? response?.data?.account_number ?? response?.account_number ?? '';

        return `
            <div class="card border-0 shadow-sm mb-0" style="background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold text-dark">${escapeHtml(title || 'Bank verification')}</div>

                    </div>
                    ${bankName || accountName || accountNumber ? `
                        <div class="d-flex flex-column gap-1 text-dark">
                            ${bankName ? `<div><span class="fw-bold">${escapeHtml(bankName)}</span></div>` : ''}
                            ${accountName ? `<div>${escapeHtml(accountName)}</div>` : ''}
                            ${accountNumber ? `<div>${escapeHtml(accountNumber)}</div>` : ''}
                        </div>
                    ` : `
                        <div class="text-muted">Unavailable at the moment.</div>
                    `}
                </div>
            </div>
        `;
    };

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
            ? bandRows.map((charge) => `<div class="transfer-preview-line"><span>${charge.charge_name}</span><strong>${formatMoney(charge.value)}</strong></div>`).join('')
            : '';

        previewAdditionalCharges.innerHTML = additionalRows.length
            ? additionalRows.map((charge) => `<div class="transfer-preview-line"><span>${charge.charge_name}</span><strong>${formatMoney(charge.value)}</strong></div>`).join('')
            : '';
    };

    amountInput.addEventListener('input', function () {
        const amount = parseFloat(this.value) || 0;
        const band = matchesBand(amount);
        const providerFee = band ? (parseFloat(band.provider_fee) || 0) : 0;
        const extraCharge = band ? (parseFloat(band.extra_charge) || 0) : 0;
        const baseTransferCharge = band ? (providerFee + extraCharge) : 0;
        const bandExtraCharges = Array.isArray(band?.extra_charges) ? band.extra_charges : [];
        const additionalCharges = [...globalExtraCharges, ...bandExtraCharges];
        const globalFees = globalExtraCharges.reduce((sum, charge) => sum + (parseFloat(charge.value) || 0), 0);
        const bandFees = bandExtraCharges.reduce((sum, charge) => sum + (parseFloat(charge.value) || 0), 0);
        const fee = band ? (baseTransferCharge + globalFees + bandFees) : 0;
        const total = band ? (amount + fee) : 0;

        previewAmount.textContent = formatMoney(amount);
        previewFee.textContent = band ? formatMoney(baseTransferCharge) : 'N/A';
        previewTotal.textContent = band ? formatMoney(total) : 'N/A';
        renderBreakdown(band, bandExtraCharges, globalExtraCharges);

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

        const previewCard = document.getElementById('transfer-preview');
        if (previewCard) {
            previewCard.style.opacity = (amount > 0 && !isValid) ? '0.92' : '1';
        }

        submitButton.disabled = !isValid;
    });

    transferModes.forEach((radio) => {
        radio.addEventListener('change', updateTransferModeUi);
    });
    updateTransferModeUi();

    if (verifyBankButton && verifyBankResult) {
        verifyBankButton.addEventListener('click', async function () {
            const bank = document.getElementById('bank');
            const accountNumber = document.getElementById('account_number');
            const accountName = document.getElementById('account_name');
            const csrfToken = document.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!bank || !accountNumber || !accountName) {
                return;
            }

            verifyBankButton.disabled = true;
            verifyBankButton.textContent = 'Verifying...';
            verifyBankResult.classList.remove('d-none');
            verifyBankResult.innerHTML = renderVerificationCard({
                status: 'pending',
                message: 'Verifying bank details...'
            }, 'Bank verification');

            try {
                const response = await fetch('{{ route('customer.verify.bank.details') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
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
                    verifyBankResult.innerHTML = renderVerificationCard({
                        status: 'failed',
                    }, 'Bank details');
                    return;
                }

                verifyBankResult.innerHTML = renderVerificationCard(payload, 'Bank details verified successfully');
            } catch (error) {
                verifyBankResult.innerHTML = renderVerificationCard({
                    status: 'failed',
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
@endsection
