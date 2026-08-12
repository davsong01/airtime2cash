@extends('layouts.app')
@section('title', 'Fund Wallet')

@section('page-css')
<link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/pages/dashboard-analytics.css') }}">
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }

    .funding-mode-switch {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.35rem;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
    }

    .funding-mode-switch__option {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 0;
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-weight: 600;
        font-size: 0.95rem;
        color: #475569;
        background: transparent;
        transition: all 0.2s ease;
    }

    .funding-mode-switch__option:hover {
        color: #0f172a;
        background: #e2e8f0;
    }

    .funding-mode-switch__option.is-active {
        color: #ffffff;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 10px 20px -12px rgba(79, 70, 229, 0.6);
    }
</style>
@endsection

@php
    $allowCardFunding = strtolower((string) (getSettings()->allow_fund_with_card ?? 'no')) === 'yes';
    $allowReservedAccountFunding = strtolower((string) (getSettings()->allow_fund_with_reserved_account ?? 'no')) === 'yes';
    $cardFundingCharge = (float) ($gateway->charge ?? 0);
    $cardFundingExtraCharge = (float) (getSettings()->card_funding_extra_charge ?? 0);
    $reservedAccountChargeType = strtolower((string) ($gateway->reserved_account_payment_charge_type ?? 'flat'));
    $reservedAccountChargeValue = (float) ($gateway->reserved_account_payment_charge ?? 0);
    $fundingModes = [];

    if ($allowCardFunding) {
        $cardChargeText = 'Card funding charge is ' . number_format($cardFundingCharge, 1) . '%';
        if ($cardFundingExtraCharge > 0) {
            $cardChargeText .= ' + ' . getSettings()->currency . number_format($cardFundingExtraCharge, 2);
        }

        $fundingModes['card'] = [
            'label' => 'Fund with Card',
            'title' => 'Card funding',
            'description' => 'Credit your wallet instantly and keep the balance ready for purchases.',
            'charge_text' => $cardChargeText . '.',
        ];
    }

    if ($allowReservedAccountFunding) {
        $reservedChargeText = 'Bank transfer funding is automatic after processing.';
        if ($reservedAccountChargeValue > 0) {
            $reservedChargeText .= ' Charge: ';
            $reservedChargeText .= $reservedAccountChargeType === 'percentage'
                ? number_format($reservedAccountChargeValue, 1) . '%'
                : getSettings()->currency . number_format($reservedAccountChargeValue, 2);
            $reservedChargeText .= '.';
        }

        $fundingModes['bank'] = [
            'label' => 'Fund with Bank Transfer',
            'title' => 'Bank transfer funding',
            'description' => 'Transfer into your dedicated reserved account and the wallet will be credited automatically.',
            'charge_text' => $reservedChargeText,
        ];
    }

    $defaultFundingMode = array_key_first($fundingModes);
    $defaultFundingModeData = ($defaultFundingMode !== null && isset($fundingModes[$defaultFundingMode]))
        ? $fundingModes[$defaultFundingMode]
        : [];
    $showFundingModeSwitch = count($fundingModes) > 1;
    $fundingModeLabel = $defaultFundingModeData['label'] ?? 'Funding method';
    $walletFundingSubtitle = count($fundingModes) > 1
        ? 'Choose a funding method below. The details update based on what you select.'
        : ($defaultFundingModeData['description'] ?? 'Choose a funding method that fits your account and KYC status.');
