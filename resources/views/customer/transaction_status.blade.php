<?php

    if($transaction->status == 'failed'){
        $color = 'red';
    }elseif($transaction->status == 'initiated'){
        $color = '#FDAC41';
    }else {
        $color = 'green';
    }
?>
@extends('layouts.app')
@section('title', 'Transaction Completed')

@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
    .item-progress{
        overflow:auto !important
    }
    .key{
        color:#1A233A;
    }
    .trans-details{
        padding: 1.7rem 2px;
    }
</style>
@endsection
@section('content')
<!-- Content wrapper -->
 <div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <!-- Basic Inputs start -->
            <section id="basic-input">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                                <div class="content-body">
                                <!-- Nav Filled Starts -->
                                <section id="nav-filled">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="col-md-12">
                                                    <div class="card-header" style="padding:1.4rem 0.7rem">
                                                        <h4 class="card-title">Transaction Status Page</h4>

                                                        @if(in_array($transaction->user_status, ['completed','delivered','pending','attention-required','success']))
                                                            <div class="alert alert-success" role="alert" style="margin-bottom: 5px !important; margin-top:10px">
                                                                <strong>{{ strtoupper($transaction->user_status) }}</strong>
                                                            </div>
                                                        @elseif($transaction->status == 'failed')
                                                            <div class="alert alert-danger" role="alert" style="margin-bottom: 5px !important;margin-top:10px">
                                                                <strong>{{ strtoupper($transaction->user_status) }}</strong>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <div class="card content-area">
                                                        <div class="card-innr">
                                                            <div class="row">

                                                                <div class="col-md-12">
                                                                    @if(!empty($transaction->extras))
                                                                    <h3 style="color:#D50000;font-weight: bold;font-size: 28px;line-height: 28px;text-align: center;"><strong>{{ $transaction->extras }}</strong></h3>
                                                                    @endif
                                                                    <small style="display:block;font-family: Roboto;font-style: italic;font-weight: normal;font-size: 12px;line-height: 20px;color: #575A5F;text-align:center;">{{ $transaction->instruction }}</small>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-1">
                                                                    @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                    <img id="product-image" width="60" height="60" src="{{ asset('site/upgrade.jpg') }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                    @else 
                                                                    <img id="product-image" width="60" height="60" src="{{ asset($transaction->product->image) }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-4">
                                                                   
                                                                    <span class="data-details-title" style="color:#174159;"><h3 style="color:#174159;"><strong style="line-height: unset;font-size:17px;">
                                                                        @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                            {{ ucfirst(str_replace("-"," ",$transaction->reason))}}
                                                                        @else
                                                                        {{ $transaction->product->name }}@if(!empty($transaction->variation->system_name)) | {{$transaction->variation->system_name}} @endif 
                                                                        @endif
                                                                    </strong></h3></span>
                                                                   
                                                                    <span class="data-details-info">{{ $transaction->unique_element }} </span> <br/>
                                                                    <span class="data-details-info"><strong style="color:#174159;">Amount: {!! getSettings()->currency !!}{{ number_format($transaction->amount, 2) }}</strong></span> <br>
                                                                    @if(!in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING']) && !in_array($transaction->status, ['failed']))
                                                                    <a href="{{ route('transaction.receipt.download', $transaction->id)}}" target="_blank" class="btn btn-primary mt-1 mb-1" style="color:#fff;width:100%;"><i class="fa fa-download"></i>Download Transaction Receipt</a>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-3">
                                                                    Description <br>
                                                                    <span style="color:{{ $color }}">{{ ucfirst($transaction->descr) }}</span><br><br>
                                                                    {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong>Transaction ID</strong> <br>
                                                                    <span >{{ ucfirst($transaction->transaction_id) }}</span> <br>
                                                                    <strong>Reference ID</strong> <br>
                                                                    <span >{{ ucfirst($transaction->reference_id) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="row" >
                                                                <div class="col-md-12">
                                                                    <div class="card-body trans-details">
                                                                        <div class="mb-2 card-head align-items-center">
                                                                            <h4 class="card-title mb-0">Transaction Details</h4>
                                                                        </div>
                                                                        <ul class="p-0 m-0">
                                                                            @if(!empty($transaction->descr))
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Description: </p>
                                                                                </div>
                                                                                <div class="item-progress value">{{ ucfirst($transaction->descr) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            @endif
                                                                            @if(!empty($transaction->extras))
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Extras: </p>
                                                                                </div>
                                                                                <div class="item-progres value">{{ ucfirst($transaction->extras) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            @endif
                                                                            @if(!empty($transaction->extra_info))
                                                                                @foreach ( json_decode($transaction->extra_info) as $key=>$value )
                                                                                    <li class="d-flex mb-1">
                                                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                    <div class="me-2">
                                                                                        <p class="mb-0 lh-1 key">{{ $key }} </p>
                                                                                    </div>
                                                                                    <div class="item-progres value">{{ ucfirst($value) }}</div>
                                                                                    </div>
                                                                                </li>
                                                                                @endforeach
                                                                            @endif
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Payment method: </p>
                                                                                </div>
                                                                                <div class="item-progress value">{{ ucfirst($transaction->payment_method) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Service: </p>
                                                                                </div>
                                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                                    {{ ucfirst(str_replace("-"," ",$transaction->reason))}}
                                                                                @else
                                                                                <div class="item-progress value">{{$transaction->product->display_name}} @if(!empty($transaction->variation->system_name)) ({{$transaction->variation->system_name}})@endif</div>
                                                                                @endif
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Phone: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{$transaction->customer_phone}}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Biller: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{$transaction->unique_element}}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Email: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{$transaction->customer_email }}</div>
                                                                                </div>
                                                                            </li>

                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Unit Price: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->unit_price) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            @if(!empty($transaction->provider_charge))
                                                                                <li class="d-flex mb-1">
                                                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                    <div class="me-2">
                                                                                        <p class="mb-0 lh-1 key">Convenience Fee </p>
                                                                                    </div>

                                                                                    <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->provider_charge) }}</div>
                                                                                    </div>
                                                                                </li>
                                                                            @endif
                                                                           
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Quantity: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{ number_format($transaction->quantity) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Discount Applied: </p>
                                                                                </div>
                                                                                
                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->discount, 2) }}</div>
                                                                                </div>
                                                                               
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Total Amount: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->total_amount, 2) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Initial Balance: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->balance_before, 2) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Final Balance: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->balance_after, 2) }}</div>
                                                                                </div>
                                                                            </li>
                                                                        </ul>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </section>
                                <!-- Nav Filled Ends -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>


@endsection
