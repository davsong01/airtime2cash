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
@section('title', 'Transction Details')

@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }

    .heads {
        color: black
    }
    body {
        font-size: 1rem;
        font-weight: 398;
        color: black;
        font-size: smaller;
    }
    .table {
        color: black;
    }

    code{
        max-height: 250px;
        display: block;
        overflow:scroll;
        word-wrap: break-word;
        padding: 10px;
        margin:bottom:10px;
        height: 250px;
    }
    .well, .validate-div {
        min-height: 20px;
        padding: 19px;
        margin-bottom: 20px;
        background-color: #f5f5f5;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
        box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
        margin-top: 10px;
    }
    .txn-breakdown-box {
        background: #f8fafc;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        padding: 14px 16px;
        color: #1f2937;
    }
    .txn-breakdown-title {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .txn-breakdown-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    .txn-breakdown-row:last-child {
        margin-bottom: 0;
    }
    .txn-breakdown-section {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e5e7eb;
    }
    .txn-breakdown-section-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .txn-bank-box {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        padding: 14px 16px;
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
                                                        <h4 class="card-title">Transaction Details</h4>
                                                        @include('layouts.alerts')
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        @php
                                                            $isWalletToBank = strtolower((string) ($transaction->reason ?? '')) === 'wallet to bank transfer';
                                                            $chargeBreakdown = collect(normalizeChargeBreakdown($transaction->charge_breakdown ?? []))->filter(fn ($charge) => is_array($charge));
                                                            $baseTransferCharge = $chargeBreakdown->whereIn('type', ['provider_fee', 'our_charge'])->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                                                            $bandExtraCharges = $chargeBreakdown->where('type', 'band_extra_charge')->values();
                                                            $additionalCharges = $chargeBreakdown->where('type', 'global_extra_charge')->values();
                                                            $extraChargesTotal = $bandExtraCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)) + $additionalCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                                                            $totalFee = $baseTransferCharge + $extraChargesTotal;
                                                            $requestData = [];
                                                            if (!empty($transaction->request_data)) {
                                                                $decodedRequestData = json_decode($transaction->request_data, true);
                                                                if (is_array($decodedRequestData)) {
                                                                    $requestData = $decodedRequestData;
                                                                }
                                                            }
                                                            $legacyBankCode = $transaction->bank_code ?? data_get($requestData, 'bank_code') ?? data_get($requestData, 'bank') ?? null;
                                                            $bankName = $transaction->bank?->bank_name
                                                                ?: $transaction->bank_name
                                                                ?: ($legacyBankCode ? \App\Models\Bank::where('cbn_code', $legacyBankCode)->value('bank_name') : null)
                                                                ?: 'N/A';

                                                            if ($baseTransferCharge <= 0 && (float) $transaction->provider_charge > 0) {
                                                                $baseTransferCharge = (float) $transaction->provider_charge;
                                                                $totalFee = $baseTransferCharge + $extraChargesTotal;
                                                            }
                                                        @endphp
                                                        <div class="row">
                                                            <div class="col-md-1">
                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                <img id="product-image" width="60" height="60" src="{{ asset('site/upgrade.jpg') }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @else
                                                                <img id="product-image" width="60" height="60" src="{{ asset($transaction->product->image) }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @endif

                                                            </div>
                                                                <div class="col-md-5">
                                                                    <h5 style="color:black"><strong>{{ $transaction->product_name }}</strong></h5>
                                                                    <h5 class="mb-1">
                                                                        {{ $transaction->transaction_id }}</h5> <br>

                                                                    {{ $transaction->created_at }}
                                                                    @if(!in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING']))
                                                                     <br>
                                                                     <a href="{{ route('transaction.receipt.download', $transaction->id)}}" target="_blank" class="btn btn-primary btn-sm" style="color:#fff;"><i class="fa fa-download"></i> Download Receipt</a> <br>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-3">
                                                                   <strong>Request Id:</strong> <br>{{ $transaction->reference_id }} <br>
                                                                   <strong>IP Address: </strong><br>{{ $transaction->ip_address }} <br>
                                                                   @if(!empty($transaction->extras))
                                                                    <li class="d-flex mb-1">
                                                                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                            <div class="me-2">
                                                                                <p class="mb-0 lh-1 key"><strong>Extras:</strong> <br></p>
                                                                            </div>
                                                                            <div class="">{{ ucfirst($transaction->extras) }}</div>
                                                                        </div>
                                                                    </li>
                                                                    @endif
                                                                    @if(!empty($transaction->extra_info))
                                                                        @foreach ( json_decode($transaction->extra_info) as $key=>$value )
                                                                            <li class="d-flex mb-1">
                                                                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                                                <div class="me-2">
                                                                                    <p class="mb-0 lh-1 key"><strong>{{ $key }}:</strong> </p>
                                                                                </div>
                                                                                <div class="item-progres value">{{ ucfirst($value) }}sd</div>
                                                                            </div>
                                                                        </li>
                                                                        @endforeach
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <strong>User Status:</strong> <br>
                                                                    <span style="color:{{ $color }}"><strong>{{ ucfirst($transaction->descr) }}</strong></span><br><br>
                                                                    <strong>Real Status</strong> <br>
                                                                    <span style="color:{{ $color }}"><strong>{{ ucfirst($transaction->status) }}</strong></span><br><br>
                                                                    @if(!in_array($transaction->status, ['completed','success']))
                                                                    <a id="qw_resolve" class="btn btn-success btn-sm" style="color:#fff;"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M754-81q-8 0-15-2.5T726-92L522-296q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l85-85q6-6 13-8.5t15-2.5q8 0 15 2.5t13 8.5l204 204q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13l-85 85q-6 6-13 8.5T754-81Zm0-95 29-29-147-147-29 29 147 147ZM205-80q-8 0-15.5-3T176-92l-84-84q-6-6-9-13.5T80-205q0-8 3-15t9-13l212-212h85l34-34-165-165h-57L80-765l113-113 121 121v57l165 165 116-116-43-43 56-56H495l-28-28 142-142 28 28v113l56-56 142 142q17 17 26 38.5t9 45.5q0 24-9 46t-26 39l-85-85-56 56-42-42-207 207v84L233-92q-6 6-13 9t-15 3Zm0-96 170-170v-29h-29L176-205l29 29Zm0 0-29-29 15 14 14 15Zm549 0 29-29-29 29Z"/></svg> Resolve</a>
                                                                    @endif
                                                                    {{-- Description <br> --}}
                                                                    {{-- <span style="color:{{ $color }}"><strong>{{ ucfirst($transaction->descr) }}</strong></span><br><br> --}}
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <strong class="heads">Wallet Trail:</strong> <br>

                                                                    @if($transaction->wallets)
                                                                        @foreach($transaction->wallets as $wallet)
                                                                            @if($wallet->type == 'credit')
                                                                            <span style="color:green"><strong>CREDIT :</strong> {{ $wallet->created_at}} ({!! getSettings()->currency. number_format($wallet->amount, 2) !!})
                                                                            </span>
                                                                            @endif
                                                                            @if($wallet->type == 'debit')
                                                                            <span style="color:red"><strong>DEBIT : </strong>{{ $wallet->created_at}}
                                                                                ({!! getSettings()->currency. number_format($wallet->amount, 2) !!})
                                                                            </span>
                                                                            @endif
                                                                            <br>
                                                                        @endforeach
                                                                    @endif

                                                                </div>
                                                                <div class="col-md-3">
                                                                    <strong class="heads">Payment Details</strong> <br>
                                                                    <strong>PAYMENT METHOD: </strong> {{ $transaction->payment_method}} <br>
                                                                    <strong>CHANNEL: </strong>{{ $transaction->channel}} <br>
                                                                    <strong>CUST. EMAIL: </strong>{{ $transaction->customer_email }} <br>
                                                                    <strong>PHONE: </strong>{{ $transaction->customer_phone }} <br>
                                                                    @if($transaction->variation)
                                                                        <strong>Variation: </strong>{{ $transaction->variation->system_name ?? 'null'}} <br>
                                                                    @endif
                                                                    @if(!in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING']))

                                                                        <br><br>
                                                                        <strong class="heads">Transaction Details</strong> <br>
                                                                        <strong>Product:</strong>{{ $transaction->product_name }}
                                                                        @if($transaction->category)<br>
                                                                        <strong>Category:</strong>{{ $transaction->category->display_name }}
                                                                        @endif
                                                                        @if($transaction->category)
                                                                        <br>
                                                                        <strong>Variation:</strong>{{ $transaction->category->system_name }}
                                                                        @endif
                                                                    @endif
                                                                    @if(!empty($transaction->api))
                                                                    <br>
                                                                    <strong>Provider:</strong>{{ $transaction->api->name }} <br>
                                                                    @endif
                                                                </div>
                                                                @if(!in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING']))
                                                                <div class="col-md-3">
                                                                    <strong class="heads">Request Payload</strong> <br>
                                                                    <div>
                                                                        <code style="margin:10px 0">

                                                                            {!! $transaction->request_data !!}
                                                                        </code>

                                                                    </div>

                                                                </div>
                                                                <div class="col-md-3">
                                                                    <strong class="heads">API Response ({{ $transaction->api->name ?? null }})</strong> <br>
                                                                    <div>
                                                                        <code style="margin:10px 0">
                                                                            {!! $transaction->api_response!!}
                                                                        </code>

                                                                    </div>

                                                                </div>
                                                                @endif
                                                            </div>

                                                            <div class="row">
                                                                <div class="table-responsive">
                                                                    <table id="table-extended-success" class="table mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th style="color:black">Item</th>
                                                                                @if($isWalletToBank)
                                                                                    <th style="color:black">Quantity</th>
                                                                                    <th style="color:black">Amount Details</th>
                                                                                    <th style="color:black">Biller</th>
                                                                                    <th style="color:black">Bank Details</th>
                                                                                @else
                                                                                    <th style="color:black">Unit Cost</th>
                                                                                    <th style="color:black">Quantity</th>
                                                                                    <th style="color:black">Amount</th>
                                                                                    <th style="color:black">Biller</th>
                                                                                @endif
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                                    {{ ucfirst(str_replace("-"," ",$transaction->reason))}}
                                                                                @else
                                                                                {{ $transaction->product->name }}@if(!empty($transaction->variation->system_name)) <strong> | {{$transaction->variation->system_name}} </strong> @endif
                                                                                @endif
                                                                            </td>
                                                                            @if($isWalletToBank)
                                                                                <td>
                                                                                    {{ $transaction->quantity  }}
                                                                                </td>
                                                                                <td>
                                                                                    <div class="txn-breakdown-box">
                                                                                        <div class="txn-breakdown-title">Price Breakdown</div>
                                                                                        <div class="txn-breakdown-row">
                                                                                            <span>Transfer Amount</span>
                                                                                            <strong>{!! getSettings()->currency !!}{{ number_format((float) $transaction->amount, 2) }}</strong>
                                                                                        </div>
                                                                                        <div class="txn-breakdown-row">
                                                                                            <span>Base Transfer Charge</span>
                                                                                            <strong>{!! getSettings()->currency !!}{{ number_format((float) $baseTransferCharge, 2) }}</strong>
                                                                                        </div>
                                                                                        @if($bandExtraCharges->count())
                                                                                            <div class="txn-breakdown-section">
                                                                                                <div class="txn-breakdown-section-label">Band Extra Charges</div>
                                                                                                @foreach($bandExtraCharges as $charge)
                                                                                                    <div class="txn-breakdown-row">
                                                                                                        <span>{{ $charge['label'] ?? 'Band charge' }}</span>
                                                                                                        <strong>{!! getSettings()->currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</strong>
                                                                                                    </div>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                        @if($additionalCharges->count())
                                                                                            <div class="txn-breakdown-section">
                                                                                                <div class="txn-breakdown-section-label">Additional Charges</div>
                                                                                                @foreach($additionalCharges as $charge)
                                                                                                    <div class="txn-breakdown-row">
                                                                                                        <span>{{ $charge['label'] ?? 'Additional charge' }}</span>
                                                                                                        <strong>{!! getSettings()->currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</strong>
                                                                                                    </div>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                        @if(!empty($transaction->pricing_band_name))
                                                                                            <div class="txn-breakdown-section">
                                                                                                <div class="txn-breakdown-row mb-0">
                                                                                                    <span>Matched Band</span>
                                                                                                    <strong>{{ $transaction->pricing_band_name }}</strong>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endif
                                                                                        <div class="txn-breakdown-section">
                                                                                            <div class="txn-breakdown-row">
                                                                                                <span>Total Fee</span>
                                                                                                <strong>{!! getSettings()->currency !!}{{ number_format((float) $totalFee, 2) }}</strong>
                                                                                            </div>
                                                                                            <div class="txn-breakdown-row">
                                                                                                <span>Total Debit</span>
                                                                                                <strong>{!! getSettings()->currency !!}{{ number_format((float) $transaction->total_amount, 2) }}</strong>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                                <td>{{ $transaction->unique_element }}
                                                                                    <?php
                                                                                        if (isset($transaction->variation) && in_array($transaction->category->unique_element, verifiableUniqueElements()))
                                                                                        {
                                                                                            $element = $transaction->category->unique_element;
                                                                                        } elseif (isset($transaction->variation) && in_array($transaction->variation->slug, verifiableUniqueElements()))
                                                                                        {
                                                                                            $element = specialVerifiableVariations()[$transaction->variation->slug];
                                                                                        } else {
                                                                                            $element = null;
                                                                                        }
                                                                                    ?>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="txn-bank-box">
                                                                                        <div class="txn-breakdown-title">Bank Details</div>
                                                                                        <div class="txn-breakdown-row">
                                                                                            <span>Bank Name</span>
                                                                                            <strong>{{ $bankName }}</strong>
                                                                                        </div>
                                                                                        <div class="txn-breakdown-row">
                                                                                            <span>Account Name</span>
                                                                                            <strong>{{ $transaction->account_name ?: 'N/A' }}</strong>
                                                                                        </div>
                                                                                        <div class="txn-breakdown-row mb-0">
                                                                                            <span>Account Number</span>
                                                                                            <strong>{{ $transaction->account_number ?: 'N/A' }}</strong>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            @else
                                                                                <td>
                                                                                    {!! getSettings()->currency. number_format($transaction->amount, 2) !!}
                                                                                </td>
                                                                                <td>
                                                                                    {{ $transaction->quantity  }}
                                                                                </td>
                                                                                <td>
                                                                                    <span style="color:black">Convenience Fee:</span> {!! getSettings()->currency. number_format($transaction->provider_charge, 2) !!} <br>
                                                                                    <span style="color:black">Discount: </span>{!! getSettings()->currency. number_format($transaction->discount, 2) !!} <br>
                                                                                    <span style="color:black">Provider Charge:</span>{!! getSettings()->currency. number_format($transaction->provider_charge, 2) !!} <br>
                                                                                    <span style="color:black">Total Amount:</span> {!! getSettings()->currency. number_format($transaction->total_amount, 2) !!}
                                                                                </td>
                                                                                <td>{{ $transaction->unique_element }}
                                                                                    <?php
                                                                                        if (isset($transaction->variation) && in_array($transaction->category->unique_element, verifiableUniqueElements()))
                                                                                        {
                                                                                            $element = $transaction->category->unique_element;
                                                                                        } elseif (isset($transaction->variation) && in_array($transaction->variation->slug, verifiableUniqueElements()))
                                                                                        {
                                                                                            $element = specialVerifiableVariations()[$transaction->variation->slug];
                                                                                        } else {
                                                                                            $element = null;
                                                                                        }
                                                                                    ?>
                                                                                </td>
                                                                            @endif
                                                                        </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <strong>Initial Balance:</strong> {!! getSettings()->currency.number_format($transaction->balance_before, 2) !!} <br>
                                                                    <strong>Final Balance:</strong> {!! getSettings()->currency. number_format($transaction->balance_after, 2) !!}<br>

                                                                    <div class="well">
                                                                        <address>
                                                                            <img src="{{url('/')}}/site/loading.gif" height="70" style="display:none; margin-left: auto; margin-right:auto;height:initial;" id="img_loading">
                                                                            <div id="q_res" style="max-height:300px;overflow:scroll;word-wrap: break-word">
                                                                            </div>
                                                                        </address>
                                                                    </div>
                                                                    <a id="qw_debit" onclick="queryCredit('{{$transaction->id}}', 'credit')" class="btn btn-success btn-sm" style="color:#fff;"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q65 0 123 19t107 53l-58 59q-38-24-81-37.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160q32 0 62-6t58-17l60 61q-41 20-86 31t-94 11Zm280-80v-120H640v-80h120v-120h80v120h120v80H840v120h-80ZM424-296 254-466l56-56 114 114 400-401 56 56-456 457Z"/></svg> Query Credit</a>

                                                                    <a id="qw_credit" onclick="queryCredit('{{$transaction->id}}', 'debit')" class="btn btn-danger btn-sm" style="color:#fff;"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M200-440v-80h560v80H200Z"/></svg> Query Debit</a>

                                                                    <a id="qw-transaction" onclick="queryStatus('{{$transaction->id}}')" data-id="{{$transaction->id}}" class="btn btn-info btn-sm" style="color:#fff;"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="m105-233-65-47 200-320 120 140 160-260 109 163q-23 1-43.5 5.5T545-539l-22-33-152 247-121-141-145 233ZM863-40 738-165q-20 14-44.5 21t-50.5 7q-75 0-127.5-52.5T463-317q0-75 52.5-127.5T643-497q75 0 127.5 52.5T823-317q0 26-7 50.5T795-221L920-97l-57 57ZM643-217q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Zm89-320q-19-8-39.5-13t-42.5-6l205-324 65 47-188 296Z"/></svg></i> Re Query Transaction</a>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <br><br>
                                                                    <div class="validate-div" style="display:none;">
                                                                        <address>
                                                                            <img src="{{url('/')}}/site/loading.gif" height="70" style="margin-left: auto; margin-right:auto;height:initial" id="img_loading2">
                                                                            <div id="q_res2" style="max-height:300px;overflow:scroll;word-wrap: break-word">
                                                                            </div>
                                                                        </address>
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
<script>
    function queryCredit(id, type){
		var tid = id;
        if(type == 'credit'){
			url = '{{url("/")}}/admin/query-wallet/'+tid+'?type=credit&tid='+tid;
        }else{
			url = '{{url("/")}}/admin/query-wallet/'+tid+'?type=debit&tid='+tid;
        }
		$.ajax({
			url : url,
			type : 'GET',
			beforeSend: function (){
				$('#q_res').hide();
				$('#img_loading').show();
				$('#validate-biller').html('Processing....');
			},
			success:function (data) {
				$('#qw_debit').html('Query '+type+' <i class="fa fa-check"></i>');
				$('#img_loading').hide();
				$('#q_res').show();
				$('#q_res').html(data.message);
			}
		});
		e.preventDefault();
	}

    function queryStatus(id){
		var tid = id;
        url = '{{url("/")}}/admin/requery-transaction/'+tid;

		$.ajax({
			url : url,
			type : 'GET',
			beforeSend: function (){
				$('#q_res').hide();
				$('#img_loading').show();
                $('.validate-div').show();
				$('#img_loading2').show();
				$('#qw_status').html('Processing....');
			},
			success:function (data) {
				$('#qw_status').html('Requery Complete <i class="fa fa-check"></i>');
				$('#img_loading').hide();
				$('#q_res').show();
				$('#q_res').html(data.message);

                // $('#validate-div').show();
                // $('#validate-biller').html('Validate Biller <i class="fa fa-check"></i>');
				$('#img_loading2').hide();
				$('#validate-div').show();
				$('#q_res2').show();
				$('#q_res2').html(JSON.stringify(data.api_response, null, 5));

			}
		});
		e.preventDefault();
	}

    function validateBiller(variation_id, element, value){
        var variation_id = variation_id;
        var element = element;
        var value = value;

        var data = {
            'variation':variation_id,
            'unique_element':{{$transaction->unique_element}},
            _token: {{ csrf_token() }},
        };

        var url = "{{ route('admin.verify.post') }}";
		$.ajax({
			url : url,
			type : 'POST',
            data : data,
			beforeSend: function (){
				$('.validate-div').show();
				$('#img_loading2').show();
				$('#validate-biller').html('Processing....');
			},
			success:function (data) {
                console.log(data);
				$('#validate-biller').html('Validate Biller <i class="fa fa-check"></i>');
				$('#img_loading2').hide();
				$('#validate-div').show();
				$('#q_res2').show();
				$('#q_res2').html(data.message);
			}
		});
		e.preventDefault();
    }

    // $('#qw-transaction').click(function () {
    //     let id = $(this).data('id')
    //     $.ajax({
	// 		url : `/admin/requery-transaction/${id}`,
	// 		beforeSend: function (){
	// 			$('.validate-div').show();
	// 			$('#img_loading2').show();
	// 			$('#validate-biller').html('Processing....');
	// 		},
	// 		success:function (data) {
	// 			$('#validate-biller').html('Validate Biller <i class="fa fa-check"></i>');
	// 			$('#img_loading2').hide();
	// 			$('#validate-div').show();
	// 			$('#q_res2').show();
	// 			$('#q_res2').html(data.message);
	// 		}
	// 	});
    // });
</script>
@endsection

{{-- $('#response').html(JSON.stringify(response.response, null, 3)); --}}
