@extends('sneat.layouts.app')
@section('title', 'Create Transaction Pin')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Security',
        'title' => 'Create Transaction PIN',
        'subtitle' => 'Set a PIN to approve purchases and wallet actions.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-lock-alt fs-4"></i></span>
                    <div><h5 class="mb-1">Secure transactions</h5><small class="text-muted">Create the PIN used to approve wallet activity.</small></div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Your security is important. Enter your current password and choose a 5 digit PIN for transactions.</p>
                    <form action="{{ route('customer.process.create.pin') }}" method="POST" class="customer-modern-form">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input autocomplete="off" type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-4">
                            <label for="transaction_pin" class="form-label">Transaction PIN</label>
                            <input autocomplete="off" type="text" class="form-control" id="transaction_pin" maxlength="5" name="transaction_pin" inputmode="numeric" required>
                        </div>
                        <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-lock-alt me-1"></i> Create PIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
