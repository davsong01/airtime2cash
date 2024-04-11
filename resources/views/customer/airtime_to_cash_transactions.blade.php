<?php 
use App\Models\Airtime2CashTransactions;
?>
@extends('layouts.app')
@section('title', 'Transactions History')

@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
    .title{
        color:black;
    }
</style>
@endsection
@section('content')
<!-- Content wrapper -->
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="card-title">
            Airtime to cash Transactions History
        </div>
        <div class="card-header">
            <h5 class="card-title mb-2">Airtime to Cash Transactions</h5>
            <div class="d-inline-block">
                <!-- chart-1   -->
                <div class="d-flex mb-75 market-statistics-2">
                    <!-- chart statistics-2 -->
                    <div id="donut-warning-chart"></div>
                    <!-- data-2 -->
                    <div class="statistics-data my-auto">
                        <div class="statistics">
                            <span class="font-medium-2 mr-50 text-bold-600">{{Airtime2CashTransactions::where(['customer_id' => auth()->user()->customer->id, 'type' => 'credit', 'status' => 'pending'])->count()}}</span><br><span class="text-warning">Pending Transaction</span>
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
                            <span class="font-medium-2 mr-50 text-bold-600">{{Airtime2CashTransactions::where(['customer_id' => auth()->user()->customer->id, 'type' => 'credit', 'status' => 'declined'])->count()}}</span><br><span
                                class="text-danger">Declined Transaction</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <div class="row">
                <!-- Marketing Campaigns Starts -->
                <div class="col-xl-12 col-12 dashboard-marketing-campaign">
                    <div class="card marketing-campaigns">
                        <div class="card-content">
                            <div class="card-body pb-0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <form action="{{ route('customer.airtime2cash.transaction.history') }}" method="GET">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <fieldset class="form-group">
                                                        <label for="product_id">Product</label>
                                                        <select class="form-control" name="product_id" id="product_id">
                                                            <option value="">Select</option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}" {{ \Request::get('product_id') == $product->id ? 'selected' : ''}}>{{ $product->name }}</option>
                                                            @endforeach
                                                        </select>
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
                                                        <label for="status">Status</label>
                                                        <select class="form-control" name="status" id="status">
                                                            <option value="">Select</option>
                                                            <option value="pending" {{ \Request::get('status') == 'pending' ? 'selected' : ''}}>Pending</option>
                                                            <option value="delivered" {{ \Request::get('status') == 'delivered' ? 'selected' : ''}}>Delivered</option>
                                                            <option value="declined" {{ \Request::get('status') == 'declined' ? 'selected' : ''}}>Declined</option>
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
                                                    <label for="to"></label>
                                                    <input type="submit" class="form-control btn btn-primary" value="Search">
                                                </div>
                                            </div>
                                        </form>
                                        <hr>
                                    </div>
                                    @foreach ($transactions as $transaction)
                                    <div class="col-md-6" style="box-shadow: rgba(0, 0, 0, 0.15) 1.95px 1.95px 2.6px;padding-top:10px;padding-bottom: 10px;">
                                        <div class="d-inline-block">
                                            <!-- chart-1   -->
                                            <div class="d-flex market-statistics-1" style="position: relative;">
                                                <!-- chart-statistics-1 -->

                                                <!-- data -->
                                                <div class="statistics-data my-auto">
                                                    <div class="statistics">
                                                        <span class="title">Product</span> <br>
                                                        <small>
                                                            <span class="mr-50 text-bold-200">
                                                                <strong>{{ $transaction->product->name ?? 'NOT SET'}}</strong>
                                                                (@if($transaction->status == 'declined')
                                                                    <span class="text-danger">{{ ucfirst($transaction->status) }}</span>
                                                                @elseif($transaction->status == 'pending')
                                                                    <span class="text-warning">{{ ucfirst($transaction->status) }}</span>
                                                                @else
                                                                    <span class="text-success">{{ ucfirst($transaction->descr) }}</span>
                                                                @endif)
                                                            </span> 
                                                        </small><br>
                                                        {{-- {{dd($transaction)}} --}}
                                                        <span class="title">Amount To Transfer</span>: 
                                                        <small>{!! getSettings()['currency']!!}{{ number_format($transaction->total_amount, 2) }}
                                                        </small> <br>
                                                        <span class="title">Amount Charged</span>: 
                                                        <small>{!! getSettings()['currency']!!}{{ number_format($transaction->amount_charged, 2) }}
                                                        </small> <br>
                                                        <span class="title">Charge Rate</span>: 
                                                        <small>{{ number_format($transaction->charge_rate) }}%
                                                        </small> <br>
                                                        <span class="title">Amount to Receive</span>: 
                                                        <small>{!! getSettings()['currency']!!}{{ number_format($transaction->amount_paid, 2) }}
                                                        </small> <br>
                                                        <span class="title">Transaction Id</span>: 
                                                        <small>
                                                            {{ $transaction->transaction_id }}</strong>
                                                        </small> <br>
                                                        <span class="title">Payment Method</span>: 
                                                        <small>
                                                            {{ $transaction->payment_method }}</strong>
                                                        </small> <br>
                                                        <span class="title">Date</span>: 
                                                        <small>
                                                            {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                        </small> <br>
                                                    </div>
                                                        <small class="text-muted">
                                                            <a target="_blank" href="{{ route('airtime2cash.transaction.status', $transaction->transaction_id) }}" class="btn btn-sm btn-primary glow mt-md-2 mb-1">View</a></small> <small class="text-muted">
                                                            @if(in_array($transaction->status, ['approved']))
                                                            <a target="_blank" href="{{ route('airtime2cash.transaction.receipt.download', $transaction->id) }}" class="btn btn-sm btn-info glow mt-md-2 mb-1">Download Transaction Receipt</a>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-footer">
                                {!! $transactions->appends($_GET)->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script>
@endsection
