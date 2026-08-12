@extends('sneat.layouts.app')
@section('title', 'Fund Wallet')

@php
    $allowCardFunding = strtolower((string) (getSettings()->allow_fund_with_card ?? 'no')) === 'yes';
    $allowReservedAccountFunding = strtolower((string) (getSettings()->allow_fund_with_reserved_account ?? 'no')) === 'yes';
    $cardFundingCharge = (float) ($gateway->charge ?? 0);
    $cardFundingExtraCharge = (float) (getSettings()->card_funding_extra_charge ?? 0);
    $walletFundingSubtitle = 'Choose a funding method that fits your account and KYC status.';
    $walletFundingSubtitle .= ' Card funding charge is ' . number_format($cardFundingCharge, 1) . '%';
    if ($cardFundingExtraCharge > 0) {
        $walletFundingSubtitle .= ' + ' . getSettings()->currency . number_format($cardFundingExtraCharge, 2);
    }
    $walletFundingSubtitle .= '.';
@endphp

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Wallet',
        'title' => 'Fund Wallet',
        'subtitle' => $walletFundingSubtitle,
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4">
        <div class="col-12">
            @if(getFinalKycStatus(auth()->user()->customer->id) == 'unverified')
                <div class="alert alert-warning">
                    You need to complete KYC before you can fund your wallet.
                    <a href="{{ route('update.kyc.details') }}" class="alert-link">Update KYC details</a>
                </div>
            @else
                <div class="card customer-form-card">
                    <div class="card-body">
                        <ul class="nav nav-pills mb-4" role="tablist">
                            @if($allowCardFunding)
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#fund-card" type="button">Fund with Card</button>
                                </li>
                            @endif
                            @if($allowReservedAccountFunding)
                                <li class="nav-item">
                                    <button class="nav-link {{ $allowCardFunding ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#fund-bank" type="button">Fund with Bank Transfer</button>
                                </li>
                            @endif
                        </ul>

                        <div class="tab-content">
                            @if($allowCardFunding)
                                <div class="tab-pane fade show active" id="fund-card">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-lg-7">
                                            <h5 class="mb-3">Card funding</h5>
                                            <p class="text-muted">Credit your wallet instantly and keep the balance ready for purchases.</p>
                                            <div class="alert alert-info">
                                                Charge: <strong>{{ number_format($gateway->charge, 1) }}%</strong>
                                                @if(getSettings()->card_funding_extra_charge > 0)
                                                    + <strong>{{ getSettings()->currency }}{{ getSettings()->card_funding_extra_charge }}</strong>
                                                @endif
                                            </div>
                                            <form action="{{ route('process-customer-load-wallet') }}" method="POST" id="wallet_load" class="customer-modern-form">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="amount" class="form-label">Amount</label>
                                                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="{{ old('amount') }}" required>
                                                </div>
                                                <button class="btn btn-primary customer-form-submit" type="button" onclick="loadWallet()"><i class="bx bx-credit-card me-1"></i> Pay now</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($allowReservedAccountFunding)
                                <div class="tab-pane fade {{ $allowCardFunding ? '' : 'show active' }}" id="fund-bank">
                                    @if(auth()->user()->customer->reserved_accounts->count() > 0)
                                        <div class="alert alert-success">
                                            Bank transfer funding is automatic. Once you transfer, your wallet is credited after processing.
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
        function loadWallet() {
            $.LoadingOverlay("show");
            document.forms["wallet_load"].submit();
        }
    </script>
@endsection
