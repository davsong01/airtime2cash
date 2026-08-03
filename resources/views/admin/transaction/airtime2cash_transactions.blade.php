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
                        <h5 class="card-title mb-2">Airtime to Cash Transactions</h5>
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
                                            class="text-success">Approved</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- <div class="d-inline-block mx-3">
                            <!-- chart-2 -->
                            <div class="d-flex mb-75 market-statistics-2">
                                <!-- chart statistics-2 -->
                                <div id="donut-success-chart"></div>
                                <!-- data-2 -->
                                <div class="statistics-data my-auto">
                                    <div class="statistics">
                                        <span class="font-medium-2 mr-50 text-bold-600">{!! getSettings()->currency. number_format($total_profit) !!}</span><br><span class="text-success">Total Profit</span>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                        <div class="d-inline-block mx-3">
                            <!-- chart-2 -->
                            <div class="d-flex mb-75 market-statistics-2">
                                <!-- chart statistics-2 -->
                                <div id="donut-warning-chart"></div>
                                <!-- data-2 -->
                                <div class="statistics-data my-auto">
                                    <div class="statistics">
                                        <s!!an
                                            class="font-medium-2 mr-50 text-bold-600">{!! number_format($totalPending) !!}</s!!an><br><span
                                            class="text-warning">Pending</span>
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
                                            class="font-medium-2 mr-50 text-bold-600">{!! number_format($failed) !!}</s!!an><br><span
                                            class="text-danger">Declined</span>
                                    </div>
                                
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.airtime.2.cash.log') }}" method="GET">
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
                                            <label for="status">Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="">Select</option>
                                                <option value="pending" {{ \Request::get('status') == 'pending' ? 'selected' : ''}}>Pending</option>
                                                            <option value="approved" {{ \Request::get('status') == 'approved' ? 'selected' : ''}}>Approved</option>
                                                            <option value="declined" {{ \Request::get('status') == 'declined' ? 'selected' : ''}}>Declined</option>
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
                            <hr>
                        </div>
                        <div class="table-responsive">
                            <form method="post">
                                <table id="table-extended-success" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Transaction</th>
                                            <th>Payment Details</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->customer->user->name }} <br>
                                                    <a target="_blank" href="{{ request()->route()->getPrefix() }}/customer/edit/{{ $transaction->customer_id }}">{{ $transaction->customer->user->email  }}</a> <br>
                                                    {{ $transaction->customer_phone }} <br>
                                                    @if($transaction->status == 'pending')
                                                    <button class="btn btn-warning btn-sm">{{ucfirst($transaction->status) }}</button>
                                                    @elseif($transaction->status == 'declined')
                                                    <button class="btn btn-danger btn-sm">{{ucfirst($transaction->status) }}</button>
                                                    @else
                                                    <button class="btn btn-success btn-sm">{{ucfirst($transaction->status) }}</button>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small>
                                                    <strong>Amount to Transfer: </strong>{!! getSettings()->currency. number_format($transaction->total_amount, 2) !!} <br>
                                                    <strong>Charge Rate: </strong>{{ $transaction->charge_rate }}% <br>
                                                    <strong>Charge Amount: </strong>{!! getSettings()->currency. number_format($transaction->amount_charged, 2) !!} <br>
                                                    <strong>Amount to Receive: </strong>{!! getSettings()->currency. number_format($transaction->amount_paid,2) !!} <br>
                                                    <strong>Date: </strong>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}

                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                    <span style="color:crimson"><strong>TransactionID: </strong> {{ $transaction->transaction_id }}</span> <br>
                                                    <span style="color:rgb(0, 145, 87)"><strong>Payment Method: </strong> {{ $transaction->payment_method }} </span>
                                                    
                                                    @if (!empty($transaction->description))
                                                        <br><span style="color:black"><strong>Description: </strong> {{ $transaction->description }}</span>
                                                    @endif
                                                    </small>
                                                </td>
                                                <td>
                                                    <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('admin.single.airtime2cash.transaction.view', $transaction->id) }}">
                                                        <i class="fa fa-eye"></i>
                                                        <span class="align-middle ml-25">View</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                    <div class="card-footer">
                        {!! $transactions->appends($_GET)->links() !!}
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@section('page-script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
        });
    </script>
@endsection
