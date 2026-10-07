@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')
@section('title', 'BVN Debit Log')
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
                        <li class="breadcrumb-item"><a href="{{ route('admin.walletlog') }}">Financials</a></li>
                        <li class="breadcrumb-item active">BVN debit log</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <section class="ops-hero mb-2">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="ops-kicker"><i class="bx bx-shield-quarter"></i> Verification billing</span>
                        <h2>BVN debit log</h2>
                        <p>Audit the wallet debits automatically collected for completed BVN verification charges.</p>
                    </div>
                    <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                        <a href="{{ route('admin.walletlog') }}" class="btn btn-light"><i class="bx bx-wallet mr-50"></i> Wallet log</a>
                        <a href="{{ route('admin.kyc') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-user-check mr-50"></i> KYC review</a>
                    </div>
                </div>
            </section>

            <section class="row">
                <div class="col-sm-6 col-xl-6">
                    <div class="card ops-metric-card">
                        <div class="card-body">
                            <span class="ops-metric-icon is-danger"><i class="bx bx-down-arrow-alt"></i></span>
                            <span class="ops-metric-label">Total BVN debits</span>
                            <strong>{{ $currency }}{{ number_format((float) $total, 2) }}</strong>
                            <small>Automatically collected from customer wallets</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-6">
                    <div class="card ops-metric-card">
                        <div class="card-body">
                            <span class="ops-metric-icon is-success"><i class="bx bx-receipt"></i></span>
                            <span class="ops-metric-label">Debit entries</span>
                            <strong>{{ number_format((int) $count) }}</strong>
                            <small>Matching BVN verification fee records</small>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="ops-section-kicker">BVN verification billing</span>
                            <h5 class="card-title mb-0">{{ number_format($transactions->total()) }} matching debits</h5>
                        </div>
                        <span class="badge badge-light-primary px-1 py-50">Latest first</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.bvn.verification.debits') }}" method="GET" class="mb-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <fieldset class="form-group">
                                        <label for="email">Customer email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ request('email') }}" placeholder="Search customer email">
                                    </fieldset>
                                </div>
                                <div class="col-md-4">
                                    <fieldset class="form-group">
                                        <label for="transaction_id">Transaction ID</label>
                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="{{ request('transaction_id') }}" placeholder="BVN-...">
                                    </fieldset>
                                </div>
                                <div class="col-md-2">
                                    <fieldset class="form-group">
                                        <label for="from">From</label>
                                        <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
                                    </fieldset>
                                </div>
                                <div class="col-md-2">
                                    <fieldset class="form-group">
                                        <label for="to">To</label>
                                        <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
                                    </fieldset>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search mr-50"></i> Search debits</button>
                            <a href="{{ route('admin.bvn.verification.debits') }}" class="btn btn-light ml-50">Clear</a>
                        </form>

                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Customer</th>
                                        <th>Transaction</th>
                                        <th>Amount</th>
                                        <th>Balance movement</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                        @php
                                            $customerUser = $transaction->customer?->user;
                                            $log = $transaction->transaction_log;
                                            $logStatus = strtolower((string) ($log?->status ?? 'success'));
                                            $statusClass = in_array($logStatus, ['success', 'successful', 'approved', 'completed', 'delivered'], true) ? 'badge-light-success' : 'badge-light-warning';
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                            <td>
                                                <strong>{{ trim(($customerUser?->firstname ?? '') . ' ' . ($customerUser?->lastname ?? '')) ?: 'Customer' }}</strong><br>
                                                <small class="text-muted">{{ $customerUser?->email ?? 'No email' }}</small><br>
                                                <small class="text-muted">{{ $customerUser?->phone ?? 'No phone' }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ $log ? route('admin.single.transaction.view', $log->id) : '#' }}" target="_blank">{{ $transaction->transaction_id }}</a><br>
                                                <small class="text-muted">BVN verification fee</small>
                                            </td>
                                            <td><strong class="text-danger">-{{ $currency }}{{ number_format((float) $transaction->amount, 2) }}</strong></td>
                                            <td>
                                                <small>{{ $currency }}{{ number_format((float) $transaction->balance_before, 2) }}</small>
                                                <i class="bx bx-right-arrow-alt mx-25"></i>
                                                <small>{{ $currency }}{{ number_format((float) $transaction->balance_after, 2) }}</small>
                                            </td>
                                            <td><span class="badge {{ $statusClass }}">{{ ucfirst($logStatus) }}</span></td>
                                            <td>{{ optional($transaction->created_at)->format('M jS, Y g:iA') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">No automatic BVN debit records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($transactions->hasPages())
                        <div class="card-footer d-flex justify-content-center">
                            {{ $transactions->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
