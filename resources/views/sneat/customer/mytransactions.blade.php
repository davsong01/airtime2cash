@extends('sneat.layouts.app')
@section('title', 'Transaction History')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .transaction-ledger-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .64);
            border-radius: 1rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .74));
            box-shadow: 0 1.5rem 3.5rem rgba(34, 48, 62, .09), 0 .3rem .8rem rgba(34, 48, 62, .05);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            backdrop-filter: blur(18px) saturate(140%);
        }

        .transaction-ledger-card .table > :not(caption) > * > * {
            padding: 1rem 1.25rem;
        }

        .transaction-ledger-card .table thead th {
            border-bottom-width: 1px;
            color: var(--bs-secondary-color);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .055em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transaction-ledger-card .table tbody tr {
            transition: background-color .18s ease;
        }

        .transaction-ledger-card .table tbody tr:hover {
            background: rgba(var(--bs-primary-rgb), .035);
        }

        .transaction-service-mark {
            display: inline-flex;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            background: rgba(var(--bs-primary-rgb), .1);
            color: var(--bs-primary);
            box-shadow: inset 0 0 0 1px rgba(var(--bs-primary-rgb), .08);
        }

        .transaction-reference {
            display: block;
            max-width: 180px;
            overflow: hidden;
            color: var(--bs-secondary-color);
            font-family: var(--bs-font-monospace);
            font-size: .75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .transaction-amount {
            font-size: .96rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .transaction-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .68rem;
            border-radius: 100rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .transaction-status::before {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: currentColor;
            content: "";
            opacity: .72;
        }

        .transaction-mobile-item {
            padding: 1.15rem;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .transaction-mobile-item:last-child {
            border-bottom: 0;
        }

        .transaction-empty-icon {
            display: inline-flex;
            width: 64px;
            height: 64px;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb), .1);
            color: var(--bs-primary);
        }

        [data-bs-theme="dark"] .transaction-ledger-card {
            border-color: rgba(255, 255, 255, .08);
            background: linear-gradient(145deg, rgba(47, 51, 73, .88), rgba(38, 41, 60, .76));
            box-shadow: 0 1.5rem 3.5rem rgba(0, 0, 0, .26), 0 .3rem .8rem rgba(0, 0, 0, .14);
        }

        @media (max-width: 575.98px) {
            .transaction-filter-actions .btn {
                flex: 1 1 0;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $activeFilters = collect([
            request('service'),
            request('reason'),
            request('transaction_id'),
            request('status'),
            request('unique_element'),
            request('from'),
            request('to'),
        ])->filter(fn ($value) => filled($value))->count();
        $currency = getSettings()['currency'];
    @endphp

    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Activity',
        'title' => 'Transaction History',
        'subtitle' => 'Track purchases, wallet activity, and account transactions in one place.',
    ])

    @include('sneat.layouts.alerts')

    <div class="card customer-form-card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-filter-alt fs-4"></i></span>
                <div>
                    <h5 class="mb-1">Find a transaction</h5>
                    <small class="text-muted">Filter by service, reference, recipient, status, or date.</small>
                </div>
            </div>
            @if($activeFilters > 0)
                <span class="badge bg-label-primary">{{ $activeFilters }} {{ Str::plural('filter', $activeFilters) }} active</span>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ route('customer.transaction.history') }}" method="GET" class="customer-modern-form">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label for="service" class="form-label">Service</label>
                        <select class="form-select modern-select2" id="service" name="service" data-placeholder="All services">
                            <option value="">All services</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ request('service') == $product->id ? 'selected' : '' }}>{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="reason" class="form-label">Purpose</label>
                        <select class="form-select" id="reason" name="reason">
                            <option value="">All purposes</option>
                            <option value="WALLET-FUNDING" {{ request('reason') === 'WALLET-FUNDING' ? 'selected' : '' }}>Wallet Funding</option>
                            <option value="Product Purchase" {{ request('reason') === 'Product Purchase' ? 'selected' : '' }}>Product Purchase</option>
                            <option value="REFERRAL-WALLET" {{ request('reason') === 'REFERRAL-WALLET' ? 'selected' : '' }}>Referral Earnings</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="transaction_id" class="form-label">Transaction ID</label>
                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="{{ request('transaction_id') }}" placeholder="Enter exact ID">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All statuses</option>
                            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="unique_element" class="form-label">Recipient / biller</label>
                        <input type="text" class="form-control" id="unique_element" name="unique_element" value="{{ request('unique_element') }}" placeholder="Phone, meter, smartcard...">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="from" class="form-label">From</label>
                        <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="to" class="form-label">To</label>
                        <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
                    </div>
                    <div class="col-md-12 col-xl-4 d-flex align-items-end">
                        <div class="transaction-filter-actions d-flex gap-2 w-100">
                            <button class="btn btn-primary customer-filter-submit flex-grow-1" type="submit">
                                <i class="bx bx-search me-1"></i> Apply filters
                            </button>
                            @if($activeFilters > 0)
                                <a href="{{ route('customer.transaction.history') }}" class="btn btn-label-secondary customer-filter-submit">
                                    <i class="bx bx-reset me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card transaction-ledger-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4">
            <div>
                <h5 class="mb-1">Your transactions</h5>
                <small class="text-muted">
                    @if($transactions->total())
                        Showing {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} of {{ number_format($transactions->total()) }}
                    @else
                        No matching activity
                    @endif
                </small>
            </div>
            <span class="avatar-initial rounded bg-label-primary p-2"><i class="bx bx-receipt fs-5"></i></span>
        </div>

        @if($transactions->count())
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Recipient</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            @php
                                $serviceName = in_array($transaction->reason, ['LEVEL-UPGRADE', 'WALLET-FUNDING', 'ADMIN-DEBIT', 'ADMIN-CREDIT'], true)
                                    ? str($transaction->reason)->replace('-', ' ')->title()
                                    : ($transaction->product->display_name ?? $transaction->product->name ?? 'Transaction');
                                $variationName = $transaction?->variation?->system_name;
                                $status = strtolower($transaction->status ?? 'pending');
                                $statusColor = match ($status) {
                                    'failed' => 'danger',
                                    'delivered', 'successful', 'success', 'completed' => 'success',
                                    'pending', 'initiated', 'processing' => 'warning',
                                    'refunded', 'reversed' => 'info',
                                    default => 'secondary',
                                };
                                $statusLabel = filled($transaction->descr) ? $transaction->descr : $status;
                                $isDebit = $transaction->type === 'debit';
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="transaction-service-mark"><i class="bx bx-transfer fs-5"></i></span>
                                        <div class="min-w-0">
                                            <h6 class="mb-1">{{ $serviceName }}</h6>
                                            @if($variationName)
                                                <small class="d-block text-muted mb-1">{{ $variationName }}</small>
                                            @endif
                                            <span class="transaction-reference" title="{{ $transaction->transaction_id }}">{{ $transaction->transaction_id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block fw-medium">{{ $transaction->unique_element ?: 'Not provided' }}</span>
                                    @if($transaction->payment_method)
                                        <small class="text-muted">{{ str($transaction->payment_method)->replace('-', ' ')->title() }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="transaction-amount text-{{ $isDebit ? 'danger' : 'success' }}">
                                        {{ $isDebit ? '-' : '+' }}{!! $currency !!}{{ number_format($transaction->total_amount, 2) }}
                                    </span>
                                    <small class="d-block text-muted">{{ $isDebit ? 'Debit' : 'Credit' }}</small>
                                </td>
                                <td><span class="transaction-status bg-label-{{ $statusColor }}">{{ str($statusLabel)->title() }}</span></td>
                                <td>
                                    <span class="d-block fw-medium">{{ $transaction->created_at->format('M j, Y') }}</span>
                                    <small class="text-muted">{{ $transaction->created_at->format('g:i A') }}</small>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a target="_blank" rel="noopener" href="{{ route('transaction.status', $transaction->transaction_id) }}" class="btn btn-sm btn-icon btn-label-primary" title="View transaction" aria-label="View transaction">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    @if(!in_array($transaction->reason, ['LEVEL-UPGRADE', 'WALLET-FUNDING'], true) && $status !== 'failed')
                                        <a target="_blank" rel="noopener" href="{{ route('transaction.receipt.download', $transaction->id) }}" class="btn btn-sm btn-icon btn-label-secondary ms-1" title="Download receipt" aria-label="Download receipt">
                                            <i class="bx bx-download"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none">
                @foreach ($transactions as $transaction)
                    @php
                        $serviceName = in_array($transaction->reason, ['LEVEL-UPGRADE', 'WALLET-FUNDING', 'ADMIN-DEBIT', 'ADMIN-CREDIT'], true)
                            ? str($transaction->reason)->replace('-', ' ')->title()
                            : ($transaction->product->display_name ?? $transaction->product->name ?? 'Transaction');
                        $status = strtolower($transaction->status ?? 'pending');
                        $statusColor = match ($status) {
                            'failed' => 'danger',
                            'delivered', 'successful', 'success', 'completed' => 'success',
                            'pending', 'initiated', 'processing' => 'warning',
                            'refunded', 'reversed' => 'info',
                            default => 'secondary',
                        };
                        $statusLabel = filled($transaction->descr) ? $transaction->descr : $status;
                        $isDebit = $transaction->type === 'debit';
                    @endphp
                    <article class="transaction-mobile-item">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="transaction-service-mark"><i class="bx bx-transfer fs-5"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h6 class="mb-1 text-truncate">{{ $serviceName }}</h6>
                                        <span class="transaction-reference" title="{{ $transaction->transaction_id }}">{{ $transaction->transaction_id }}</span>
                                    </div>
                                    <span class="transaction-amount text-{{ $isDebit ? 'danger' : 'success' }}">
                                        {{ $isDebit ? '-' : '+' }}{!! $currency !!}{{ number_format($transaction->total_amount, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="transaction-status bg-label-{{ $statusColor }}">{{ str($statusLabel)->title() }}</span>
                            <small class="text-muted">{{ $transaction->created_at->format('M j, Y') }} at {{ $transaction->created_at->format('g:i A') }}</small>
                        </div>
                        @if($transaction->unique_element)
                            <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                                <i class="bx bx-user-pin"></i>
                                <span class="text-truncate">{{ $transaction->unique_element }}</span>
                            </div>
                        @endif
                        <div class="d-flex gap-2">
                            <a target="_blank" rel="noopener" href="{{ route('transaction.status', $transaction->transaction_id) }}" class="btn btn-sm btn-label-primary flex-grow-1">
                                <i class="bx bx-show me-1"></i> View details
                            </a>
                            @if(!in_array($transaction->reason, ['LEVEL-UPGRADE', 'WALLET-FUNDING'], true) && $status !== 'failed')
                                <a target="_blank" rel="noopener" href="{{ route('transaction.receipt.download', $transaction->id) }}" class="btn btn-sm btn-label-secondary flex-grow-1">
                                    <i class="bx bx-download me-1"></i> Receipt
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="card-body py-5 text-center">
                <span class="transaction-empty-icon mb-3"><i class="bx bx-receipt fs-2"></i></span>
                <h5 class="mb-2">No transactions found</h5>
                <p class="text-muted mb-3">Try adjusting your filters or return to the dashboard to start a transaction.</p>
                @if($activeFilters > 0)
                    <a href="{{ route('customer.transaction.history') }}" class="btn btn-label-primary"><i class="bx bx-reset me-1"></i> Clear filters</a>
                @endif
            </div>
        @endif

        @if($transactions->hasPages())
            <div class="card-footer d-flex justify-content-center justify-content-md-end p-3">
                {{ $transactions->onEachSide(1)->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        $('.modern-select2').select2({
            width: '100%',
            placeholder: function () { return $(this).data('placeholder'); },
            allowClear: true
        });
    </script>
@endsection
