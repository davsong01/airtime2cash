@extends('sneat.layouts.app')
@section('title', 'Edit Profile')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Profile',
        'title' => 'Edit Profile',
        'subtitle' => 'Update your identity and security details in one place.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Referral link</div>
                    <p class="mb-3">Share this link to invite others.</p>
                    <div class="p-3 rounded bg-light text-break">{{ url('/register') . '?referral=' . auth()->user()->username }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-user fs-4"></i></span>
                    <div><h5 class="mb-1">Profile details</h5><small class="text-muted">Keep your personal and security details current.</small></div>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" autocomplete="off" class="customer-modern-form">
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First name</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" value="{{ auth()->user()->firstname }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="middlename" class="form-label">Middle name</label>
                                <input type="text" class="form-control" id="middlename" name="middlename" value="{{ auth()->user()->middlename }}">
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last name</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" value="{{ auth()->user()->lastname }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ auth()->user()->phone }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email address</label>
                                <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer level</label>
                                <input type="text" class="form-control" value="Level {{ auth()->user()->customer?->level?->name }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="new_transaction_pin" class="form-label">New transaction PIN</label>
                                <input type="text" class="form-control" name="new_transaction_pin">
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New password</label>
                                <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="customer-form-actions mt-4 mx-n4 mb-n4">
                            <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-save me-1"></i> Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
