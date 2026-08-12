@extends('sneat.layouts.app')
@section('title', 'Fund Wallet')

@section('page-css')
<style>
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

    .wallet-balance-card {
        border: 0;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #7c3aed 100%);
        color: #fff;
        box-shadow: 0 18px 40px -18px rgba(15, 23, 42, 0.45);
        overflow: hidden;
    }

    .wallet-balance-card__label {
        font-size: 0.8rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .wallet-balance-card__amount {
        font-size: clamp(1.9rem, 4vw, 2.8rem);
        font-weight: 800;
        line-height: 1;
    }

    .wallet-balance-card__note {
        color: rgba(255, 255, 255, 0.8);
    }

    .wallet-balance-card__pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.78rem;
        font-weight: 600;
        backdrop-filter: blur(8px);
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
            'panel_note' => 'Fastest option for instant wallet credit.',
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
            'panel_note' => 'Best when you want to fund from your bank account.',
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
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Wallet',
        'title' => 'Fund Wallet',
        'subtitle' => $walletFundingSubtitle,
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4 mb-3">
        <div class="col-12 col-lg-12">
            <div class="card wallet-balance-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="wallet-balance-card__label mb-2">Current wallet balance</div>
                            <div class="wallet-balance-card__amount">{{ getSettings()->currency }}{{ number_format((float) walletBalance(auth()->user()), 2) }}</div>
                            <div class="wallet-balance-card__note mt-2">Use this balance for purchases, transfers, and wallet-based services.</div>
                        </div>
                        <div class="d-flex flex-column gap-2 align-items-start align-items-lg-end">
                            <span class="wallet-balance-card__pill"><i class="bx bx-wallet"></i> Live balance</span>
                            <span class="wallet-balance-card__pill"><i class="bx bx-shield-quarter"></i> KYC verified actions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            @if(getFinalKycStatus(auth()->user()->customer->id) == 'unverified')
                <div class="alert alert-warning">
                    You need to complete KYC before you can fund your wallet.
                    <a href="{{ route('update.kyc.details') }}" class="alert-link">Update KYC details</a>
                </div>
            @elseif(empty($fundingModes))
                <div class="alert alert-danger">
                    No wallet funding method is currently active.
                </div>
            @else
                <div class="card customer-form-card">
                    <div class="card-body">
                        @if($showFundingModeSwitch)
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <label class="form-label mb-0">Funding method</label>
                                    <span class="badge bg-light text-dark" id="funding_mode_badge">{{ $fundingModeLabel }}</span>
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
                                <div class="text-muted small mt-2">Tap a method to preview its message and details.</div>
                            </div>
                        @endif

                        <div class="alert alert-info mb-4" id="funding_mode_message">
                            {{ $defaultFundingModeData['charge_text'] ?? '' }}
                        </div>

                        <div class="funding-mode-panels">
                            @if($allowCardFunding)
                                <div class="funding-mode-panel" data-funding-mode="card" @if($showFundingModeSwitch && $defaultFundingMode !== 'card') style="display:none;" @endif>
                                    <div class="row g-4 align-items-center">
                                        <div class="col-lg-7">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                                <div>
                                                    <h5 class="mb-1">{{ $fundingModes['card']['title'] }}</h5>
                                                    <p class="text-muted mb-0">{{ $fundingModes['card']['description'] }}</p>
                                                </div>
                                                <span class="badge bg-success-subtle text-success">{{ $fundingModes['card']['panel_note'] }}</span>
                                            </div>
                                            <div class="alert alert-info">
                                                {{ $fundingModes['card']['charge_text'] }}
                                            </div>
                                            <form action="{{ route('process-customer-load-wallet') }}" method="POST" id="wallet_load" class="customer-modern-form">
                                                @csrf
                                                <input type="hidden" name="funding_mode" value="card">
                                                <div class="mb-3">
                                                    <label for="amount" class="form-label">Amount</label>
                                                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="{{ old('amount') }}" required>
                                                </div>
                                                <button class="btn btn-primary customer-form-submit" type="button" onclick="loadWallet()">
                                                    <i class="bx bx-credit-card me-1"></i> Pay now
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($allowReservedAccountFunding)
                                <div class="funding-mode-panel" data-funding-mode="bank" @if($showFundingModeSwitch && $defaultFundingMode !== 'bank') style="display:none;" @endif>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                        <div>
                                            <h5 class="mb-1">{{ $fundingModes['bank']['title'] }}</h5>
                                            <p class="text-muted mb-0">{{ $fundingModes['bank']['description'] }}</p>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary">{{ $fundingModes['bank']['panel_note'] }}</span>
                                    </div>

                                    @if(auth()->user()->customer->reserved_accounts->count() > 0)
                                        <div class="alert alert-success" id="bank_funding_message">
                                            {{ $fundingModes['bank']['charge_text'] }}
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Account Name</th>
                                                        <th>Bank Name</th>
                                                        <th>Account Number</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach(auth()->user()->customer->reserved_accounts as $account)
                                                        @if($account->api_id == ($provider->id ?? $gateway->id))
                                                            <tr>
                                                                <td>{{ $account->account_name }}</td>
                                                                <td>{{ $account->bank_name }}</td>
                                                                <td>{{ $account->account_number }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-danger">
                                            No reserved account number found. Please contact support.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
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

        function loadWallet() {
            $.LoadingOverlay("show");
            document.forms["wallet_load"].submit();
        }
    </script>
@endsection
