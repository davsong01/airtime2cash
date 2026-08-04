@extends('sneat.layouts.app')
@section('title', 'Withdraw Earnings')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Network',
        'title' => 'Withdraw Earnings',
        'subtitle' => 'Move your downline earnings into your wallet.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-wallet fs-4"></i></span>
                    <div><h5 class="mb-1">Withdrawal details</h5><small class="text-muted">Enter the amount to move into your wallet.</small></div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Available referral earnings: <strong>{{ getSettings()->currency }}{{ number_format(referralBalance(auth()->user()), 2) }}</strong>
                    </div>
                    <form action="{{ route('process.withdrawal') }}" method="POST" class="customer-modern-form">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" value="{{ old('value') ?? referralBalance(auth()->user()) }}" placeholder="Amount to withdraw..." id="amount" min="0" max="{{ referralBalance(auth()->user()) }}" required>
                        </div>
                        <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-wallet me-1"></i> Withdraw earnings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
