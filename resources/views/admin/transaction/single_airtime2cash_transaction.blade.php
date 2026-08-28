<?php
    if($transaction->status == 'declined'){
        $color = 'red';
    }elseif($transaction->status == 'pending'){
        $color = '#FDAC41';
    }else {
        $color = 'green';
    }
    $completedAt = $transaction->completed_at ?? $transaction->updated_at ?? $transaction->created_at;
    $completedAtText = $completedAt ? date("M jS, Y g:iA", strtotime($completedAt)) : 'Awaiting completion';
    $statusClass = in_array(strtolower((string) ($transaction->status ?? 'pending')), ['approved', 'success', 'successful', 'completed', 'delivered'], true)
        ? 'is-success'
        : (strtolower((string) ($transaction->status ?? 'pending')) === 'pending' ? 'is-warning' : 'is-danger');
?>
@php
    $product = $transaction->product;
    $productName = $product->name ?? 'Airtime to Cash';
    $productImage = $product->image ?? 'site/upgrade.jpg';
@endphp
@extends('layouts.app')
@section('title', 'Transction Details')

@section('page-css')
<style>
    .txn-details-page {
        font-size: smaller;
        font-weight: 398;
        color: black;
    }

    .reset-pin {
        font-size: 10px;
        float: right;
    }

    .heads {
        color: black
    }

    .txn-details-page .table {
        color: black;
    }

    .txn-details-page code {
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
</style>
@endsection
@section('content')
<!-- Content wrapper -->
<div class="app-content content txn-details-page">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <!-- Basic Inputs start -->
            <section id="basic-input">
                <div class="row">
                    <div class="col-md-12">
                        <section class="ops-hero mb-2">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <span class="ops-kicker"><i class="bx bx-transfer-alt"></i> Airtime to cash review</span>
                                    <h2>{{ $productName }}</h2>
                                    <p>Track the conversion request, confirm payout status, and review the completion trail in one dashboard view.</p>
                                </div>
                                <div class="col-lg-4 text-lg-right mt-2 mt-lg-0">
                                    <a href="{{ route('admin.airtime.2.cash.log') }}" class="btn btn-light"><i class="bx bx-list-ul mr-50"></i> Conversion log</a>
                                    <a href="{{ route('admin.walletfundinglog') }}" class="btn btn-outline-primary ml-50"><i class="bx bx-wallet mr-50"></i> Wallet funding</a>
                                </div>
                            </div>
                        </section>

                        <section class="row">
                            <div class="col-sm-6 col-xl-3">
                                <div class="card ops-metric-card">
                                    <div class="card-body">
                                        <span class="ops-metric-icon {{ $statusClass }}"><i class="bx bx-stats"></i></span>
                                        <span class="ops-metric-label">Status</span>
                                        <strong>{{ ucfirst($transaction->status) }}</strong>
                                        <small>Current conversion state</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card ops-metric-card">
                                    <div class="card-body">
                                        <span class="ops-metric-icon is-primary"><i class="bx bx-money"></i></span>
                                        <span class="ops-metric-label">Amount paid</span>
                                        <strong>{!! getSettings()->currency !!}{{ number_format((float) $transaction->amount_paid, 2) }}</strong>
                                        <small>Customer cash-out value</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card ops-metric-card">
                                    <div class="card-body">
                                        <span class="ops-metric-icon is-success"><i class="bx bx-trending-up"></i></span>
                                        <span class="ops-metric-label">Income</span>
                                        <strong>{!! getSettings()->currency !!}{{ number_format((float) ($transaction->profit ?? 0), 2) }}</strong>
                                        <small>Conversion revenue recorded</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card ops-metric-card">
                                    <div class="card-body">
                                        <span class="ops-metric-icon is-warning"><i class="bx bx-check-shield"></i></span>
                                        <span class="ops-metric-label">Completed</span>
                                        <strong>{{ $completedAtText }}</strong>
                                        <small>Final completion trail</small>
                                    </div>
                                </div>
                            </div>
                        </section>

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
                                                        <div class="row">
                                                            <div class="col-md-1">
                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                <img id="product-image" width="60" height="60" src="{{ asset('site/upgrade.jpg') }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @else
                                                                
                                                                <img id="product-image" width="60" height="60" src="{{ asset($productImage) }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @endif

                                                            </div>
                                                            <div class="col-md-5">
                                                                <h5 style="color:black"><strong>{{ $productName }}</strong></h5>
                                                                <h5 class="mb-1">
                                                                    {{ $transaction->transaction_id }}</h5> <br>

                                                                {{ $transaction->created_at }}
                                                                
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <strong>Status:</strong>
                                                                <span style="color:{{ $color }}"><strong>{{ ucfirst($transaction->status) }}</strong></span><br>
                                                                @if(!empty($transaction->description))
                                                                <strong>Description:</strong> {{$transaction->description}} <br>
                                                                @endif
                                                                @if(!empty($transaction->decline_reason) && $transaction->status == 'declined')
                                                                <strong>Decline Reason:</strong> {{$transaction->decline_reason}}
                                                                @endif
                                                                <br>
                                                                
                                                                @if($transaction->status == 'pending')
                                                                    <a onclick="return confirm('You are about to approve this transaction. Customer will be credited')" href="{{ url('/admin/approve-airtime2cash-transaction/'.$transaction->id) }}" class="btn btn-success btn-sm" id="approve"> Approve</a>

                                                                    <a data-target="#decline" class="btn btn-danger btn-sm" data-toggle="modal" class="MainNavText" id="MainNavHelp" href="#decline">Decline</a>
                                                                    @if($transaction->status != 'approved')
                                                                    <a data-target="#change" class="btn btn-info btn-sm" data-toggle="modal" class="MainNavText" id="MainNavHelp" href="#decline">Change Payment Method</a>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <strong class="heads">Wallet Trail:</strong> <br>
                                                                @php
                                                                    $walletTrail = $transaction->wallets ?? collect();
                                                                    $snapshotTrail = $walletTrail->isEmpty() && $transaction->transactionLog
                                                                        ? collect([$transaction->transactionLog])
                                                                        : collect();
                                                                    $trail = $walletTrail->isNotEmpty() ? $walletTrail : $snapshotTrail;
                                                                    $initialBalance = $trail->first()?->balance_before;
                                                                    $finalBalance = $trail->last()?->balance_after;
                                                                @endphp
                                                                @if($trail->count())
                                                                    <strong>Initial Balance:</strong> {!! getSettings()->currency !!}{{ number_format((float) ($initialBalance ?? 0), 2) }} <br>
                                                                    <strong>Final Balance:</strong> {!! getSettings()->currency !!}{{ number_format((float) ($finalBalance ?? 0), 2) }} <br><br>
                                                                    @if($walletTrail->isEmpty() && $transaction->transactionLog)
                                                                        <small class="text-muted d-block mb-1">
                                                                            Showing transaction log snapshot while wallet rows are unavailable.
                                                                        </small>
                                                                    @endif
                                                                @endif
                                                                @forelse($trail as $wallet)
                                                                    @php
                                                                        $isSnapshot = $wallet instanceof \App\Models\TransactionLog;
                                                                        $trailType = $isSnapshot ? 'Snapshot' : strtoupper($wallet->type);
                                                                        $trailAmount = $wallet->amount ?? $wallet->total_amount ?? $transaction->amount_paid;
                                                                        $trailColor = $isSnapshot
                                                                            ? (($wallet->status ?? $transaction->status) === 'failed' ? 'red' : '#FDAC41')
                                                                            : ($wallet->type === 'credit' ? 'green' : 'red');
                                                                    @endphp
                                                                    <span style="color:{{ $trailColor }}">
                                                                        <strong>{{ $trailType }}:</strong>
                                                                        {{ $wallet->created_at ?? $transaction->created_at }}
                                                                        ({!! getSettings()->currency . number_format((float) $trailAmount, 2) !!})
                                                                        <br>
                                                                        @if(! is_null($wallet->balance_before) || ! is_null($wallet->balance_after))
                                                                            <small>
                                                                                Balance {{ $wallet->balance_before !== null ? number_format((float) $wallet->balance_before, 2) : 'n/a' }}
                                                                                &rarr;
                                                                                {{ $wallet->balance_after !== null ? number_format((float) $wallet->balance_after, 2) : 'n/a' }}
                                                                            </small>
                                                                        @endif
                                                                        @if($isSnapshot && ! empty($wallet->status))
                                                                            <br><small>Status: {{ ucfirst($wallet->status) }}</small>
                                                                        @endif
                                                                    </span>
                                                                    <br>
                                                                @empty
                                                                    <span class="text-muted">No wallet trail or snapshot recorded for this airtime-to-cash transaction.</span>
                                                                @endforelse
                                                            </div>
                                                            <div class="col-md-4">
                                                                <strong class="heads">Trail:</strong> <br>
                                                                <span class="text warning"><strong>Requested: </strong> {{ $transaction->created_at}} <br>
                                                                @if($transaction->status == 'approved')
                                                                <span style="color:green"><strong>Approved:</strong> {{ $transaction->updated_at}}
                                                                @endif
                                                                @if($transaction->status == 'declined')
                                                                <span style="color:red"><strong>Declined:</strong> {{ $transaction->updated_at}} 
                                                                @endif
                                                                <br>
                                                                <strong>Completed:</strong>
                                                                @if($transaction->status == 'pending' && blank($transaction->completed_at))
                                                                    Awaiting completion
                                                                @else
                                                                    {{ $completedAtText }}
                                                                @endif
                                                            </div>
                                                            <div class="col-md-4">
                                                                <strong class="heads" style="color:green">Payment Details</strong> <br>
                                                                <strong>Amount to Transfer: </strong>{!! getSettings()->currency. number_format($transaction->total_amount, 2) !!} <br>
                                                                <strong>Transfer Method: </strong>{{ $transaction->transfer_mode === 'auto_share' ? 'Auto Share' : 'Manual Transfer' }} <br>
                                                                <strong>Charge Rate: </strong>{{ $transaction->charge_rate }}% <br>
                                                                <strong>Profit Percentage: </strong>{{ number_format((float) ($transaction->profit_percentage ?? 0), 2) }}% <br>
                                                                <strong>Charge Amount: </strong>{!! getSettings()->currency. number_format($transaction->amount_charged, 2) !!} <br>
                                                                <strong>Profit: </strong>{!! getSettings()->currency. number_format((float) ($transaction->profit ?? 0), 2) !!} <br>
                                                                <strong>Amount to Receive: </strong>{!! getSettings()->currency. number_format($transaction->amount_paid,2) !!} <br>
                                                                <strong>Date: </strong>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <strong class="heads" style="color:green">Payment Method</strong>  <br>
                                                                <strong>Where to receive funds: </strong>{{ $transaction->payment_method }}
                                                                
                                                                @if($transaction->payment_method == 'Transfer to Bank Account') <br>
                                                                <strong>Bank Name: </strong>{{ $transaction->bank_name }}<br>
                                                                <strong>Bank Code: </strong>{{ $transaction->bank_code }}<br>
                                                                <strong>Account Name: </strong>{{ $transaction->account_name }}<br>
                                                                <strong>Account Number: </strong>{{ $transaction->account_number }} <br>
                                                                @endif
                                                            </div>

                                                            <div class="col-md-4">
                                                                <strong class="heads" style="color:green">Provider Status</strong>  <br>
                                                                <strong>Provider: </strong>{{ $transaction->provider->name ?? 'Unknown' }} <br>
                                                                <strong>Current Status: </strong>
                                                                <span id="airtime-provider-status">{{ ucfirst($transaction->provider_status ?? $transaction->status) }}</span><br>
                                                                <button type="button" class="btn btn-primary btn-sm mt-2" id="query-airtime-status" onclick="queryAirtimeStatus()">
                                                                    Query Provider Status
                                                                </button>
                                                                <div class="well mt-2" id="airtime-status-container" style="display:none;">
                                                                    <img src="{{url('/')}}/site/loading.gif" height="70" style="display:none; margin-left: auto; margin-right:auto;height:initial;" id="airtime-status-loading">
                                                                    <div id="airtime-status-response" style="max-height:300px;overflow:scroll;word-wrap: break-word"></div>
                                                                </div>
                                                            </div>
                                                            
                                                        </div>
                                                        @if($transaction->payment_method == 'Transfer to Bank Account')
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="well">
                                                                    <address>
                                                                        <img src="{{url('/')}}/site/loading.gif" height="70" style="display:none; margin-left: auto; margin-right:auto;height:initial;" id="img_loading">
                                                                        <div id="q_res" style="max-height:300px;overflow:scroll;word-wrap: break-word">
                                                                        </div>
                                                                    </address>
                                                                </div>
                                                                <a id="verify-bank-details" onclick="return confirm('Are you sure?')?queryBankDetails():'';" class="btn btn-success btn-sm" style="color:#fff;"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q65 0 123 19t107 53l-58 59q-38-24-81-37.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160q32 0 62-6t58-17l60 61q-41 20-86 31t-94 11Zm280-80v-120H640v-80h120v-120h80v120h120v80H840v120h-80ZM424-296 254-466l56-56 114 114 400-401 56 56-456 457Z"/></svg> Verify Bank Details</a>

                                                                
                                                            </div>
                                                            @if(!empty($transaction->bank_transfer_api_response))
                                                            <div class="col-md-6">
                                                                <label for="">Bank Transfer Response</label>
                                                                <div class="validate-div">
                                                                    <address>
                                                                        <div id="q_res2" style="max-height:300px;overflow:scroll;word-wrap: break-word">
                                                                            {{$transaction->bank_transfer_api_response}}
                                                                        </div>
                                                                    </address>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endif
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
<div class="modal fade text-left" id="decline" tabindex="-1" role="dialog" aria-labelledby="decline" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title white" id="myModalLabel160">Decline Transaction</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <form action="{{url('/admin/decline-airtime2cash-transaction/'.$transaction->id)}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset class="form-group">
                                <label for="decline_reason" style="display: block">Decline Reason</label>
                                <textarea style="width: 100%;" name="decline_reason" id="decline_reason" cols="30" width="100%" rows="10"></textarea>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                        <i class="bx bx-x d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">Close</span>
                    </button>
                    <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade text-left" id="change" tabindex="-1" role="dialog" aria-labelledby="decline" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title white" id="myModalLabel160">Change Transaction Payment method</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <form action="{{url('/admin/change-transaction-method/'.$transaction->id)}}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        $bankTransferCharge = getBankTransferChargeDetails($transaction->total_amount, getSettings()->bank_transfer_provider_id);
                        $bankTransferProviderName = $bankTransferCharge['provider_name'] ?? 'active bank transfer provider';
                        $bankTransferFee = (float) ($bankTransferCharge['transfer_fee'] ?? 0);
                    @endphp
                    <div class="row">
                        <div class="col-md-12">
                            {{-- {{dd($transaction->payment_method)}} --}}
                            <fieldset class="form-group col-sm-12 col-12">
                                <label for="category">Select Payment method</label>
                                <small class="d-block text-muted mb-2">
                                    Wallet to bank transfers are routed through <strong>{{ $bankTransferProviderName }}</strong>
                                    and this transaction currently attracts a base transfer charge of
                                    <strong>{!! getSettings()['currency'] !!}{{ number_format($bankTransferFee, 2) }}</strong>.
                                </small>
                                    <select class="form-control" name="payment_method" id="payment_method" required>
                                        <option value="Transfer to Wallet" {{ $transaction->payment_method == 'Transfer to Wallet' ? 'selected' : ''}}>Transfer to Wallet ({!! getSettings()['currency'] !!}0 charges)</option>
                                        <option value="Transfer to Bank Account" {{ $transaction->payment_method == 'Transfer to Bank Account' ? 'selected' : ''}}>Transfer to Bank Account ({!! getSettings()['currency'] !!}{{ number_format($bankTransferFee, 2) }} base charge via {{ $bankTransferProviderName }})</option>
                                    </select>
                                </select>
                            </fieldset>
                        </div>
                        <div class="col-md-12" id="bank-details-div" style="display:{{ $transaction->payment_method == 'Transfer to Bank Account' ? 'block' : 'none'}}">
                            <fieldset class="form-group col-sm-12 col-12">
                                <label for="payment_method">Select Bank for {{ $bankTransferProviderName }}</label>
                                <select class="form-control" name="bank" id="bank">
                                    <option value="">Select</option>
                                    @foreach($banks as $bank)
                                    <option value="{{ $bank->cbn_code }}" {{ ($transaction->bank_code == $bank->cbn_code ||  old('bank') == $bank->cbn_code) ? 'selected' : ''}}>{{ $bank->bank_name }}</option>
                                    @endforeach
                                </select>
                                <small class="d-block text-muted mt-1">
                                    Bank details here will be validated against the same provider used for wallet-to-bank transfers.
                                </small>
                            </fieldset>
                            <fieldset class="form-group col-sm-12 col-12">
                                <label for="receive" class="">Account Number</label>
                                <input class="form-control" id="account_number" name="account_number" type="text" value="{{ $transaction->account_number ?? old('account_number')}}">
                            </fieldset>
                            <fieldset class="form-group col-sm-12 col-12">
                                <label for="receive" class="">Account Name</label>
                                <input class="form-control" id="account_name" name="account_name" type="text" value="{{ $transaction->account_name ?? old('account_name')}}">
                                <small class="footnote" style="color:red">Please ensure that bank details entered are correct to enable us complete the transaction</small>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                        <i class="bx bx-x d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">Close</span>
                    </button>
                    <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
    function renderPrettyJsonPanel(payload, title, subtitle) {
        let parsed = payload;

        if (typeof parsed === 'string') {
            try {
                parsed = JSON.parse(parsed);
            } catch (error) {
                parsed = { raw: payload };
            }
        }

        const pretty = JSON.stringify(parsed ?? {}, null, 2);

        return `
            <div class="validate-div mb-0">
                <div class="small text-uppercase text-muted font-weight-bold mb-2">${escapeHtml(title || 'Provider status')}</div>
                <div class="text-muted mb-2">${escapeHtml(subtitle || '')}</div>
                <pre class="mb-0 bg-transparent border-0 p-0" style="white-space:pre-wrap;max-height:260px;overflow:auto;">${escapeHtml(pretty)}</pre>
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderVerificationCard(payload, title) {
        const response = payload?.raw_response ?? payload?.data ?? payload ?? {};
        const rawResponse = typeof response === 'string'
            ? response
            : JSON.stringify(response, null, 2);

        return `
            <div class="validate-div mb-0">
                <div class="small text-uppercase text-muted font-weight-bold mb-2">${escapeHtml(title || 'Bank verification')}</div>
                <pre class="mb-0 bg-transparent border-0 p-0" style="white-space:pre-wrap;max-height:260px;overflow:auto;">${escapeHtml(rawResponse)}</pre>
            </div>
        `;
    }

    function queryBankDetails(){
        url = '{{route("admin.verify.bank.details")}}';
        var formData =  {
            transaction_id: {{ $transaction->id }},
            bank_code: '{{$transaction->bank_code}}',
            account_number:'{{$transaction->account_number}}',
        };

		$.ajax({
			url : url,
			type : 'POST',
            data: formData, 
			beforeSend: function (){
				$('#q_res').hide();
				$('#img_loading').show();
				$('#validate-biller').html('Processing....');
			},
			success:function (data) { 
				$('#qw_debit').html('Query Debit <i class="fa fa-check"></i>');
				$('#img_loading').hide();
				$('#q_res').show();
				$('#q_res').html(renderVerificationCard(data, 'Account verification completed'));
			}
		});
		e.preventDefault();
	}

    function queryAirtimeStatus() {
        const url = '{{ url('/admin/single-airtime2cash-transaction-view/'.$transaction->id.'/requery') }}';

        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function () {
                $('#airtime-status-container').show();
                $('#airtime-status-loading').show();
                $('#airtime-status-response').hide();
                $('#query-airtime-status').prop('disabled', true).text('Querying...');
            },
            success: function (data) {
                $('#airtime-provider-status').text((data?.provider_status ?? 'unknown').toString().replace(/^./, function (char) { return char.toUpperCase(); }));
                $('#airtime-status-loading').hide();
                $('#airtime-status-response').show().html(renderPrettyJsonPanel(data, 'Provider status response', 'Airtime-to-cash lookup result'));
                $('#query-airtime-status').prop('disabled', false).text('Query Provider Status');
            },
            error: function (xhr) {
                const payload = xhr.responseJSON ?? { status: false, message: 'Unable to query provider status.' };
                $('#airtime-status-loading').hide();
                $('#airtime-status-response').show().html(renderPrettyJsonPanel(payload, 'Provider status response', 'Airtime-to-cash lookup result'));
                $('#query-airtime-status').prop('disabled', false).text('Query Provider Status');
            }
        });
    }

    $('#payment_method').on('change', function () {
        if($('#payment_method').val() == 'Transfer to Bank Account'){
            $('#bank-details-div').show()
            $("#bank").attr({
                "required": '',
            });
            $("#account_number").attr({
                "required": '',
            });
            $("#account_name").attr({
                "required": '',
            });
        }else{
            $('#bank-details-div').hide()
            $("#bank").removeAttr('required');
            $("#account_number").removeAttr('required');
            $("#account_name").removeAttr('required');
        }
    });

</script>
@endsection
