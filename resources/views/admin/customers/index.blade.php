@php
    $currency = getSettings()?->currency ?? 'NGN';
    $canEditCustomers = hasAccess('customers.edit');
@endphp

@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb p-0 mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Customer operations</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero ops-hero-customers mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-group"></i> Customer operations</span>
                            <h2>Customer portfolio</h2>
                            <p>Find accounts quickly, review account health, and move directly into customer support or compliance actions.</p>
                        </div>
                        <div class="col-lg-4 mt-2 mt-lg-0 text-lg-right">
                            <a href="{{ route('admin.kyc') }}" class="btn btn-light"><i class="bx bx-shield-quarter mr-50"></i> Open KYC dashboard</a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-3"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-primary"><i class="bx bx-group"></i></span><span class="ops-metric-label">All customers</span><strong>{{ number_format((int) $summary->total) }}</strong><small>{{ number_format((int) $summary->new_this_month) }} joined this month</small></div></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-success"><i class="bx bx-user-check"></i></span><span class="ops-metric-label">Active accounts</span><strong>{{ number_format((int) $summary->active) }}</strong><small>Available for transactions</small></div></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-info"><i class="bx bx-badge-check"></i></span><span class="ops-metric-label">KYC verified</span><strong>{{ number_format((int) $summary->verified) }}</strong><small>Identity review completed</small></div></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-danger"><i class="bx bx-user-x"></i></span><span class="ops-metric-label">Suspended</span><strong>{{ number_format((int) $summary->suspended) }}</strong><small>Accounts currently restricted</small></div></div></div>
                </section>

                <section class="card ops-panel ops-filter-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center"><span class="ops-filter-icon"><i class="bx bx-filter-alt"></i></span><div><h5 class="mb-25">Find customers</h5><small class="text-muted">Search broadly or combine precise filters.</small></div></div>
                        @if(request()->query() || $selectedStatus)
                            <a href="{{ route('customers') }}" class="btn btn-sm btn-light-secondary mt-1 mt-sm-0"><i class="bx bx-reset mr-25"></i> Clear filters</a>
                        @endif
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customers') }}" method="GET">
                            <div class="row">
                                <div class="col-md-6 col-xl-4 form-group">
                                    <label for="search">Name, email, username or phone</label>
                                    <div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="bx bx-search"></i></span></div><input type="search" class="form-control" id="search" name="search" placeholder="Start typing customer details" value="{{ request('search') }}"></div>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="status">Account status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All statuses</option>
                                        @foreach(['active' => 'Active', 'suspended' => 'Suspended', 'delete' => 'Deleted', 'api' => 'API Customers', 'email-blacklist' => 'Email Blacklist', 'phone-blacklist' => 'Phone Blacklist'] as $value => $label)
                                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="level">Customer level</label>
                                    <select class="form-control" id="level" name="level"><option value="">All levels</option>@foreach($customer_levels as $level)<option value="{{ $level->id }}" @selected((string) request('level') === (string) $level->id)>{{ $level->name }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group"><label for="from">Joined from</label><input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}"></div>
                                <div class="col-md-6 col-xl-2 form-group"><label for="to">Joined to</label><input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}"></div>
                                <div class="col-md-6 col-xl-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit"><i class="bx bx-search mr-25"></i> Apply filters</button></div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card ops-panel">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div><span class="ops-section-kicker">Account directory</span><h5 class="mb-0">{{ number_format($customers->total()) }} matching customers</h5></div>
                        <span class="badge badge-light-primary px-1 py-50">Newest first</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 ops-table ops-customer-table">
                            <thead><tr><th>S/N</th><th>Customer</th><th>Account</th><th>Verification</th><th>Balances</th><th>Joined</th>@if($canEditCustomers)<th class="text-right">Action</th>@endif</tr></thead>
                            <tbody>
                                @forelse($customers as $user)
                                    @php
                                        $name = trim(collect([$user->firstname, $user->middlename, $user->lastname])->filter()->implode(' ')) ?: 'Unnamed customer';
                                        $status = strtolower($user->status ?: 'unknown');
                                        $statusColor = $status === 'active' ? 'success' : ($status === 'suspended' || $status === 'delete' ? 'danger' : 'warning');
                                        $kycVerified = $user->customer?->kyc_status === 'verified';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $customers->firstItem() + $loop->index }}</td>
                                        <td><div class="d-flex align-items-center"><span class="ops-customer-mark">{{ str($name)->substr(0, 1)->upper() }}</span><div class="min-width-0"><a href="{{ route('customers.edit', $user->id) }}" class="d-block font-weight-bold text-truncate">{{ $name }}</a><small class="d-block text-muted text-truncate">{{ $user->email }}</small><small class="d-block text-muted">{{ $user->phone ?: 'No phone number' }}</small></div></div></td>
                                        <td><strong class="d-block">{{ '@' . ($user->username ?: 'not-set') }}</strong><span class="badge badge-light-{{ $statusColor }} mt-50">{{ ucfirst(str_replace('-', ' ', $status)) }}</span><small class="d-block text-muted mt-50">{{ $user->customer?->level?->name ?: 'No level assigned' }}</small></td>
                                        <td><span class="ops-verification {{ $kycVerified ? 'is-verified' : 'is-pending' }}"><i class="bx {{ $kycVerified ? 'bx-check-shield' : 'bx-time-five' }}"></i> {{ $kycVerified ? 'KYC verified' : 'KYC pending' }}</span><small class="d-block text-muted mt-50">Email {{ $user->email_verified_at ? 'verified' : 'unverified' }}</small></td>
                                        <td><strong class="d-block">{{ $currency }}{{ number_format((float) ($user->customer?->wallet ?? 0), 2) }}</strong><small class="d-block text-muted">Referral {{ $currency }}{{ number_format((float) ($user->customer?->referal_wallet ?? 0), 2) }}</small><small class="d-block text-muted">A2Cash {{ $currency }}{{ number_format((float) ($user->customer?->a2cashwallet ?? 0), 2) }}</small></td>
                                        <td><strong class="d-block">{{ $user->created_at->format('M j, Y') }}</strong><small class="text-muted">{{ $user->created_at->format('g:i A') }}</small></td>
                                        @if($canEditCustomers)<td class="text-right"><a href="{{ route('customers.edit', $user->id) }}" class="btn btn-sm btn-primary"><i class="bx bx-user mr-25"></i> Open</a></td>@endif
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-3"><i class="bx bx-user-x d-block font-large-1 text-muted mb-1"></i><strong>No customers found</strong><p class="text-muted mb-0">Try clearing or adjusting the filters.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($customers->hasPages())<div class="card-footer d-flex justify-content-between align-items-center flex-wrap"><small class="text-muted">Showing {{ number_format($customers->firstItem()) }}–{{ number_format($customers->lastItem()) }} of {{ number_format($customers->total()) }}</small><div>{{ $customers->links() }}</div></div>@endif
                </section>
            </div>
        </div>
    </div>
@endsection
