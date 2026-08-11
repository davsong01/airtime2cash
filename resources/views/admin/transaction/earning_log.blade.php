@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')
@section('title', 'Earning Log')
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
                            <li class="breadcrumb-item active">Earning log</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-line-chart"></i> Referral operations</span>
                            <h2>Earning log</h2>
                            <p>Review referral credits and debits, then trace each earning back to the transaction that created it.</p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.trans') }}" class="btn btn-light"><i class="bx bx-receipt mr-50"></i> Transaction log</a>
                            <a href="{{ route('admin.walletfundinglog') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-wallet mr-50"></i> Wallet funding</a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-primary"><i class="bx bx-receipt"></i></span>
                                <span class="ops-metric-label">All entries</span>
                                <strong>{{ number_format((int) $total) }}</strong>
                                <small>Referral earning rows</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-trending-up"></i></span>
                                <span class="ops-metric-label">Total credit</span>
                                <strong>{{ $currency }}{{ number_format((float) $success, 2) }}</strong>
                                <small>Referral earnings credited</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-danger"><i class="bx bx-trending-down"></i></span>
                                <span class="ops-metric-label">Total debit</span>
                                <strong>{{ $currency }}{{ number_format((float) $failed, 2) }}</strong>
                                <small>Referral reversals or deductions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-info"><i class="bx bx-calculator"></i></span>
                                <span class="ops-metric-label">Net earnings</span>
                                <strong>{{ $currency }}{{ number_format((float) $success - (float) $failed, 2) }}</strong>
                                <small>Credit less debit</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card ops-panel ops-filter-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="ops-filter-icon"><i class="bx bx-filter-alt"></i></span>
                            <div><h5 class="mb-25">Find earnings</h5><small class="text-muted">Search by upline, downline, transaction, type, or date range.</small></div>
                        </div>
                        @if(request()->query())
                            <a href="{{ route('admin.earninglog') }}" class="btn btn-sm btn-light-secondary mt-1 mt-sm-0"><i class="bx bx-reset mr-25"></i> Clear filters</a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.earninglog') }}" method="GET">
                                {{-- @csrf --}}
                                <div class="row">
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="upline_email">Upline Email</label>
                                            <input type="upline_email" class="form-control" id="upline_email" name="upline_email" placeholder="Enter upline email address" value="{{ \Request::get('upline_email')}}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-3">
                                        <fieldset class="form-group">
                                            <label for="downline_email">Downline Email</label>
                                            <input type="downline_email" class="form-control" id="downline_email" name="downline_email" placeholder="Enter upline email address" value="{{ \Request::get('downline_email')}}">
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
                                            <label for="type">Type</label>
                                            <select class="form-control" name="type" id="type">
                                                <option value="type">Select</option>
                                                <option value="credit" {{ \Request::get('type') == 'credit' ? 'selected' : ''}}>Credit</option>
                                                <option value="debit" {{ \Request::get('type') == 'debit' ? 'selected' : ''}}>Debit</option>
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
                        <div><span class="ops-section-kicker">Earning directory</span><h5 class="mb-0">{{ number_format($transactions->total()) }} matching entries</h5></div>
                        <span class="badge badge-light-success px-1 py-50">Latest first</span>
                    </div>
                    <div class="table-responsive">
                        <div class="table-responsive">
                            <form method="post">
                                <table id="table-extended-success" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Upline</th>
                                            <th>Downline</th>
                                            <th>Payment Details</th>
                                            <th>Type</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                                <td>{{ $transaction->customer->user->name }} <br>
                                                    <a href="{{ route('customers.edit', $transaction->customer->user->id)}}">{{ $transaction->customer->user->email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                    
                                                </td>
                                                 <td>{{ $transaction->referredCustomer->user->name }} <br>
                                                    <a href="{{ route('customers.edit', $transaction->referredCustomer->user->id)}}">{{ $transaction->referredCustomer->user->email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                    
                                                </td>
                                                 <td>
                                                    <small>
                                                    <span style="color:crimson"><strong>TransactionID: </strong> {{ $transaction->transaction_id }}</span> <br>
                                                    </small><small>
                                                    <strong>Amount: </strong><span style="color:{{$transaction->type == 'credit' ? 'green':''}}">{{ $transaction->type == 'credit' ? '+':'-' }}{!! getSettings()->currency. number_format($transaction->amount, 2) !!} </span><br>
                                                    <strong>Initial Balance: </strong>{!! getSettings()->currency. number_format($transaction->balance_before, 2) !!} <br>
                                                    <strong>Final Balance: </strong>{!! getSettings()->currency. number_format($transaction->balance_after, 2) !!} <br>
                                                    <strong>Date: </strong>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}

                                                    </small>
                                                </td>
                                                <td>
                                                    <strong>
                                                        @if($transaction->type == 'credit')
                                                        <span style="color:green">{{ucfirst($transaction->type) }}</span>
                                                        @else
                                                        <span style="color:red">{{ucfirst($transaction->type) }}</span>
                                                        @endif

                                                    </strong>
                                                </td>
                                                <td>
                                                    <a class="btn btn-primary btn-sm mr-1 mb-1 {{ $transaction->transaction ? '' : 'disabled' }}" href="{{ $transaction->transaction ? route('admin.single.transaction.view', $transaction->transaction->id) : '#' }}">
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
