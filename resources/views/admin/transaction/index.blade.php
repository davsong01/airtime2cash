@php $currency = getSettings()?->currency ?? 'NGN'; @endphp
@php
    $transactionModeLabel = static function ($transaction) {
        $productType = strtolower((string) ($transaction->product?->type ?? ''));

        if (! in_array($productType, ['wallet2bank', 'airtime2cash'], true)) {
            return null;
        }

        return match (strtolower((string) ($transaction->transfer_mode ?? ''))) {
            'manual' => 'Manual',
            'auto_share' => 'Auto',
            default => null,
        };
    };

@endphp

@extends('layouts.app')
@section('title', 'Transaction Log')
@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
    <style>
        .transaction-directory-table thead th {
            border-top: 0;
            color: #697386;
            font-size: .68rem;
            letter-spacing: .055em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transaction-directory-table tbody td {
            padding-top: .85rem;
            padding-bottom: .85rem;
            vertical-align: middle;
        }

        .transaction-directory-table tbody tr {
            transition: background-color .18s ease;
        }

        .transaction-directory-table tbody tr:hover {
            background: #f8faff;
        }

        .transaction-row-number {
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

        .transaction-customer-cell {
            min-width: 220px;
        }

        .transaction-customer {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
        }

        .transaction-customer-avatar {
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
            font-size: .78rem;
            font-weight: 700;
        }

        .transaction-customer-name {
            color: #202a3b;
            font-size: .84rem;
            font-weight: 700;
        }

        .transaction-meta-line {
            display: flex;
            align-items: center;
            gap: .3rem;
            color: #7b8495;
            font-size: .7rem;
            line-height: 1.55;
        }

        .transaction-meta-line i {
            color: #8993a5;
        }

        .transaction-reference-cell {
            min-width: 205px;
        }

        .transaction-reference-primary {
            display: block;
            color: #3347c8;
            font-size: .76rem;
            font-weight: 700;
            word-break: break-all;
        }

        .transaction-reference-secondary {
            display: block;
            margin-top: .2rem;
            color: #8a93a3;
            font-size: .68rem;
            word-break: break-all;
        }

        .transaction-status-chip,
        .transaction-mode-chip,
        .transaction-identifier-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: .3rem .55rem;
            font-size: .68rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .transaction-status-chip.is-success {
            color: #168554;
            background: #e9f8f1;
            border-color: #c8eddd;
        }

        .transaction-status-chip.is-warning {
            color: #a46500;
            background: #fff7e3;
            border-color: #f6dfaa;
        }

        .transaction-status-chip.is-danger {
            color: #b33a3a;
            background: #fff1f1;
            border-color: #f4d0d0;
        }

        .transaction-status-chip.is-neutral,
        .transaction-mode-chip,
        .transaction-identifier-chip {
            color: #606b7d;
            background: #f3f5f8;
            border-color: #e2e6eb;
        }

        .transaction-date {
            display: block;
            margin-top: .4rem;
            color: #8a93a3;
            font-size: .67rem;
        }

        .transaction-financial-cell {
            min-width: 175px;
        }

        .transaction-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .25rem;
            border-radius: 9px;
            padding: .42rem .5rem;
            color: #3347c8;
            background: #eef1ff;
        }

        .transaction-financial-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .2rem .5rem;
            color: #727c8d;
            font-size: .68rem;
        }

        .transaction-financial-row strong,
        .transaction-total strong {
            color: inherit;
            white-space: nowrap;
        }

        .transaction-balance-flow {
            margin-top: .35rem;
            border-top: 1px solid #edf0f4;
            padding: .4rem .5rem 0;
            color: #7b8495;
            font-size: .66rem;
            white-space: nowrap;
        }

        .transaction-service-cell {
            min-width: 180px;
        }

        .transaction-service-name {
            display: block;
            color: #273247;
            font-size: .8rem;
            font-weight: 700;
        }

        .transaction-service-meta {
            display: block;
            margin-top: .18rem;
            color: #7b8495;
            font-size: .69rem;
        }

        .transaction-service-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .45rem;
        }

        .transaction-action-button {
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
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb p-0 mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Transaction log</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-receipt"></i> Transaction operations</span>
                            <h2>Transaction log</h2>
                            <p>Monitor every transaction from request through completion, and quickly isolate anything that still needs attention.</p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.walletlog') }}" class="btn btn-light"><i class="bx bx-wallet mr-50"></i> Wallet log</a>
                            <a href="{{ route('admin.airtime.2.cash.log') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-transfer-alt mr-50"></i> Airtime to cash</a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-check-circle"></i></span>
                                <span class="ops-metric-label">Delivered</span>
                                <strong>{{ $currency }}{{ number_format((float) $success, 2) }}</strong>
                                <small>Successful transaction value</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-warning"><i class="bx bx-time-five"></i></span>
                                <span class="ops-metric-label">Attention required</span>
                                <strong>{{ $currency }}{{ number_format((float) $attention_required, 2) }}</strong>
                                <small>Pending follow-up value</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-xl-4">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-danger"><i class="bx bx-x-circle"></i></span>
                                <span class="ops-metric-label">Failed</span>
                                <strong>{{ $currency }}{{ number_format((float) $failed, 2) }}</strong>
                                <small>Unsuccessful transaction value</small>
                            </div>
                        </div>
                    </div>
                </section>

            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="ops-section-kicker">Transaction directory</span>
                            <h5 class="card-title mb-0">{{ number_format($transactions->total()) }} matching entries</h5>
                        </div>
                        <span class="badge badge-light-success px-1 py-50">Latest first</span>
                    </div>
                    <div class="card-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.trans') }}" method="GET">
                                {{-- @csrf --}}
                                <div class="row">
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="email">Transaction Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter customer email address" value="{{ \Request::get('email')}}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="phone">Transaction Phone</label>
                                            <input type="phone" class="form-control" id="phone" name="phone" placeholder="Enter customer phone number" value="{{ \Request::get('phone')}}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="service">Service</label>
                                            <select class="form-control" name="service" id="service">
                                                <option value="">Select</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" {{ \Request::get('service') == $product->id ? 'selected' : ''}}>{{ $product->display_name }}</option>
                                                @endforeach
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="api">API</label>
                                            <select class="form-control" name="api" id="api">
                                                <option value="">Select</option>
                                                @foreach ($apis as $api)
                                                    <option value="{{ $api->id }}" {{ \Request::get('api') == $api->id ? 'selected' : ''}}>{{ $api->name }}</option>
                                                @endforeach
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="transaction_id">Transaction ID</label>
                                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter transaction ID" value="{{ \Request::get('transaction_id')}}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="unique_element">Unique Element</label>
                                            <input type="text" class="form-control" id="unique_element" name="unique_element" placeholder="Enter unique element" value="{{ \Request::get('unique_element') }}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="">Select</option>
                                                <option value="delivered" {{ \Request::get('status') == 'delivered' ? 'selected' : ''}}>Delivered</option>
                                                <option value="failed" {{ \Request::get('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                                                <option value="attention-required" {{ \Request::get('status') == 'attention-required' ? 'selected' : ''}}>Attention Required</option>
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-2">
                                        <fieldset class="form-group">
                                            <label for="from">From</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('from')}}" name="from">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-2">
                                        <fieldset class="form-group">
                                            <label for="to">To</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('to')}}" name="to">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="submit" class="form-control btn btn-primary mt-2" value="Search">
                                    </div>
                                </div>
                            </form>
                            <hr>
                        </div>
                        <div class="table-responsive">
                                <table id="table-extended-success" class="table mb-0 transaction-directory-table">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Customer</th>
                                            <th>Transaction</th>
                                            <th>Financials</th>
                                            <th>Service</th>
                                            <th>Identifier</th>
                                            @if(hasAccess('admin.single.transaction.view'))
                                            <th class="text-right">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            @php
                                                $customerName = trim((string) $transaction->customer_name) ?: 'Unknown customer';
                                                $status = strtolower((string) $transaction->status);
                                                $statusClass = match (true) {
                                                    in_array($status, ['success', 'successful', 'delivered', 'completed', 'approved'], true) => 'is-success',
                                                    in_array($status, ['failed', 'declined', 'rejected', 'cancelled', 'canceled'], true) => 'is-danger',
                                                    in_array($status, ['pending', 'initiated', 'attention-required'], true) => 'is-warning',
                                                    default => 'is-neutral',
                                                };
                                                $detailUrl = ($transaction->product?->type ?? null) === 'airtime2cash' && $transaction->airtime2cash
                                                    ? route('admin.single.airtime2cash.transaction.view', $transaction->airtime2cash->id)
                                                    : route('admin.single.transaction.view', $transaction->id);
                                            @endphp
                                            <tr>
                                                <td><span class="transaction-row-number">{{ $transactions->firstItem() + $loop->index }}</span></td>
                                                <td class="transaction-customer-cell">
                                                    <div class="transaction-customer">
                                                        <div class="min-width-0">
                                                            <span class="transaction-customer-name d-block text-truncate">{{ $customerName }}</span>
                                                            @if($transaction->customer?->user)
                                                                <a href="{{ route('customers.edit', $transaction->customer->user->id) }}" class="transaction-meta-line text-truncate"><i class="bx bx-envelope"></i>{{ $transaction->customer_email }}</a>
                                                            @else
                                                                <span class="transaction-meta-line text-truncate"><i class="bx bx-envelope"></i>{{ $transaction->customer_email ?: 'No email' }}</span>
                                                            @endif
                                                            <span class="transaction-meta-line"><i class="bx bx-phone"></i>{{ $transaction->customer_phone ?: 'No phone' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="transaction-reference-cell">
                                                    <a href="{{ $detailUrl }}" class="transaction-reference-primary">{{ $transaction->transaction_id }}</a>
                                                    <span class="transaction-reference-secondary">Request: {{ $transaction->reference_id ?: '—' }}</span>
                                                    <span class="transaction-status-chip {{ $statusClass }} mt-50"><i class="bx bx-circle"></i>{{ ucfirst(str_replace('-', ' ', $status)) }}</span>
                                                    <span class="transaction-date"><i class="bx bx-calendar mr-25"></i>{{ $transaction->created_at->format('M j, Y · g:i A') }}</span>
                                                </td>
                                                <td class="transaction-financial-cell">
                                                    <div class="transaction-total"><span>Total</span><strong>{{ $currency }}{{ number_format((float) $transaction->total_amount, 2) }}</strong></div>
                                                    <div class="transaction-financial-row"><span>Amount</span><strong>{{ $currency }}{{ number_format((float) $transaction->amount, 2) }}</strong></div>
                                                    <div class="transaction-financial-row"><span>Charge</span><strong>{{ $currency }}{{ number_format((float) $transaction->provider_charge, 2) }}</strong></div>
                                                    {{-- <div class="transaction-balance-flow">{{ $currency }}{{ number_format((float) $transaction->balance_before, 2) }} <i class="bx bx-right-arrow-alt mx-25"></i> {{ $currency }}{{ number_format((float) $transaction->balance_after, 2) }}</div> --}}
                                                </td>
                                                <td class="transaction-service-cell">
                                                    <span class="transaction-service-name">{{ $transaction->product_name ?: 'Unspecified service' }}</span>
                                                    <span class="transaction-service-meta">{{ $transaction->category->name ?? 'No category' }}</span>
                                                    @if($transaction->variation)
                                                        <span class="transaction-service-meta">{{ $transaction->variation->system_name ?? 'Unknown variation' }}</span>
                                                    @endif
                                                    @if(!empty($transaction->api))
                                                        <span class="transaction-service-meta"><i class="bx bx-server mr-25"></i>{{ $transaction->api->name }}</span>
                                                    @endif
                                                    <div class="transaction-service-tags">
                                                        @if($transactionModeLabel($transaction))
                                                            <span class="transaction-mode-chip"><i class="bx bx-transfer"></i>Mode: {{ $transactionModeLabel($transaction) }}</span>
                                                        @endif
                                                        @if((float) $transaction->discount > 0)
                                                            <span class="transaction-mode-chip"><i class="bx bx-purchase-tag"></i>-{{ $currency }}{{ number_format((float) $transaction->discount, 2) }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td><span class="transaction-identifier-chip"><i class="bx bx-target-lock"></i>{{ $transaction->unique_element ?: 'Not provided' }}</span></td>

                                                @if(hasAccess('admin.single.transaction.view'))
                                                <td class="text-right">
                                                    <a class="btn btn-outline-primary transaction-action-button" href="{{ $detailUrl }}" title="View transaction" aria-label="View transaction"><i class="bx bx-show"></i></a>
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @if($transactions->hasPages())
                                <div class="d-flex justify-content-center mt-2">
                                    {{ $transactions->onEachSide(1)->links('pagination::bootstrap-4') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    {{-- <script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
        });
    </script>
@endsection
