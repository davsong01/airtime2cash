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
            Transactions History
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
                                        <form action="{{ route('customer.transaction.history') }}" method="GET">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-4">
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
                                                        <label for="reason">Transaction Purpose</label>
                                                        <select class="form-control" name="reason" id="reason">
                                                            <option value="">Select</option>
                                                            <option value="WALLET-FUNDING" {{ old('reason') == 'WALLET-FUNDING' ? 'selected' : ''}}>Wallet Funding</option>
                                                            <option value="Product Purchase" {{ old('reason') == 'Product Purchase' ? 'selected' : ''}}>Product Purchase</option>
                                                            <option value="REFERRAL-WALLET" {{ old('reason') == 'REFERRAL-WALLET' ? 'selected' : ''}}>Referral Earnings</option>
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="transaction_id">Transaction ID</label>
                                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter transaction ID" value="{{ \Request::get('transaction_id')}}">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-2">
                                                    <fieldset class="form-group">
                                                        <label for="status">Status</label>
                                                        <select class="form-control" name="status" id="status">
                                                            <option value="">Select</option>
                                                            <option value="delivered" {{ \Request::get('status') == 'delivered' ? 'selected' : ''}}>Delivered</option>
                                                            <option value="failed" {{ \Request::get('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                                                        </select>
                                                    </fieldset>
                                                   
                                                </div>
                                                <div class="col-md-4">
                                                    <fieldset class="form-group">
                                                        <label for="unique_element">Unique Element</label>
                                                        <input type="text" class="form-control" id="unique_element" name="unique_element" placeholder="Enter unique element" value="{{ \Request::get('unique_element') }}">
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

                                                <div class="col-md-2">
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
                                                        <span class="title">Service</span> <br>
                                                        <small>
                                                            <span class="mr-50 text-bold-200">
                                                               
                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                    {{ ucfirst(str_replace("-"," ",$transaction->reason))}}
                                                                @else
                                                                <strong>{{ $transaction->product->name ?? 'NOT SET'}}</strong>
                                                                    @if($transaction?->variation?->system_name) {{ " | ". $transaction?->variation?->system_name }} 
                                                                    @endif
                                                                @endif
                                                                (@if($transaction->status == 'failed')
                                                                    <span class="text-danger">{{ ucfirst($transaction->status) }}</span>
                                                                @elseif($transaction->status == 'initiated')
                                                                    <span class="text-warning">{{ ucfirst($transaction->status) }}</span>
                                                                @else
                                                                    <span class="text-success">{{ ucfirst($transaction->descr) }}</span>
                                                                @endif)
                                                            </span> 
                                                        </small><br>
                                                        <span class="title">Biller</span> <br>
                                                        <small>
                                                            {{ $transaction->unique_element }}</strong>
                                                        </small> <br>
                                                       
                                                        <span class="title">Amount</span> <br>
                                                        <small class="{{$transaction->type == 'debit' ? 'red' : 'green' }}">{{$transaction->type == 'debit' ? '- ' : '+ '}}{!! getSettings()['currency']!!}{{ number_format($transaction->total_amount, 2) }}
                                                        {{$transaction->type == 'debit' ? '(Debit)' : '(Credit)' }}
                                                        </small> <br>
                                                        <span class="title">Transaction Id</span> <br>
                                                        <small>
                                                            {{ $transaction->transaction_id }}</strong>
                                                        </small> <br>
                                                        <span class="title">Payment Method</span> <br>
                                                        <small>
                                                            {{ $transaction->payment_method }}</strong>
                                                        </small> <br>
                                                        <span class="title">Date</span> <br>
                                                        <small>
                                                            {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                        </small> <br>

                                                    </div>

                                                    <div class="statistics-date">
                                                        <small class="text-muted">
                                                            <a target="_blank" href="{{ route('transaction.status', $transaction->transaction_id) }}" class="btn btn-sm btn-primary glow mt-md-2 mb-1">View</a></small> <small class="text-muted">
                                                            @if(!in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING']) && !in_array($transaction->status, ['failed']))
                                                            <a target="_blank" href="{{ route('transaction.receipt.download', $transaction->id) }}" class="btn btn-sm btn-info glow mt-md-2 mb-1">Download Transaction Receipt</a>
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