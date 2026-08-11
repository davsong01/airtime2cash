@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')
@section('title', 'Wallet Log')
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
                            <li class="breadcrumb-item active">Wallet log</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-wallet"></i> Wallet operations</span>
                            <h2>Wallet transaction log</h2>
                            <p>Review wallet movements, trace linked transaction IDs, and keep credit and debit flows easy to audit.</p>
                        </div>
                        <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.trans') }}" class="btn btn-light"><i class="bx bx-receipt mr-50"></i> Transaction log</a>
                            <a href="{{ route('admin.earninglog') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-trending-up mr-50"></i> Earning log</a>
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-6">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-up-arrow-alt"></i></span>
                                <span class="ops-metric-label">Total credit</span>
                                <strong>{{ $currency }}{{ number_format((float) $credit, 2) }}</strong>
                                <small>Wallet credits recorded</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-6">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-danger"><i class="bx bx-down-arrow-alt"></i></span>
                                <span class="ops-metric-label">Total debit</span>
                                <strong>{{ $currency }}{{ number_format((float) $debit, 2) }}</strong>
                                <small>Wallet debits recorded</small>
                            </div>
                        </div>
                    </div>
                </section>

            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="ops-section-kicker">Wallet directory</span>
                            <h5 class="card-title mb-0">{{ number_format($transactions->total()) }} matching entries</h5>
                        </div>
                        <span class="badge badge-light-primary px-1 py-50">Latest first</span>
                    </div>
                    <div class="card-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.walletlog') }}" method="GET">
                                <div class="row">
                                    <div class="col-md-4">
                                        <fieldset class="form-group">
                                            <label for="email">Customer Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter customer email address" value="{{ \Request::get('email')}}">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-4">
                                        <fieldset class="form-group">
                                            <label for="transaction_id">Transaction ID</label>
                                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter transaction ID" value="{{ \Request::get('transaction_id')}}">
                                        </fieldset>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <fieldset class="form-group">
                                            <label for="type">Type</label>
                                            <select class="form-control" name="type" id="type">
                                                <option value="">Select</option>
                                                <option value="credit" {{ \Request::get('type') == 'credit' ? 'selected' : ''}}>Credit</option>
                                                <option value="debit" {{ \Request::get('type') == 'debit' ? 'selected' : ''}}>Debit</option>
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-4">
                                        <fieldset class="form-group">
                                            <label for="from">From</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('from')}}" name="from">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-4">
                                        <fieldset class="form-group">
                                            <label for="to">To</label>
                                            <input type="date" class="form-control" value="{{ \Request::get('to')}}" name="to">
                                        </fieldset>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="submit" class="form-control btn btn-primary mt-2" value="Search">
                                    </div>
                                </div>
                            </form>
                            <hr>
                        </div>
                        <div class="table-responsive">
                            <form method="post">
                                <table id="table-extended-success" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Customer</th>
                                            <th>Transaction ID</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                                <td>{{ $transaction->customer->user->name }} <br>
                                                    <a href="">{{ $transaction->customer->user->email }}</a> <br>
                                                    {{ $transaction->customer->user->phone }}
                                                </td>
                                                <td>
                                                    @if($transaction->reason == 'Airtime2Cash Payment')
                                                    <a target="_blank" href="{{ route('admin.single.airtime2cash.transaction.view', $transaction->airtime2cash->id) }}">
                                                        {{ $transaction->transaction_id }}
                                                    </a>
                                                    @else
                                                    <a target="_blank" href="{{ $transaction->transaction_log ? route('admin.single.transaction.view', $transaction->transaction_log->id) : '#' }}">
                                                        {{ $transaction->transaction_id }}
                                                    </a>
                                                    @endif
                                                </td>
                                                <td style="color:{{ $transaction->type == 'credit' ? 'green' : 'red'}}">{{ ucfirst($transaction->type) }}</td>
                                                <td>{!! getSettings()->currency. number_format($transaction->amount) !!}</td>
                                                
                                                <td>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}</td>
                                                
                                            </tr>
                                            {{-- @dump($transaction) --}}
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                            {{-- {{ $transactions->appends($query) }} --}}
                        </div>
                    </div>
                    <div class="card-footer">
                        {!! $transactions->appends($_GET)->links() !!}
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
