<?php
    if($transaction->status == 'declined'){
        $color = 'red';
    }elseif($transaction->status == 'pending'){
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
                                                        <div class="row">
                                                            <div class="col-md-1">
                                                                @if(in_array($transaction->reason, ['LEVEL-UPGRADE','WALLET-FUNDING','ADMIN-DEBIT','ADMIN-CREDIT']))
                                                                <img id="product-image" width="60" height="60" src="{{ asset('site/upgrade.jpg') }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @else
                                                                <img id="product-image" width="60" height="60" src="{{ asset($transaction->product->image) }}" alt="" class="product-image" style="margin:5px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                                                                @endif

                                                            </div>
                                                                <div class="col-md-5">
                                                                    <h5 style="color:black"><strong>{{ $transaction->product->name }}</strong></h5>
                                                                    <h5 class="mb-1">
                                                                        {{ $transaction->transaction_id }}</h5> <br>

                                                                    {{ $transaction->created_at }}
                                                                    
                                                                </div>
                                                            
                                                                <div class="col-md-4">
                                                                    <strong>Status:</strong>
                                                                    <span style="color:{{ $color }}"><strong>{{ ucfirst($transaction->status) }}</strong></span><br>
                                                                    @if(!empty($transaction->description))
                                                                    <strong>Description:</strong> {{$transaction->description}} <br>
                                                                    @endif
                                                                    @if(!empty($transaction->decline_reason))
                                                                    <strong>Decline Reason:</strong> {{$transaction->decline_reason}}
                                                                    @endif
                                                                    <br>
                                                                    
                                                                    @if($transaction->status == 'pending')
                                                                    <a onclick="return confirm('You are about to approve this transaction. Customer will be credited')" href="{{ route('admin.approve.airtime2cash.transaction', $transaction->id) }}" class="btn btn-success btn-sm" id="approve"> Approve</a>

                                                                    <a data-target="#decline" class="btn btn-danger btn-sm" data-toggle="modal" class="MainNavText" id="MainNavHelp" href="#decline">Decline</a>
                                                                    
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <strong class="heads">Trail:</strong> <br>
                                                                    <span class="text warning"><strong>Requested: </strong> {{ $transaction->created_at}} <br>
                                                                    @if($transaction->status == 'approved')
                                                                    <span style="color:green"><strong>Approved:</strong> {{ $transaction->updated_at}}
                                                                    @endif
                                                                    @if($transaction->status == 'declined')
                                                                    <span style="color:red"><strong>Declined:</strong> {{ $transaction->updated_at}} 
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong class="heads" style="color:green">Payment Details</strong> <br>
                                                                    <strong>Amount to Transfer: </strong>{!! getSettings()->currency. number_format($transaction->total_amount, 2) !!} <br>
                                                                    <strong>Charge Rate: </strong>{{ $transaction->charge_rate }}% <br>
                                                                    <strong>Charge Amount: </strong>{!! getSettings()->currency. number_format($transaction->amount_charged, 2) !!} <br>
                                                                    <strong>Amount to Receive: </strong>{!! getSettings()->currency. number_format($transaction->amount_paid,2) !!} <br>
                                                                    <strong>Date: </strong>{{ date("M jS, Y g:iA", strtotime($transaction->created_at)) }}
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong class="heads" style="color:green">Payment Method</strong> <br>
                                                                    <strong>Where to receive funds: </strong>{{ $transaction->payment_method }}
                                                                    @if(!empty($transaction->account_name)) <br>
                                                                    <strong>Bank Name: </strong>{{ $transaction->bank_name }}<br>
                                                                    <strong>Bank Code: </strong>{{ $transaction->bank_code }}<br>
                                                                    <strong>Account Name: </strong>{{ $transaction->account_name }}<br>
                                                                    <strong>Account Number: </strong>{{ $transaction->account_number }}
                                                                    @endif
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
                                                            @endif
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
<div class="modal fade text-left" id="decline" tabindex="-1" role="dialog" aria-labelledby="decline" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title white" id="myModalLabel160">Decline Transaction</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <form action="{{route('admin.decline.airtime2cash.transaction', $transaction->id)}}" method="POST">
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

@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
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
				$('#q_res').html(JSON.stringify(data.message, null, 3));
			}
		});
		e.preventDefault();
	}

    // function queryStatus(id){
	// 	var tid = id;
    //     url = '{{url("/")}}/admin/requery-transaction/'+tid;

	// 	$.ajax({
	// 		url : url,
	// 		type : 'GET',
	// 		beforeSend: function (){
	// 			$('#q_res').hide();
	// 			$('#img_loading').show();
    //             $('.validate-div').show();
	// 			$('#img_loading2').show();
	// 			$('#qw_status').html('Processing....');
	// 		},
	// 		success:function (data) {
	// 			$('#qw_status').html('Requery Complete <i class="fa fa-check"></i>');
	// 			$('#img_loading').hide();
	// 			$('#q_res').show();
	// 			$('#q_res').html(data.message);

    //             // $('#validate-div').show();
    //             // $('#validate-biller').html('Validate Biller <i class="fa fa-check"></i>');
	// 			$('#img_loading2').hide();
	// 			$('#validate-div').show();
	// 			$('#q_res2').show();
	// 			$('#q_res2').html(JSON.stringify(data.api_response, null, 5));

	// 		}
	// 	});
	// 	e.preventDefault();
	// }

    // function validateBiller(variation_id, element, value){
    //     var variation_id = variation_id;
    //     var element = element;
    //     var value = value;

    //     var data = {
    //         'variation':variation_id,
    //         'unique_element':{{$transaction->unique_element}},
    //         _token: {{ csrf_token() }},
    //     };

    //     var url = "{{ route('admin.verify.post') }}";
	// 	$.ajax({
	// 		url : url,
	// 		type : 'POST',
    //         data : data,
	// 		beforeSend: function (){
	// 			$('.validate-div').show();
	// 			$('#img_loading2').show();
	// 			$('#validate-biller').html('Processing....');
	// 		},
	// 		success:function (data) {
    //             console.log(data);
	// 			$('#validate-biller').html('Validate Biller <i class="fa fa-check"></i>');
	// 			$('#img_loading2').hide();
	// 			$('#validate-div').show();
	// 			$('#q_res2').show();
	// 			$('#q_res2').html(data.message);
	// 		}
	// 	});
	// 	e.preventDefault();
    // }

</script>
@endsection