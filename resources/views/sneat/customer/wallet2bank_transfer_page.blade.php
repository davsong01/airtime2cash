@php $verifiable = verifiableUniqueElements(); @endphp
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
                    @php
                        $providerMin = 60;
                        $walletBal = walletBalance(auth()->user());
                        $pricingBands = $pricingBands ?? [];
                        $pricingEnabled = (bool) ($pricingEnabled ?? false);
                        $pricingAvailable = (bool) ($pricingAvailable ?? false);
                        $pricingConfigured = $pricingEnabled && $pricingAvailable;
                        $minimumCharge = $pricingConfigured ? getBankTransferChargeDetails($providerMin, $pricingProvider?->id) : ['transfer_fee' => 0];
                        $minimumRequiredBalance = $providerMin + (float) ($minimumCharge['transfer_fee'] ?? 0);
                        $maxAmount = max(0, $walletBal);
                        $canWithdraw = $pricingConfigured && $walletBal >= $minimumRequiredBalance;
                    @endphp

                    <form action="{{ route('initialize.wallet2banktransaction', $product->id) }}" method="POST" onsubmit="return confirm('I have entered correct details');" class="customer-modern-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                @if(!$canWithdraw)
                                    <div class="alert alert-warning">
                                        @if(!$pricingEnabled)
                                            Wallet to bank transfer pricing has been turned off by admin.
                                        @elseif(!$pricingAvailable)
                                            Wallet to bank transfer pricing is not configured for the active provider.
                                        @else
                                            You do not have sufficient balance to use this service.
                                            Minimum required balance is <strong>{{ getSettings()['currency'] }}{{ number_format($minimumRequiredBalance, 2) }}</strong>.
                                        @endif
                                    </div>
                                @else
                                    <label for="amount" class="form-label">Amount to withdraw from wallet</label>
                                    <input class="form-control" id="amount" name="amount" type="number" placeholder="Enter amount to transfer" required min="{{ $providerMin }}" max="{{ $maxAmount }}" data-wallet-balance="{{ $walletBal }}" data-min-transfer="{{ $providerMin }}" data-pricing-bands='@json($pricingBands)' data-global-extra-charges='@json($pricingProvider?->extra_charges ?? [])'>
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
                                        <small class="d-block mt-2 text-danger d-none" id="preview-error">The amount must fall within a configured pricing band.</small>
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
    if (!amountInput || !previewAmount || !previewFee || !previewTotal || !submitButton) return;
    const walletBalance = parseFloat(amountInput.dataset.walletBalance) || 0;
    const minimumTransfer = parseFloat(amountInput.dataset.minTransfer) || 0;
    const pricingBands = JSON.parse(amountInput.dataset.pricingBands || '[]');
    const globalExtraCharges = JSON.parse(amountInput.dataset.globalExtraCharges || '[]');
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

    submitButton.disabled = true;
    amountInput.dispatchEvent(new Event('input'));
});
</script>
@endsection
