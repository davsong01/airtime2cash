@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')
@section('title', 'Wallet Funding Log')
@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
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
                            <li class="breadcrumb-item active">Wallet funding log</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-wallet"></i> Funding operations</span>
                            <h2>Wallet funding log</h2>
                            <p>Track successful wallet top-ups, monitor failed attempts, and keep an eye on payments that need manual attention.</p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.reserved.accounts') }}" class="btn btn-light"><i class="bx bx-building-house mr-50"></i> Reserved accounts</a>
                            <a href="{{ route('api.index') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-cloud mr-50"></i> Provider monitor</a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-primary"><i class="bx bx-receipt"></i></span>
                                <span class="ops-metric-label">All requests</span>
                                <strong>{{ number_format((int) $total) }}</strong>
                                <small>Wallet funding transactions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-check-circle"></i></span>
                                <span class="ops-metric-label">Successful value</span>
                                <strong>{{ $currency }}{{ number_format((float) $success, 2) }}</strong>
                                <small>Delivered wallet funding amount</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-warning"><i class="bx bx-time-five"></i></span>
                                <span class="ops-metric-label">Needs attention</span>
                                <strong>{{ $currency }}{{ number_format((float) $attention_required, 2) }}</strong>
                                <small>Transactions waiting for manual review</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-danger"><i class="bx bx-x-circle"></i></span>
                                <span class="ops-metric-label">Failed value</span>
                                <strong>{{ $currency }}{{ number_format((float) $failed, 2) }}</strong>
                                <small>Wallet funding attempts that did not complete</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card ops-panel ops-filter-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="ops-filter-icon"><i class="bx bx-filter-alt"></i></span>
                            <div><h5 class="mb-25">Find wallet funding</h5><small class="text-muted">Search by customer, provider, status, or date range.</small></div>
                        </div>
                        @if(request()->query())
                            <a href="{{ route('admin.walletfundinglog') }}" class="btn btn-sm btn-light-secondary mt-1 mt-sm-0"><i class="bx bx-reset mr-25"></i> Clear filters</a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.walletfundinglog') }}" method="GET">
                                {{-- @csrf --}}
                                <div class="row">
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="email">Customer Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter customer email address" value="{{ \Request::get('email')}}">
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
                                            <label for="payment_provider">Payment Gateway</label>
                                            <select class="form-control" name="payment_provider" id="payment_provider">
                                                <option value="">Select</option>
                                                @foreach ($providers as $provider)
                                                    <option value="{{ $provider->id }}" {{ \Request::get('payment_provider') == $provider->id ? 'selected' : ''}}>{{ $provider->name }}</option>
                                                @endforeach
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="">Select</option>
                                                <option value="delivered" {{ \Request::get('status') == 'delivered' ? 'selected' : ''}}>Delivered</option>
                                                <option value="failed" {{ \Request::get('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="from">From</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('from')}}" name="from">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="to">To</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('to')}}" name="to">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="submit" class="form-control btn btn-primary mt-2" value="Search">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <section class="card ops-panel">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div><span class="ops-section-kicker">Transaction directory</span><h5 class="mb-0">{{ number_format($transactions->total()) }} matching wallet funding logs</h5></div>
                        <span class="badge badge-light-primary px-1 py-50">Latest first</span>
                    </div>
                    <div class="table-responsive">
                        <div class="table-responsive">
                            <form method="post">
                                <table id="table-extended-success" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Customer</th>
                                            <th>Transaction</th>
                                            <th>Payment Details</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                        
                                            <tr>
                                                <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                                <td>{{ $transaction->customer_name }} <br>
                                                    <a href="">{{ $transaction->customer_email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                @php
                                                    $status = strtolower((string) ($transaction->status ?? 'pending'));
                                                    $isSuccessful = in_array($status, ['success', 'successful', 'delivered', 'completed', 'approved'], true);
                                                @endphp
                                                @if($isSuccessful)
                                                    <button class="btn btn-primary btn-sm">{{ ucfirst($transaction->status) }}</button>
                                                @elseif($status === 'attention-required')
                                                    <button class="btn btn-warning btn-sm">{{ ucfirst(str_replace('-', ' ', $transaction->status)) }}</button>
                                                @else
                                                    <button class="btn btn-danger btn-sm">{{ ucfirst($transaction->status) }}</button>
                                                @endif
                                            </td>
                                                <td>
                                                    <small>
                                                    <strong>Account Number: </strong>{{ $transaction->account_number }} <br>
                                                    <strong>Amount: </strong>{!! getSettings()->currency. number_format((float) $transaction->amount, 2) !!} <br>
                                                    <strong>Charge: </strong>{!! getSettings()->currency. number_format((float) $transaction->provider_charge, 2) !!} <br>
                                                    <strong>Total Amount: </strong>{!! getSettings()->currency. number_format((float) $transaction->total_amount,2) !!} <br>
                                                    <strong>Initial Balance: </strong>{!! getSettings()->currency. number_format((float) $transaction->balance_before, 2) !!} <br>
                                                    <strong>Final Balance: </strong>{!! getSettings()->currency. number_format((float) $transaction->balance_after, 2) !!} <br>
                                                    <strong>Provider: </strong>{{ $transaction->api?->name ?? 'Unknown API' }} <br>
                                                    <strong>Date: </strong>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}

                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                    <span style="color:crimson"><strong>TransactionID: </strong> {{ $transaction->transaction_id }}</span> <br>
                                                    <span style="color:rgb(27, 20, 220)"><strong>Request ID: </strong>{{ $transaction->reference_id }}</span> <br>
                                                    <span style="color:rgb(0, 145, 87)"><strong>Payment Method: </strong> {{ $transaction->payment_method }}
                                                    </small>

                                                </td>
                                            
                                                <td>
                                                    <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('admin.single.transaction.view', $transaction->id) }}">
                                                        <i class="fa fa-eye"></i>
                                                        <span class="align-middle ml-25">View</span>
                                                    </a>

                                                </td>
                                            </tr>
                                            {{-- @dump($transaction) --}}
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                            {{-- {{ $transactions->appends($query) }} --}}
                        </div>
                    </div>
                     <div class="card-footer d-flex justify-content-end">
                        {!! $transactions->appends($_GET)->links() !!}
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