@endphp

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <section id="basic-input">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="content-body">
                                <section id="nav-filled">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="col-md-12">
                                                    <div class="card-header" style="padding:1.4rem 0.7rem">
                                                        <div>
                                                            <h4 class="card-title">Fund Wallet</h4>
                                                            <div class="text-muted">{{ $walletFundingSubtitle }}</div>
                                                        </div>
                                                        @include('layouts.walletanalytics')
                                                        @include('layouts.alerts')
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        @if(getFinalKycStatus(auth()->user()->customer->id) == 'unverified')
                                                            Hang on a second! You need to fill in your KYC information for verification before you can fund your wallet <br>
                                                            <a href="{{ route('update.kyc.details') }}" class="btn btn-info btn-sm">Update KYC details here</a>
                                                        @elseif(empty($fundingModes))
                                                            <div class="alert alert-danger">No wallet funding method is currently active.</div>
                                                        @else
                                                            @if($showFundingModeSwitch)
                                                                <div class="form-group mb-4">
                                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                                        <label class="mb-0">Funding method</label>
                                                                        <span class="badge badge-light" id="funding_mode_badge">{{ $fundingModeLabel }}</span>
                                                                    </div>
                                                                    <div class="funding-mode-switch" role="tablist" aria-label="Funding method">
                                                                        @foreach($fundingModes as $key => $mode)
                                                                            <button
                                                                                type="button"
                                                                                class="funding-mode-switch__option {{ $key === $defaultFundingMode ? 'is-active' : '' }}"
                                                                                data-funding-mode-option="{{ $key }}"
                                                                                aria-pressed="{{ $key === $defaultFundingMode ? 'true' : 'false' }}"
                                                                            >
                                                                                <i class="bx {{ $key === 'card' ? 'bx-credit-card' : 'bx-transfer-alt' }}"></i>
                                                                                {{ $mode['label'] }}
                                                                            </button>
                                                                        @endforeach
                                                                    </div>
                                                                    <small class="text-muted d-block mt-2">Tap a method to preview its message and details.</small>
                                                                </div>
                                                            @endif

                                                            <div class="alert alert-info mb-4" id="funding_mode_message">
                                                                {{ $defaultFundingModeData['charge_text'] ?? '' }}
                                                            </div>

                                                            <div class="funding-mode-panels">
                                                                @if($allowCardFunding)
                                                                    <div class="funding-mode-panel" data-funding-mode="card" @if($showFundingModeSwitch && $defaultFundingMode !== 'card') style="display:none;" @endif>
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <h5>{{ $fundingModes['card']['title'] }}</h5>
                                                                                <p class="text-muted">{{ $fundingModes['card']['description'] }}</p>
                                                                                <div class="alert alert-info">
                                                                                    {{ $fundingModes['card']['charge_text'] }}
                                                                                </div>
                                                                                <form action="{{ route('process-customer-load-wallet') }}" method="POST" id="wallet_load">
                                                                                    @csrf
                                                                                    <input type="hidden" name="funding_mode" value="card">
                                                                                    <div class="row">
                                                                                        <div class="col-md-12">
                                                                                            <fieldset class="form-group">
                                                                                                <label for="amount">Enter Amount</label>
                                                                                                <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="{{ old('amount') }}" required>
                                                                                            </fieldset>
                                                                                        </div>
                                                                                        <div class="col-md-12">
                                                                                            <a class="btn btn-primary" style="color:white" onclick="loadWallet()">Pay now</a>
                                                                                        </div>
                                                                                    </div>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if($allowReservedAccountFunding)
                                                                    <div class="funding-mode-panel" data-funding-mode="bank" @if($showFundingModeSwitch && $defaultFundingMode !== 'bank') style="display:none;" @endif>
                                                                        @if(auth()->user()->customer->reserved_accounts->count() > 0)
                                                                            <div class="alert alert-success">
                                                                                {{ $fundingModes['bank']['charge_text'] }}
                                                                            </div>
                                                                            <div>
                                                                                <h5>Wallet Funding Account Details</h5>
                                                                                <div class="table-responsive mt-2">
                                                                                    <table class="table table-striped">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th style="color:#495463;">Account Name</th>
                                                                                                <th style="color:#495463;">Bank Name</th>
                                                                                                <th style="color:#495463;">Account Number</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @foreach(auth()->user()->customer->reserved_accounts as $account)
                                                                                                @if($account->api_id == ($provider->id ?? $gateway->id))
                                                                                                    <tr>
                                                                                                        <td style="color:#173D52;">{{ $account->account_name }}</td>
                                                                                                        <td style="color:#173D52;">{{ $account->bank_name }}</td>
                                                                                                        <td style="color:#173D52;">{{ $account->account_number }}</td>
                                                                                                    </tr>
                                                                                                @endif
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <p>
                                                                                <span style="color:red"><b>SORRY!</b></span>
                                                                                No Account Number found, please contact us via on <a target="_blank" href="https://wa.me/{{ getSettings()->whatsapp_number }}?text="{{ urlencode('Hi, I could nor find a reserved account number after completing my KYC verification') }}"> Whatsapp on {{ getSettings()->whatsapp_number }} </a>to attend to this as soon as possible.
                                                                            </p>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            {!! getSettings()->google_ad_code !!}
                                        </div>
                                    </div>
                                </section>
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
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script type="text/javascript" src="https://sdk.monnify.com/plugin/monnify.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fundingModeButtons = document.querySelectorAll('[data-funding-mode-option]');
        const fundingModeMessage = document.getElementById('funding_mode_message');
        const fundingPanels = document.querySelectorAll('.funding-mode-panel');
        const fundingModeBadge = document.getElementById('funding_mode_badge');
        const panelData = @json($fundingModes);

        if (!fundingModeButtons.length || !fundingPanels.length) {
            return;
        }

        const switchFundingMode = (mode) => {
            fundingPanels.forEach((panel) => {
                panel.style.display = panel.dataset.fundingMode === mode ? '' : 'none';
            });

            if (fundingModeMessage && panelData[mode]) {
                fundingModeMessage.textContent = panelData[mode].charge_text || '';
            }

            if (fundingModeBadge && panelData[mode]) {
                fundingModeBadge.textContent = panelData[mode].label || '';
            }

            fundingModeButtons.forEach((button) => {
                const isActive = button.dataset.fundingModeOption === mode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        fundingModeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                switchFundingMode(button.dataset.fundingModeOption);
            });
        });

        switchFundingMode(@json($defaultFundingMode));
    });

    function loadWallet(){
        $.LoadingOverlay("show");
        document.forms["wallet_load"].submit();
    }
</script>
@endsection
