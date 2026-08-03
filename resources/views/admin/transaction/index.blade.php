@extends('layouts.app')
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <!-- head -->
                        <h5 class="card-title mb-2">Transactions</h5>
                        <div class="d-inline-block">
                            <!-- chart-1   -->
                            <div class="d-flex market-statistics-1">
                                <!-- chart-statistics-1 -->
                                <div id="donut-success-chart"></div>
                                <!-- data -->
                                <div class="statistics-data my-auto">
                                    <div class="statistics">
                                        <span
                                            class="font-medium-2 mr-50 text-bold-600">{!! getSettings()->currency. number_format($success) !!}</span>
                                            <br>
                                            <span
                                            class="text-success">Delivered</span>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                        <div class="d-inline-block mx-3">
                            <!-- chart-2 -->
                            <div class="d-flex mb-75 market-statistics-2">
                                <!-- chart statistics-2 -->
                                <div id="donut-danger-chart"></div>
                                <!-- data-2 -->
                                <div class="statistics-data my-auto">
                                    <div class="statistics">
                                        <span
                                            class="font-medium-2 mr-50 text-bold-600">{!! getSettings()->currency. number_format($attention_required) !!}</span><br><span
                                            class="text-warning">Attention Required</span>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="d-inline-block mx-3">
                            <!-- chart-2 -->
                            <div class="d-flex mb-75 market-statistics-2">
                                <!-- chart statistics-2 -->
                                <div id="donut-danger-chart"></div>
                                <!-- data-2 -->
                                <div class="statistics-data my-auto">
                                    <div class="statistics">
                                        <s!!an
                                            class="font-medium-2 mr-50 text-bold-600">{!! getSettings()->currency. number_format($failed) !!}</s!!an><br><span
                                            class="text-danger">Failed</span>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
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
                                                <td>
                                                    <span style="color:crimson"><strong>TransactionID: </strong> <br>{{ $transaction->transaction_id }}</span> <br>
                                                    <span style="color:rgb(27, 20, 220)"><strong>Request ID: </strong> <br>{{ $transaction->reference_id }}</span> <br><br>
                                                    {{ $transaction->customer_name }} <br>
                                                    <a href="">{{ $transaction->customer_email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                     {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }} <br>
                                                    @if($transaction->status == 'success' || $transaction->status == 'delivered')
                                                    <button class="btn btn-success btn-sm" readonly>{{ucfirst($transaction->status) }}</button>
                                                    @else
                                                    <button class="btn btn-danger btn-sm" readonly>{{ucfirst($transaction->status) }}</button>
                                                    @endif 
                                                    
                                                   
                                                </td>
                                                <td>
                                                    <small>
                                                    <strong>Amount: </strong>{!! getSettings()->currency. number_format($transaction->amount, 2) !!} <br>
                                                    <strong>Charge: </strong>{!! getSettings()->currency. number_format($transaction->provider_charge, 2) !!} <br>
                                                    <strong>Total Amount: </strong>{!! getSettings()->currency. number_format($transaction->total_amount,2) !!} <br>
                                                    <strong>Initial Balance: </strong>{!! getSettings()->currency. number_format($transaction->balance_before, 2) !!} <br>
                                                    <strong>Final Balance: </strong>{!! getSettings()->currency. number_format($transaction->balance_after, 2) !!} <br>
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
                                                    <strong>Convenience: </strong>{!! getSettings()->currency. number_format($transaction->provider_charge, 2) !!} <br>
                                                    <strong>Discount: </strong>{!! getSettings()->currency. number_format($transaction->discount, 2) !!} <br>
                                                

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
