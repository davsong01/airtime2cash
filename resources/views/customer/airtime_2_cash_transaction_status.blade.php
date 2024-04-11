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

                                                        @if(in_array($transaction->status, ['approved']))
                                                            <div class="alert alert-success" role="alert" style="margin-bottom: 5px !important; margin-top:10px">
                                                                <strong>{{ strtoupper($transaction->status) }}</strong>
                                                            </div>
                                                        @elseif($transaction->status == 'declined')
                                                            <div class="alert alert-danger" role="alert" style="margin-bottom: 5px !important;margin-top:10px">
                                                                <strong>{{ strtoupper($transaction->status) }}</strong>
                                                            </div>
                                                        @else 
                                                        <div class="alert alert-warning" role="alert" style="margin-bottom: 5px !important;margin-top:10px">
                                                            <strong>{{ strtoupper($transaction->status) }}</strong>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <div class="card content-area">
                                                        <div class="card-innr">
                                                            
                                                            <div class="row">
                                                                <div class="col-md-1">
                                                                    <img id="product-image" width="60" height="60" src="{{ asset($transaction->product->image) }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                </div>
                                                                <div class="col-md-4">
                                                                   
                                                                    <span class="data-details-title" style="color:#174159;"><h3 style="color:#174159;"><strong style="line-height: unset;font-size:17px;">
                                                                        @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                            {{ ucfirst(str_replace("-"," ",$transaction->reason))}}
                                                                        @else
                                                                        {{ $transaction->product->name }}@if(!empty($transaction->variation->system_name)) | {{$transaction->variation->system_name}} @endif 
                                                                        @endif
                                                                    </strong></h3></span>
                                                                   
                                                                    <span class="data-details-info"><span style="color:#174159;">Amount Transferred: {!! getSettings()->currency !!}{{ number_format($transaction->total_amount, 2) }}</span></span> <br>
                                                                    <span class="data-details-info"><span style="color:#174159;">Amount Received: {!! getSettings()->currency !!}{{ number_format($transaction->amount_paid, 2) }}</span></span> <br>
                                                                    @if($transaction->status == 'approved')
                                                                    <a href="{{ route('airtime2cash.transaction.receipt.download', $transaction->id)}}" target="_blank" class="btn btn-primary mt-1 mb-1" style="color:#fff;width:100%;"><i class="fa fa-download"></i>Download Transaction Receipt</a>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <strong>Date</strong> <br>
                                                                    {{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong>Transaction ID</strong> <br>
                                                                    <span >{{ ucfirst($transaction->transaction_id) }}</span> <br>
                                                                </div>
                                                            </div>
                                                            <div class="row" >
                                                                <div class="col-md-12">
                                                                    <div class="card-body trans-details">
                                                                        <div class="mb-2 card-head align-items-center">
                                                                            <h4 class="card-title mb-0">Transaction Details</h4>
                                                                        </div>
                                                                        <ul class="p-0 m-0">
                                                                            
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Where to receive payment: </p>
                                                                                </div>
                                                                                <div class="item-progress value">{{ ucfirst($transaction->payment_method) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Product: </p>
                                                                            
                                                                                </div>
                                                                                <div class="item-progress value">{{$transaction->product->name}}</div>

                                                                                @if($transaction->status == 'declined')
                                                                                    {{ $transaction->decline_reason}}
                                                                                @endif
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Phone: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{$transaction->phone_numbers}}</div>
                                                                                </div>
                                                                            </li>
                                                                             <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Amount to Transfer: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->total_amount) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Amount Charged: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->amount_charged) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Charge Rate: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{ number_format($transaction->charge_rate) }}%</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Amount to recieve: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{!! getSettings()->currency !!}{{ number_format($transaction->amount_paid) }}</div>
                                                                                </div>
                                                                            </li>
                                                                            <li class="d-flex mb-1">
                                                                                 <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key">Status: </p>
                                                                                </div>

                                                                                <div class="item-progress value">{{ ucfirst($transaction->status) }}</div>
                                                                                </div>
                                                                            </li>
                                                                           
                                                                            {{-- <li class="d-flex mb-1">
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
                                                                            </li> --}}
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
