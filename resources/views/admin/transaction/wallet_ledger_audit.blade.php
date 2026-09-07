@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')

@section('title', 'Wallet Ledger Audit')

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
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.walletlog') }}">Wallet log</a></li>
                            <li class="breadcrumb-item active">Wallet ledger audit</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-search-alt"></i> Reconciliation</span>
                            <h2>Wallet ledger audit</h2>
                            <p>These are wallet-impacting transaction logs that do not currently have a matching row in the wallet ledger.</p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.walletlog') }}" class="btn btn-light">
                                <i class="bx bx-wallet mr-50"></i> Wallet log
                            </a>
                            <a href="{{ route('admin.earninglog') }}" class="btn btn-outline-primary ml-50">
                                <i class="bx bx-trending-up mr-50"></i> Earning log
                            </a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-6 mb-2">
                        <div class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-warning"><i class="bx bx-analyse"></i></span>
                                <span class="ops-metric-label">Potential missing ledger entries</span>
                                <strong>{{ number_format((int) $summary->total) }}</strong>
                                <small>Transaction logs with wallet signals but no wallet row</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-6 mb-2">
                        <div class="card ops-metric-card h-100">
                            <div class="card-body">
                                <span class="ops-metric-icon is-danger"><i class="bx bx-money"></i></span>
                                <span class="ops-metric-label">Impacted amount</span>
                                <strong>{{ $currency }}{{ number_format((float) $summary->impacted_total, 2) }}</strong>
                                <small>Successful wallet-impacting logs without matching ledger rows</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <span class="ops-section-kicker">Audit trail</span>
                            <h5 class="mb-0">Missing ledger candidates</h5>
                        </div>
                        <span class="badge badge-light-warning px-1 py-50">Latest first</span>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.walletledgeraudit') }}">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="email">Customer Email</label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        placeholder="Enter customer email address"
                                        value="{{ request('email') }}"
                                    >
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="transaction_id">Transaction ID</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="transaction_id"
                                        name="transaction_id"
                                        placeholder="Enter transaction ID"
                                        value="{{ request('transaction_id') }}"
                                    >
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label for="from">From</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="from"
                                        name="from"
                                        value="{{ request('from') }}"
                                    >
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label for="to">To</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="to"
                                        name="to"
                                        value="{{ request('to') }}"
                                    >
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Transaction</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Balance Before</th>
                                        <th>Balance After</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transactions as $transaction)
                                        @php
                                            $user = $transaction->customer?->user;
                                            $name = trim(collect([$user?->firstname, $user?->middlename, $user?->lastname])->filter()->implode(' ')) ?: ($transaction->customer_name ?? 'Unknown customer');
                                            $status = strtolower((string) ($transaction->status ?? 'pending'));
                                            $statusColor = in_array($status, ['success', 'successful', 'delivered', 'completed', 'approved'], true)
                                                ? 'success'
                                                : (in_array($status, ['failed', 'declined', 'cancelled'], true) ? 'danger' : 'warning');
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                            <td>
                                                {{ optional($transaction->created_at)->format('M j, Y') ?? 'N/A' }}<br>
                                                <small class="text-muted">{{ optional($transaction->created_at)->format('g:i A') ?? '' }}</small>
                                            </td>
                                            <td>
                                                <strong class="d-block">{{ $name }}</strong>
                                                <small class="d-block text-muted">{{ $user?->email ?? $transaction->customer_email ?? 'No email' }}</small>
                                                <small class="d-block text-muted">{{ $user?->phone ?? $transaction->customer_phone ?? '' }}</small>
                                            </td>
                                            <td>
                                                <strong class="d-block">{{ $transaction->transaction_id }}</strong>
                                                <small class="d-block text-muted">{{ $transaction->unique_element ?? '—' }}</small>
                                            </td>
                                            <td>{{ $transaction->reason ?? $transaction->descr ?? '—' }}</td>
                                            <td>
                                                <span class="badge badge-light-{{ $statusColor }}">
                                                    {{ ucfirst(str_replace('-', ' ', $status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $currency }}{{ number_format((float) ($transaction->total_amount ?? 0), 2) }}</td>
                                            <td>{{ $currency }}{{ number_format((float) ($transaction->balance_before ?? 0), 2) }}</td>
                                            <td>{{ $currency }}{{ number_format((float) ($transaction->balance_after ?? 0), 2) }}</td>
                                            <td>
                                                <a
                                                    href="{{ route('admin.single.transaction.view', $transaction->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    target="_blank"
                                                >
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-3 text-muted">
                                                No missing ledger candidates found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $transactions->links() }}
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
