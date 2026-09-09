@php
    $currency = getSettings()?->currency ?? 'NGN';
    $canEditCustomers = hasAccess('customers.edit');
@endphp

@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
    <style>
        .customer-access-cell {
            min-width: 190px;
        }

        .customer-access-services,
        .customer-verification-states {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .customer-access-chip,
        .customer-verification-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: .3rem .55rem;
            font-size: .7rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        .customer-access-chip.is-enabled,
        .customer-verification-chip.is-verified {
            color: #168554;
            background: #e9f8f1;
            border-color: #c8eddd;
        }

        .customer-access-chip.is-disabled {
            color: #b33a3a;
            background: #fff1f1;
            border-color: #f4d0d0;
        }

        .customer-verification-chip.is-pending {
            color: #6b7280;
            background: #f4f6f8;
            border-color: #e1e5ea;
        }

        .customer-verification-states {
            margin-top: .45rem;
            padding-top: .45rem;
            border-top: 1px solid #edf0f4;
        }

        .customer-directory-table thead th {
            border-top: 0;
            color: #697386;
            font-size: .69rem;
            letter-spacing: .055em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .customer-directory-table tbody td {
            padding-top: .85rem;
            padding-bottom: .85rem;
            vertical-align: middle;
        }

        .customer-directory-table tbody tr {
            transition: background-color .18s ease, transform .18s ease;
        }

        .customer-directory-table tbody tr:hover {
            background: #f8faff;
        }

        .customer-row-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            color: #637085;
            background: #f1f4f8;
            font-size: .72rem;
            font-weight: 700;
        }

        .customer-account-cell {
            min-width: 235px;
        }

        .customer-account {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
        }

        .customer-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, #5969e8, #7c4dff);
            box-shadow: 0 5px 12px rgba(89, 105, 232, .2);
            font-size: .82rem;
            font-weight: 700;
        }

        .customer-account-name {
            color: #202a3b;
            font-size: .86rem;
        }

        .customer-account-meta {
            display: flex;
            align-items: center;
            gap: .3rem;
            color: #7b8495;
            font-size: .72rem;
            line-height: 1.55;
        }

        .customer-account-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .35rem;
        }

        .customer-account-tags .badge {
            padding: .28rem .45rem;
            font-size: .64rem;
            font-weight: 600;
        }

        .customer-balance-cell {
            min-width: 175px;
        }

        .customer-balance-primary,
        .customer-balance-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border-radius: 9px;
            padding: .4rem .5rem;
        }

        .customer-balance-primary {
            margin-bottom: .25rem;
            color: #3347c8;
            background: #eef1ff;
        }

        .customer-balance-primary:hover {
            color: #2436aa;
            background: #e5e9ff;
        }

        .customer-balance-row {
            color: #697386;
            font-size: .69rem;
        }

        .customer-balance-row:hover {
            color: #3347c8;
            background: #f5f7fb;
        }

        .customer-balance-row.is-variance {
            color: #b54708;
            background: #fff7e8;
        }

        .customer-balance-label {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .67rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .customer-balance-value {
            font-size: .76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .customer-joined {
            min-width: 120px;
        }

        .customer-joined-date {
            display: flex;
            align-items: center;
            gap: .45rem;
            color: #303b4e;
            font-size: .78rem;
            font-weight: 700;
        }

        .customer-joined-date i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            color: #6371db;
            background: #eff1ff;
            font-size: 1rem;
        }

        .customer-joined-time {
            display: block;
            margin: .2rem 0 0 2.9rem;
            color: #8a93a3;
            font-size: .68rem;
        }

        .customer-row-actions {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .customer-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 9px;
            padding: 0;
        }
    </style>
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
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="kyc_status">KYC status</label>
                                    <select class="form-control" id="kyc_status" name="kyc_status">
                                        <option value="">All KYC states</option>
                                        @foreach(['verified' => 'Verified', 'in-review' => 'In review', 'awaiting-approval' => 'Awaiting approval', 'pending' => 'Pending', 'unverified' => 'Unverified'] as $value => $label)
                                            <option value="{{ $value }}" @selected(request('kyc_status') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="order_by">Order by</label>
                                    <select class="form-control" id="order_by" name="order_by">
                                        <option value="registration" @selected(request('order_by', 'registration') === 'registration')>Registration</option>
                                        <option value="wallet_balance" @selected(request('order_by') === 'wallet_balance')>Wallet Balance</option>
                                        <option value="kyc_verified" @selected(request('order_by') === 'kyc_verified')>KYC Verified</option>
                                        <option value="active_status" @selected(request('order_by') === 'active_status')>Active Status</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group"><label for="from">Joined from</label><input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}"></div>
                                <div class="col-md-6 col-xl-2 form-group"><label for="to">Joined to</label><input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}"></div>
                                <div class="col-md-6 col-xl-2 form-group mt-2"><button class="btn btn-primary btn-block" type="submit"><i class="bx bx-search mr-25"></i> Apply filters</button></div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card ops-panel">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        @php
                            $orderBy = request('order_by', 'registration');
                            $orderLabel = match ($orderBy) {
                                'wallet_balance' => 'Wallet balance first',
                                'kyc_verified' => 'KYC verified first',
                                'active_status' => 'Active status first',
                                default => 'Newest first',
                            };
                        @endphp
                        <div class="mb-1 mb-sm-0">
                            <span class="ops-section-kicker">Account directory</span>
                            <h5 class="mb-0">{{ number_format($customers->total()) }} matching customers</h5>
                        </div>
                                <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                                    <span class="badge badge-light-primary px-1 py-50">{{ $orderLabel }}</span>
                                    @if($canEditCustomers)
                                        <select class="form-control form-control-sm" id="bulkActionSelect" style="min-width: 180px;">
                                            <option value="">Bulk action</option>
                                            <option value="deactivate">Deactivate</option>
                                            <option value="activate">Activate</option>
                                            <option value="suspend">Suspend</option>
                                            <option value="delete">Delete</option>
                                            <option value="enable_w2bank_manual_access">Enable Manual Wallet 2 Bank</option>
                                            <option value="disable_w2bank_manual_access">Disable Manual Wallet 2 Bank</option>
                                            <option value="enable_w2bank_auto_access">Enable Auto Wallet 2 Bank</option>
                                            <option value="disable_w2bank_auto_access">Disable Auto Wallet 2 Bank</option>
                                            <option value="enable_a2c_access">Enable Airtime 2 Cash</option>
                                            <option value="disable_a2c_access">Disable Airtime 2 Cash</option>
                                            <option value="move_level">Move to level</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-secondary" id="bulkActionApplyBtn">Apply</button>
                                    @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <form id="customerBulkForm" method="POST" action="{{ route('customers.bulk-actions') }}">
                            @csrf
                            <input type="hidden" name="action" id="bulkActionValue">
                            <input type="hidden" name="customer_ids" id="bulkCustomerIds">
                        </form>
                        <table class="table table-hover mb-0 ops-table customer-directory-table">
                            <thead>
                                <tr>
                                    @if($canEditCustomers)
                                        <th style="width: 34px; padding-left: .5rem; padding-right: .5rem;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAllCustomers">
                                                <label class="custom-control-label" for="selectAllCustomers"></label>
                                            </div>
                                        </th>
                                    @endif
                                    <th style="white-space: nowrap;">S/N</th><th>Account</th><th>Access</th><th>Balances</th><th>Joined</th>@if($canEditCustomers)<th class="text-right">Actions</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $user)
                                    @php
                                        $name = trim(collect([$user->firstname, $user->middlename, $user->lastname])->filter()->implode(' ')) ?: 'Unnamed customer';
                                        $status = strtolower($user->status ?: 'unknown');
                                        $statusColor = $status === 'active' ? 'success' : ($status === 'suspended' || $status === 'delete' ? 'danger' : 'warning');
                                        $kycVerified = $user->customer?->kyc_status === 'verified';
                                        $walletAccess = (bool) ($user->customer?->can_access_w2bank ?? true);
                                        $walletAutoAccess = (bool) ($user->customer?->can_access_w2bank_auto ?? false);
                                        $a2cAccess = (bool) ($user->customer?->can_access_a2c ?? false);
                                        $ledgerWalletBalance = (float) ($user->live_wallet_balance ?? 0);
                                        $storedWalletBalance = (float) ($user->customer?->wallet ?? 0);
                                        $walletBalanceVariance = $ledgerWalletBalance - $storedWalletBalance;
                                    @endphp
                                    <tr>
                                        @if($canEditCustomers)
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input customer-checkbox" id="customerSelect{{ $user->id }}" value="{{ $user->id }}" form="customerBulkForm">
                                                    <label class="custom-control-label" for="customerSelect{{ $user->id }}"></label>
                                                </div>
                                            </td>
                                        @endif
                                        <td style="width: 52px; white-space: nowrap;"><span class="customer-row-number">{{ $customers->firstItem() + $loop->index }}</span></td>
                                        <td class="customer-account-cell">
                                            <div class="customer-account">
                                                <div class="min-width-0">
                                                    <a href="{{ route('customers.edit', $user->id) }}" class="customer-account-name d-block font-weight-bold text-truncate">{{ $name }}</a>
                                                    <span class="customer-account-meta text-truncate"><i class="bx bx-envelope"></i>{{ $user->email }}</span>
                                                    <span class="customer-account-meta"><i class="bx bx-phone"></i>{{ $user->phone ?: 'No phone number' }}</span>
                                                    <div class="customer-account-tags">
                                                        <span class="badge badge-light-{{ $statusColor }}">{{ ucfirst(str_replace('-', ' ', $status)) }}</span>
                                                        <span class="badge badge-light-secondary">{{ '@' . ($user->username ?: 'not-set') }}</span>
                                                        <span class="badge badge-light-info">{{ $user->customer?->level?->name ?: 'No level' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="customer-access-cell">
                                            <div class="customer-access-services">
                                                <span class="customer-access-chip {{ $walletAccess ? 'is-enabled' : 'is-disabled' }}" title="Manual Wallet 2 Bank {{ $walletAccess ? 'Enabled' : 'Disabled' }}">
                                                    <i class="bx {{ $walletAccess ? 'bx-check-circle' : 'bx-x-circle' }}"></i> Manual W2B
                                                </span>
                                                <span class="customer-access-chip {{ $walletAutoAccess ? 'is-enabled' : 'is-disabled' }}" title="Auto Wallet 2 Bank {{ $walletAutoAccess ? 'Enabled' : 'Disabled' }}">
                                                    <i class="bx {{ $walletAutoAccess ? 'bx-check-circle' : 'bx-x-circle' }}"></i> Auto W2B
                                                </span>
                                                <span class="customer-access-chip {{ $a2cAccess ? 'is-enabled' : 'is-disabled' }}" title="Airtime 2 Cash {{ $a2cAccess ? 'Enabled' : 'Disabled' }}">
                                                    <i class="bx {{ $a2cAccess ? 'bx-check-circle' : 'bx-x-circle' }}"></i> Airtime 2 Cash
                                                </span>
                                            </div>
                                            <div class="customer-verification-states">
                                                <span class="customer-verification-chip {{ $kycVerified ? 'is-verified' : 'is-pending' }}">
                                                    <i class="bx {{ $kycVerified ? 'bx-check-shield' : 'bx-time-five' }}"></i> KYC {{ $kycVerified ? 'verified' : 'pending' }}
                                                </span>
                                                <span class="customer-verification-chip {{ $user->email_verified_at ? 'is-verified' : 'is-pending' }}">
                                                    <i class="bx {{ $user->email_verified_at ? 'bx-envelope-open' : 'bx-envelope' }}"></i> Email {{ $user->email_verified_at ? 'verified' : 'unverified' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="customer-balance-cell">
                                            <a href="{{ route('admin.walletlog', ['email' => $user->email]) }}" class="customer-balance-primary">
                                                <span class="customer-balance-label"><i class="bx bx-calculator"></i> Ledger</span>
                                                <span class="customer-balance-value">{{ $currency }}{{ number_format($ledgerWalletBalance, 2) }}</span>
                                            </a>
                                            <div class="customer-balance-row">
                                                <span class="customer-balance-label"><i class="bx bx-data"></i> Stored</span>
                                                <span class="customer-balance-value">{{ $currency }}{{ number_format($storedWalletBalance, 2) }}</span>
                                            </div>
                                            @if(abs($walletBalanceVariance) > 0.009)
                                                <div class="customer-balance-row is-variance" title="Ledger balance minus stored balance">
                                                    <span class="customer-balance-label"><i class="bx bx-error-circle"></i> Variance</span>
                                                    <span class="customer-balance-value">{{ $walletBalanceVariance < 0 ? '-' : '+' }}{{ $currency }}{{ number_format(abs($walletBalanceVariance), 2) }}</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('admin.earninglog', ['upline_email' => $user->email]) }}" class="customer-balance-row">
                                                <span class="customer-balance-label"><i class="bx bx-gift"></i> Referral</span>
                                                <span class="customer-balance-value">{{ $currency }}{{ number_format((float) ($user->customer?->referal_wallet ?? 0), 2) }}</span>
                                            </a>
                                            <div class="customer-balance-row">
                                                <span class="customer-balance-label"><i class="bx bx-transfer-alt"></i> A2Cash</span>
                                                <span class="customer-balance-value">{{ $currency }}{{ number_format((float) ($user->customer?->a2cashwallet ?? 0), 2) }}</span>
                                            </div>
                                        </td>
                                        <td class="customer-joined"><span class="customer-joined-date"><i class="bx bx-calendar"></i>{{ $user->created_at->format('M j, Y') }}</span><small class="customer-joined-time">{{ $user->created_at->format('g:i A') }}</small></td>
                                        @if($canEditCustomers)
                                            <td class="text-right">
                                                <div class="customer-row-actions">
                                                    <a href="{{ route('customers.edit', $user->id) }}" class="btn btn-outline-primary customer-action-button" title="Open customer" aria-label="Open customer"><i class="bx bx-user"></i></a>
                                                    @if($user->customer)
                                                        <a href="{{ route('customers.wallet-report', $user) }}" class="btn btn-outline-success customer-action-button" title="Download wallet report" aria-label="Download wallet report"><i class="bx bx-download"></i></a>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $canEditCustomers ? 7 : 6 }}" class="text-center py-3"><i class="bx bx-user-x d-block font-large-1 text-muted mb-1"></i><strong>No customers found</strong><p class="text-muted mb-0">Try clearing or adjusting the filters.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($customers->hasPages())<div class="card-footer d-flex justify-content-between align-items-center flex-wrap"><small class="text-muted">Showing {{ number_format($customers->firstItem()) }}–{{ number_format($customers->lastItem()) }} of {{ number_format($customers->total()) }}</small><div>{{ $customers->links() }}</div></div>@endif
                </section>
            </div>
        </div>
    </div>

    @if($canEditCustomers)
        <div class="modal fade" id="moveLevelModal" tabindex="-1" role="dialog" aria-labelledby="moveLevelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('customers.bulk-actions') }}" id="moveLevelForm">
                        @csrf
                        <input type="hidden" name="action" value="move_level">
                        <input type="hidden" name="customer_ids" id="moveLevelCustomerIds">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="moveLevelModalLabel">Move selected customers</h5>
                                <small class="text-muted">Choose the target active customer level.</small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-0">
                                <label for="moveLevelId">Customer level</label>
                                <select class="form-control" name="level_id" id="moveLevelId" required>
                                    <option value="">Select level</option>
                                    @foreach($activeCustomerLevels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Move the selected customers to this level?')">Move</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('page-script')
    <script>
        document.getElementById('selectAllCustomers')?.addEventListener('change', function () {
            document.querySelectorAll('.customer-checkbox').forEach(checkbox => checkbox.checked = this.checked);
        });

        document.getElementById('bulkActionApplyBtn')?.addEventListener('click', function () {
            const action = document.getElementById('bulkActionSelect')?.value;
            const ids = Array.from(document.querySelectorAll('.customer-checkbox:checked')).map(checkbox => checkbox.value);

            if (!action || ids.length === 0) {
                alert('Select an action and at least one customer.');
                return;
            }

            if (action === 'move_level') {
                document.getElementById('moveLevelCustomerIds').value = ids.join(',');
                $('#moveLevelModal').modal('show');
                return;
            }

            const messages = {
                activate: 'Activate the selected customer(s)?',
                deactivate: 'Deactivate the selected customer(s)?',
                suspend: 'Suspend the selected customer(s)?',
                delete: 'Delete the selected customer(s)? This will mark them as deleted.',
                enable_w2bank_manual_access: 'Enable Manual Wallet 2 Bank access for the selected customer(s)?',
                disable_w2bank_manual_access: 'Disable Manual Wallet 2 Bank access for the selected customer(s)?',
                enable_w2bank_auto_access: 'Enable Auto Wallet 2 Bank access for the selected customer(s)?',
                disable_w2bank_auto_access: 'Disable Auto Wallet 2 Bank access for the selected customer(s)?',
                enable_a2c_access: 'Enable Airtime 2 Cash access for the selected customer(s)?',
                disable_a2c_access: 'Disable Airtime 2 Cash access for the selected customer(s)?',
            };

            if (!window.confirm(messages[action] || 'Apply this action to the selected customer(s)?')) {
                return;
            }

            document.getElementById('bulkActionValue').value = action;
            document.getElementById('bulkCustomerIds').value = ids.join(',');
            document.getElementById('customerBulkForm').submit();
        });
    </script>
@endsection
