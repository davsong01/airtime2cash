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
                            <li class="breadcrumb-item active">Operations overview</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-12">
                            <span class="ops-kicker"><i class="bx bx-pulse"></i> Live operations</span>
                            <h2>Operations control centre</h2>
                            <p>Monitor customer activity, wallet exposure, transaction flow, and provider health from one place.</p>
                        </div>
                        {{-- <div class="col-lg-5 mt-2 mt-lg-0">
                            <div class="ops-earnings-card">
                                <span><i class="bx bx-trending-up"></i> Recorded platform earnings</span>
                                <strong>{{ $currency }}{{ number_format($recordedEarnings, 2) }}</strong>
                                <small>Approved A2Cash fees + recorded transaction charges, before costs</small>
                            </div>
                            <div class="ops-hero-signal mt-1">
                                <span><i class="bx bxs-circle"></i> {{ number_format((int) $transactionSummary->today) }} transactions today</span>
                                <strong>{{ number_format((int) $airtimeToCashSummary->pending_count) }} A2Cash pending</strong>
                            </div>
                        </div> --}}
                    </div>
                </section>

               <section class="row">
                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('customers') }}" class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-primary"><i class="bx bx-group"></i></span>
                                <span class="ops-metric-label">Customer portfolio</span>
                                <strong>{{ number_format((int) $customerSummary->total) }}</strong>
                                <small>{{ number_format((int) $customerSummary->active) }} active accounts</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('admin.walletlog') }}" class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-wallet"></i></span>
                                <span class="ops-metric-label">Wallet exposure</span>
                                <strong>{{ $currency }}{{ number_format((float) $walletSummary->wallet_total, 2) }}</strong>
                                <small>{{ $currency }}{{ number_format((float) $walletSummary->a2cash_total, 2) }} in A2Cash wallets</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('admin.trans') }}" class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-info"><i class="bx bx-line-chart"></i></span>
                                <span class="ops-metric-label">All transactions</span>
                                <strong>{{ number_format((int) $transactionSummary->total) }}</strong>
                                <small>{{ number_format((int) $transactionSummary->successful) }} successful</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3 mb-2">
                        @if($customer?->customer?->user)
                            <a href="{{ route('customers.edit', $customer->customer->user_id) }}" class="card ops-metric-card h-100">
                                <div class="card-body">
                                    <span class="ops-metric-icon is-warning"><i class="bx bx-trophy"></i></span>
                                    <span class="ops-metric-label">Customer of the month</span>
                                    <strong class="text-truncate">
                                        {{ $customer->customer->user->username ?: $customer->customer->user->name }}
                                    </strong>
                                    <small>
                                        {{ number_format((int) $customer->count) }} transactions ·
                                        {{ $currency }}{{ number_format((float) $customer->total_amount, 2) }}
                                    </small>
                                </div>
                            </a>
                        @else
                            <div class="card ops-metric-card h-100">
                                <div class="card-body">
                                    <span class="ops-metric-icon is-warning"><i class="bx bx-trophy"></i></span>
                                    <span class="ops-metric-label">Customer of the month</span>
                                    <strong>Not available</strong>
                                    <small>No qualifying transactions this month</small>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-sm-6 col-xl-3 mb-2">
                        <div class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-server"></i></span>
                                <span class="ops-metric-label">Server address</span>
                                <strong>{{ request()->server('SERVER_ADDR', 'NOT SET') }}</strong>
                                <small>Application server IP</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3 mb-2">
                        <div class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-primary"><i class="bx bx-globe"></i></span>
                                <span class="ops-metric-label">Remote address</span>
                                <strong>{{ request()->ip() }}</strong>
                                <small>Current client IP</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card ops-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div><span class="ops-section-kicker">Service infrastructure</span><h5 class="mb-0">API provider performance</h5></div>
                        <a href="{{ route('api.index') }}" class="btn btn-sm btn-light-primary">Manage providers</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @forelse($apis as $api)
                                <div class="col-md-6 col-xl-4">
                                    <a href="{{ route('api.edit', $api->id) }}" class="ops-provider-card">
                                        <span class="ops-provider-mark">{{ str($api->name)->substr(0, 2)->upper() }}</span>
                                        <span class="ops-provider-main">
                                            <strong>{{ $api->name }}</strong>
                                            <small>{{ number_format($api->transactions_count) }} transactions · {{ number_format($api->products_count) }} products</small>
                                        </span>
                                        <span class="ops-provider-balance"><small>Balance</small><strong>{{ $api->balance !== null ? $currency . number_format((float) $api->balance, 2) : 'N/A' }}</strong></span>
                                    </a>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-2">No API providers have been configured.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-xl-8">
                        <div class="card ops-panel h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="ops-section-kicker">Transaction monitor</span>
                                    <h5 class="mb-0">Recent activity</h5>
                                </div>
                                <a href="{{ route('admin.trans') }}" class="btn btn-sm btn-light-primary">View all <i class="bx bx-right-arrow-alt ml-25"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 ops-table ops-activity-table">
                                    <thead>
                                        <tr><th>Transaction</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTransactions as $transaction)
                                            @php
                                                $user = $transaction->customer?->user;
                                                $name = trim(collect([$user?->firstname, $user?->middlename, $user?->lastname])->filter()->implode(' ')) ?: 'Unknown customer';
                                                $service = $transaction->product?->display_name ?: $transaction->product?->name ?: ($transaction->reason ?: 'Transaction');
                                                $status = strtolower($transaction->status ?? 'pending');
                                                $statusColor = in_array($status, ['success', 'delivered', 'completed']) ? 'success' : (in_array($status, ['failed', 'declined']) ? 'danger' : 'warning');
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong class="ops-service-name" title="{{ $service }}">{{ $service }}</strong>
                                                    <small class="ops-reference text-muted" title="{{ $transaction->transaction_id }}">{{ $transaction->transaction_id }}</small>
                                                </td>
                                                <td><strong class="ops-customer-name" title="{{ $name }}">{{ $name }}</strong><small class="ops-customer-email text-muted" title="{{ $user?->email ?: 'No customer email' }}">{{ $user?->email ?: 'No customer email' }}</small></td>
                                                <td><strong>{{ $currency }}{{ number_format((float) $transaction->total_amount, 2) }}</strong></td>
                                                <td><span class="badge badge-light-{{ $statusColor }}">{{ ucfirst($status) }}</span><small class="d-block text-muted mt-25">{{ $transaction->created_at->format('M j, g:i A') }}</small></td>
                                                <td class="text-right"><a href="{{ route('admin.single.transaction.view', $transaction->id) }}" class="btn btn-sm btn-icon btn-light-secondary" title="Review transaction"><i class="bx bx-show"></i></a></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center py-3 text-muted">No transaction activity is available yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 mt-2 mt-xl-0">
                        <div class="card ops-panel h-100">
                            <div class="card-header">
                                <span class="ops-section-kicker">Priority queue</span>
                                <h5 class="mb-0">Needs attention</h5>
                            </div>
                            <div class="card-body pt-0">
                                <a href="{{ route('admin.airtime.2.cash.log', ['status' => 'pending']) }}" class="ops-attention-item">
                                    <span class="ops-attention-icon is-warning"><i class="bx bx-transfer"></i></span>
                                    <span><strong>Airtime conversion reviews</strong><small>{{ number_format((int) $airtimeToCashSummary->pending_count) }} requests awaiting action</small></span>
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                                <a href="{{ route('admin.kyc', ['status' => 'review']) }}" class="ops-attention-item">
                                    <span class="ops-attention-icon is-primary"><i class="bx bx-id-card"></i></span>
                                    <span><strong>KYC review queue</strong><small>Review submitted customer identity data</small></span>
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                                <a href="{{ route('admin.autosync.webhooks.index', ['webhook_status' => ((int) $autoSyncWebhookSummary->failed > 0 ? 'failed' : 'pending')]) }}" class="ops-attention-item">
                                    <span class="ops-attention-icon {{ (int) $autoSyncWebhookSummary->failed > 0 ? 'is-danger' : 'is-info' }}"><i class="bx bx-cloud-download"></i></span>
                                    <span><strong>AutoSync webhooks</strong><small>{{ number_format((int) $autoSyncWebhookSummary->pending) }} pending · {{ number_format((int) $autoSyncWebhookSummary->failed) }} need resolution</small></span>
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                                <a href="{{ route('customers', ['status' => 'suspended']) }}" class="ops-attention-item">
                                    <span class="ops-attention-icon is-danger"><i class="bx bx-user-x"></i></span>
                                    <span><strong>Suspended customers</strong><small>{{ number_format((int) $customerSummary->suspended) }} restricted accounts</small></span>
                                    <i class="bx bx-chevron-right"></i>
                                </a>

                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>
@endsection
