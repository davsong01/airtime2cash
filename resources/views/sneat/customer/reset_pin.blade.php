@extends('sneat.layouts.app')
@section('title', 'Reset Transaction Pin')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Security',
        'title' => 'Reset Transaction PIN',
        'subtitle' => 'We will send a reset link to your registered email address.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-reset fs-4"></i></span>
                    <div><h5 class="mb-1">Reset request</h5><small class="text-muted">Confirm your password before we email the reset link.</small></div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Current email: <strong>{{ auth()->user()->email }}</strong>
                    </div>
                    <form action="{{ route('process.transaction.pin.reset') }}" method="POST" autocomplete="off" class="customer-modern-form">
                        @csrf
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input autocomplete="off" type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-envelope me-1"></i> Send reset link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
