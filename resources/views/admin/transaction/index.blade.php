@php $currency = getSettings()?->currency ?? 'NGN'; @endphp

@extends('layouts.app')
@section('title', 'Transaction Log')
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
                            {{-- <form method="post"> --}}
                                <table id="table-extended-success" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Customer</th>
                                            <th>Payment Details</th>
                                            <th>Transaction Details</th>
                                            <th>Unique Element</th>
                                            @if(hasAccess('admin.single.transaction.view'))
                                            <th>Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                                                <td>
                                                    <span style="color:crimson"><strong>TransactionID: </strong> <br>{{ $transaction->transaction_id }}</span> <br>
                                                    <span style="color:rgb(27, 20, 220)"><strong>Request ID: </strong> <br>{{ $transaction->reference_id }}</span> <br><br>
                                                    {{ $transaction->customer_name }} <br>
                                                    <a href="">{{ $transaction->customer_email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                     {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }} <br>
                                                    @php
                                                        $statusButtonClass = match ($transaction->status) {
                                                            'success', 'delivered' => 'btn-success',
                                                            'pending' => 'btn-warning',
                                                            'failed' => 'btn-danger',
                                                            default => 'btn-secondary',
                                                        };
                                                    @endphp
                                                    <button class="btn {{ $statusButtonClass }} btn-sm" readonly>{{ ucfirst($transaction->status) }}</button>
                                                    
                                                   
                                                </td>
                                                <td>
                                                    <small>
                                                    <strong>Amount: </strong>{!! getSettings()->currency. number_format((float) $transaction->amount, 2) !!} <br>
                                                    <strong>Charge: </strong>{!! getSettings()->currency. number_format((float) $transaction->provider_charge, 2) !!} <br>
                                                    <strong>Total Amount: </strong>{!! getSettings()->currency. number_format((float) $transaction->total_amount,2) !!} <br>
                                                    <strong>Initial Balance: </strong>{!! getSettings()->currency. number_format((float) $transaction->balance_before, 2) !!} <br>
                                                    <strong>Final Balance: </strong>{!! getSettings()->currency. number_format((float) $transaction->balance_after, 2) !!} <br>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                    <strong>Product: </strong>{{ $transaction->product_name }} <br>
                                                    <strong>Category: </strong>{{ $transaction->category->name ?? null}} <br>
                                                    @if($transaction->variation)
                                                    <strong>Variation: </strong>{{ $transaction->variation->system_name ?? 'null'}} <br>
                                                    @endif
                                                    @if(!empty($transaction->api))
                                                    <strong>Provider: </strong>{{ $transaction->api->name }} <br>
                                                    @endif
                                                    <strong>Convenience: </strong>{!! getSettings()->currency. number_format((float) $transaction->provider_charge, 2) !!} <br>
                                                    <strong>Discount: </strong>{!! getSettings()->currency. number_format((float) $transaction->discount, 2) !!} <br>
                                                

                                                    </small>
                                                </td>
                                                <td>{{ $transaction->unique_element }}</td>

                                                @if(hasAccess('admin.single.transaction.view'))
                                                <td>
                                                    @if($transaction->reason == 'Airtime2Cash Payment')
                                                    <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('admin.single.airtime2cash.transaction.view', $transaction->airtime2cash->id) }}">
                                                        <i class="fa fa-eye"></i><span class="align-middle ml-25">View</span>
                                                    </a>
                                                    @else
                                                    <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('admin.single.transaction.view', $transaction->id) }}">
                                                        <i class="fa fa-eye"></i><span class="align-middle ml-25">View</span>
                                                    </a>
                                                    @endif
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                            {{ $transactions->appends($query) }}
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
