@php
    $currency = getSettings()?->currency ?? 'NGN';
    $transferModeClass = static function ($transaction) {
        return match (strtolower((string) ($transaction->transfer_mode ?? ''))) {
            'manual' => 'text-warning',
            'auto_share' => 'text-success',
            default => 'text-muted',
        };
    };
@endphp

@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-a2c-dashboard.css') }}">
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb p-0 mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">A2Cash Management</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="a2c-admin-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="a2c-admin-kicker"><i class="bx bx-transfer-alt"></i> Conversion operations</span>
                            <h2>Airtime to Cash Management</h2>
                            <p>Review customer requests, monitor payout exposure, and keep conversion activity moving.</p>
                        </div>
                        <div class="col-lg-4 mt-2 mt-lg-0">
                            <div class="a2c-admin-queue">
                                <span>Pending review</span>
                                <strong>{{ number_format((int) $metrics->pending_count) }}</strong>
                                <small>{{ $currency }}{{ number_format((float) $metrics->pending_value, 2) }} expected payout</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card a2c-metric-card">
                            <div class="card-body">
                                <span class="a2c-metric-icon is-success"><i class="bx bx-check-circle"></i></span>
                                <span class="a2c-metric-label">Approved payouts</span>
                                <strong>{{ $currency }}{{ number_format((float) $metrics->approved_payout, 2) }}</strong>
                                <small>Total customer value approved</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card a2c-metric-card">
                            <div class="card-body">
                                <span class="a2c-metric-icon is-primary"><i class="bx bx-line-chart"></i></span>
                                <span class="a2c-metric-label">Approved Count</span>
                                <strong>{{ $currency }}{{ number_format((float) $metrics->approved_count, 2) }}</strong>
                                <small>Charges from approved requests</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card a2c-metric-card">
                            <div class="card-body">
                                <span class="a2c-metric-icon is-warning"><i class="bx bx-time-five"></i></span>
                                <span class="a2c-metric-label">Pending requests</span>
                                <strong>{{ number_format((int) $metrics->pending_count) }}</strong>
                                <small>Oldest requests appear first</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card a2c-metric-card">
                            <div class="card-body">
                                <span class="a2c-metric-icon is-danger"><i class="bx bx-x-circle"></i></span>
                                <span class="a2c-metric-label">Declined requests</span>
                                <strong>{{ number_format((int) $metrics->declined_count) }}</strong>
                                <small>{{ number_format((int) $metrics->today_count) }} requests submitted today</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card a2c-filter-card mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="a2c-filter-icon"><i class="bx bx-filter-alt"></i></span>
                            <div><h5 class="mb-25">Find transactions</h5><small class="text-muted">Search the queue using any combination of filters.</small></div>
                        </div>
                        @if(request()->query())
                            <a href="{{ route('admin.airtime.2.cash.log') }}" class="btn btn-sm btn-light-secondary mt-1 mt-sm-0"><i class="bx bx-reset mr-25"></i> Clear filters</a>
                        @endif
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.airtime.2.cash.log') }}" method="GET">
                            <div class="row">
                                <div class="col-md-6 col-xl-3 form-group">
                                    <label for="email">Customer email</label>
                                    <input type="search" class="form-control" id="email" name="email" placeholder="Search email" value="{{ request('email') }}">
                                </div>
                                <div class="col-md-6 col-xl-3 form-group">
                                    <label for="transaction_id">Transaction reference</label>
                                    <input type="search" class="form-control" id="transaction_id" name="transaction_id" placeholder="A2C-..." value="{{ request('transaction_id') }}">
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="product_id">Network</label>
                                    <select class="form-control" id="product_id" name="product_id">
                                        <option value="">All networks</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="transfer_mode">Transfer mode</label>
                                    <select class="form-control" id="transfer_mode" name="transfer_mode">
                                        <option value="">All modes</option>
                                        <option value="manual" @selected(request('transfer_mode') === 'manual')>Manual Transfer</option>
                                        <option value="auto_share" @selected(request('transfer_mode') === 'auto_share')>Auto Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All statuses</option>
                                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                                        <option value="declined" @selected(request('status') === 'declined')>Declined</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-xl-3 form-group mb-md-0">
                                    <label for="from">From date</label>
                                    <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
                                </div>
                                <div class="col-md-4 col-xl-3 form-group mb-md-0">
                                    <label for="to">To date</label>
                                    <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
                                </div>
                                <div class="col-md-4 col-xl-2 d-flex align-items-end">
                                    <button class="btn btn-primary btn-block" type="submit"><i class="bx bx-search mr-25"></i> Apply filters</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card a2c-queue-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div><h5 class="mb-25">Transactions</h5><small class="text-muted">{{ number_format($transactions->total()) }} matching requests</small></div>
                        <span class="badge badge-light-warning px-1 py-50">Pending first</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 a2c-admin-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Customer & network</th>
                                    <th>Transfer</th>
                                    <th>Financials</th>
                                    <th>Payout</th>
                                    <th>Wallet</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    @php
                                        $user = $transaction->customer?->user;
                                        $name = trim(collect([$user?->firstname, $user?->middlename, $user?->lastname])->filter()->implode(' ')) ?: 'Unknown customer';
                                        $status = strtolower((string) ($transaction->status ?? 'pending'));
                                        $statusColor = in_array($status, ['approved', 'successful', 'success', 'completed'], true)
                                            ? 'success'
                                            : ($status === 'declined' ? 'danger' : 'warning');
                                        $productName = $transaction->product?->display_name ?: $transaction->product?->name ?: 'Unknown network';
                                        $walletTrail = $transaction->wallets ?? collect();
                                        $hasWalletBalances = $walletTrail->contains(fn ($wallet) => ! is_null($wallet->balance_before) || ! is_null($wallet->balance_after));
                                        $transactionSnapshot = $transaction->transactionLog ?? null;
                                        $displayTrail = $hasWalletBalances
                                            ? $walletTrail
                                            : ($walletTrail->isEmpty() && $transactionSnapshot
                                                ? collect([$transactionSnapshot])
                                                : $walletTrail);
                                        $walletBefore = $displayTrail->first()?->balance_before;
                                        $walletAfter = $displayTrail->last()?->balance_after;
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="a2c-customer-mark">{{ str($name)->substr(0, 1)->upper() }}</span>
                                                <div class="min-width-0">
                                                    <strong class="d-block text-truncate">{{ $name }}</strong>
                                                    @if($user)
                                                        <a href="{{ route('customers.edit', $user->id) }}" class="d-block text-truncate">{{ $user->email }}</a>
                                                    @endif
                                                    <small class="text-muted">{{ $productName }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="d-block a2c-reference">{{ $transaction->transaction_id }}</strong>
                                            <span class="{{ $transferModeClass($transaction) }} font-weight-600 mt-50">{{ $transaction->transfer_mode === 'auto_share' ? 'Auto Transfer' : 'Manual Transfer' }}</span>
                                            <small class="d-block text-muted mt-50">{{ $transaction->phone_numbers ?: 'No sending number' }}</small>
                                        </td>
                                        <td>
                                            <strong class="d-block">{{ $currency }}{{ number_format($transaction->total_amount, 2) }}</strong>
                                            <small class="d-block text-muted">Profit rate {{ number_format((float) ($transaction->profit_percentage ?? 0), 2) }}%</small>
                                            <small class="d-block text-success">Income {{ $currency }}{{ number_format((float) ($transaction->profit ?? 0), 2) }}</small>
                                        </td>
                                        <td>
                                            <strong class="d-block">{{ $currency }}{{ number_format($transaction->amount_paid, 2) }}</strong>
                                            <small class="d-block text-muted">{{ $transaction->payment_method ?: 'Not specified' }}</small>
                                            @if($transaction->payment_method === 'Transfer to Bank Account')
                                                <small class="d-block text-muted">{{ $transaction->bank_name }} · {{ $transaction->account_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($displayTrail->count())
                                                <strong class="d-block">
                                                    {{ ! is_null($walletBefore) ? $currency . number_format((float) $walletBefore, 2) : 'n/a' }}
                                                    →
                                                    {{ ! is_null($walletAfter) ? $currency . number_format((float) $walletAfter, 2) : 'n/a' }}
                                                </strong>
                                                <small class="d-block text-muted">
                                                    {{ $hasWalletBalances ? 'Initial / final wallet balance' : 'Snapshot from transaction log' }}
                                                </small>
                                            @else
                                                <small class="text-muted">No wallet trail</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $statusColor }} px-75 py-50">{{ ucfirst($transaction->status) }}</span>
                                            <small class="d-block text-muted mt-50">{{ $transaction->created_at->format('M j, Y') }}</small>
                                            <small class="d-block text-muted">{{ $transaction->created_at->format('g:i A') }}</small>
                                        </td>
                                        <td class="text-right">
                                            <a class="btn btn-primary btn-sm" href="{{ route('admin.single.airtime2cash.transaction.view', $transaction->id) }}">
                                                <i class="bx bx-show mr-25"></i> Review
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-3"><i class="bx bx-search-alt d-block font-large-1 text-muted mb-1"></i><strong>No transactions found</strong><p class="text-muted mb-0">Try clearing or adjusting the filters.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($transactions->hasPages())
                        <div class="card-footer d-flex justify-content-end">{{ $transactions->links() }}</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
