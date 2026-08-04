@php $verifiable = verifiableUniqueElements(); @endphp
@extends('sneat.layouts.app')
@section('title', $category->seo_title ?? 'Wallet to Bank Transfer')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
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
                        $bankCharge = (float) env('BANK_TRANSFER_CHARGES');
                        $providerMin = 60;
                        $walletBal = walletBalance(auth()->user());
                        $minAmount = $bankCharge + $providerMin;
                        $maxAmount = max(0, $walletBal);
                        $canWithdraw = $walletBal >= $minAmount;
                    @endphp

                    <form action="{{ route('initialize.wallet2banktransaction', $product->id) }}" method="POST" onsubmit="return confirm('I have entered correct details');" class="customer-modern-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                @if(!$canWithdraw)
                                    <div class="alert alert-warning">
                                        You do not have sufficient balance to use this service.
                                        Minimum required balance is <strong>{{ getSettings()['currency'] }}{{ number_format($minAmount) }}</strong>.
                                    </div>
                                @else
                                    <label for="amount" class="form-label">Amount to withdraw from wallet</label>
                                    <input class="form-control" id="amount" name="amount" type="number" placeholder="Enter amount to transfer" required min="{{ $minAmount }}" max="{{ $maxAmount }}" data-bank-charge="{{ $bankCharge }}">
                                    <div class="form-text">
                                        Minimum: {{ getSettings()['currency'] }}{{ number_format($minAmount) }} | Maximum: {{ getSettings()['currency'] }}{{ number_format($maxAmount) }}
                                        <span id="recipient-amount" class="d-block text-success mt-1">Recipient will receive {{ getSettings()['currency'] }}0</span>
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
                            <button class="btn btn-primary customer-form-submit" type="submit" @disabled(!$canWithdraw)><i class="bx bx-right-arrow-alt me-1"></i> Proceed</button>
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
    const recipientEl = document.getElementById('recipient-amount');
    if (!amountInput || !recipientEl) return;
    const bankCharge = parseFloat(amountInput.dataset.bankCharge) || 0;
    const currency = `{!! getSettings()['currency'] !!}`;
    amountInput.addEventListener('input', function () {
        const amount = parseFloat(this.value) || 0;
        const receivable = Math.max(0, amount - bankCharge);
        recipientEl.textContent = `Recipient will receive ${currency}${receivable.toLocaleString()}`;
    });
});
</script>
@endsection
