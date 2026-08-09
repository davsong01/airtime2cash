@extends('layouts.app')
@section('title', 'Upgrade Level')

@section('page-css')
<link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/pages/dashboard-analytics.css')}}">
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
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
                                                        <h4 class="card-title">Fund Wallet</h4>
                                                        @include('layouts.walletanalytics')
                                                        @include('layouts.alerts')
                                                    </div>
                                                </div>
                                                    <div class="card-content">
                                                    
                                                        <div class="card-body">
                                                        @if(getFinalKycStatus(auth()->user()->customer->id) == 'unverified')
                                                            Hang on a second! You need to fill in your KYC information for verification before you can fund your wallet <br>
                                                            <a href="{{ route('update.kyc.details') }}" class="btn btn-info btn-sm">Update KYC details here</a>
                                                        @else
                                                            <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                                                                @if(getSettings()->allow_fund_with_card == 'yes')
                                                                <li class="nav-item">
                                                                    <a class="nav-link active" id="home-tab-fill" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details" aria-selected="true">
                                                                        Fund with Card
                                                                    </a>
                                                                </li>
                                                                @endif
                                                                @if(getSettings()->allow_fund_with_reserved_account == 'yes')
                                                                <li class="nav-item">
                                                                    <a class="nav-link" id="profile-tab-fill" data-toggle="tab" href="#variations" role="tab" aria-controls="variations" aria-selected="false">
                                                                        Fund with bank transfer
                                                                    </a>
                                                                </li>
                                                                @endif
                                                            </ul>
                                                            <div class="tab-content pt-1">
                                                                @if(getSettings()->allow_fund_with_card == 'yes')
                                                                <div class="tab-pane {{ getSettings()->allow_fund_with_card == 'yes' ? 'active' : ''}}" id="product-details" role="tabpanel" aria-labelledby="home-tab-fill">
                                                                    <p>Credit your wallet now, and spend from it later. No Need to enter card details everytime you want to make a Payment. Make Faster Payments. 
                                                                    <br>  <small style="color:red"><b>NOTE: </b>A charge of <strong>{{number_format($gateway->charge, 1)}}% @if(getSettings()->card_funding_extra_charge > 0)+ {!!getSettings()->currency !!}{{getSettings()->card_funding_extra_charge}} @endif </strong>is applicable to this method of wallet funding</small>
                                                                    </p>
                                                                    <form action="{{ route('process-customer-load-wallet') }}" method="POST" id="wallet_load">
                                                                        @csrf
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <fieldset class="form-group">
                                                                                    <label for="amount">Enter Amount</label>
                                                                                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="{{ old('amount')}}" required>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <a class="btn btn-primary" style="color:white" onclick="loadWallet()">Pay now</a>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                                @endif
                                                                @if(getSettings()->allow_fund_with_reserved_account == 'yes')
                                                                    <div class="tab-pane {{ getSettings()->allow_fund_with_card  !== 'yes' ? 'active' : ''}}" id="variations" role="tabpanel" aria-labelledby="profile-tab-fill">
                                                                        @if(auth()->user()->customer->reserved_accounts->count() > 0)
                                                                            <p>To fund your wallet, make payment into any of the accounts below. Your Wallet will be credited automatically. Account number is dedicated to crediting your wallet.<br><br><strong style="color:red">IMPORTANT:</strong><br> Payments made into any of these account are automated. This means that once you transfer, your wallet is credited automatically. <br>
                                                                            P.S: Just like every other transfers, you could experience a slight delay in wallet funding. You only need to hold on patiently as your wallet would be credited once processed. You do not need to contact support after funding your wallet, It is automated. <br>
                                                                            <small style="color:red"><b>NOTE: </b>A charge of <strong>{{ getSettings()->currency }}{{number_format($gateway->reserved_account_payment_charge, 1)}} </strong>is applicable to this method of wallet funding</small>
                                                                            </p>    
                                                                            <div>
                                                                                <h5>Wallet Funding Account Details</h5>
                                                                                <div class="table-responsive mt-2">
                                                                                    <table class="table table-striped">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th style="color:#495463;">Account Name</th>
                                                                                                <th style="color:#495463;">Bank Name</th>
                                                                                                <th style="color:#495463;">Account Number</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @foreach(auth()->user()->customer->reserved_accounts as $account)
                                                                                            @if($account->api_id == ($provider->id ?? $gateway->id))
                                                                                            <tr>
                                                                                                <td style="color:#173D52;">{{$account->account_name}}</td>
                                                                                                <td style="color:#173D52;">{{$account->bank_name}}</td>
                                                                                                <td style="color:#173D52;">{{$account->account_number}}</td>
                                                                                            </tr>
                                                                                            @endif
                                                                                            @endforeach                                                       
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div> 
                                                                        @else
                                                                            <p>
                                                                            <span style="color:red"><b>SORRY!</b></span>
                                                                                No Account Number found, please contact us via on <a target="_blank" href="https://wa.me/{{ getSettings()->whatsapp_number }}?text="{{ urlencode('Hi, I could nor find a reserved account number after completing my KYC verification') }}"> Whatsapp on {{ getSettings()->whatsapp_number }}  </a>to attend to this as soon as possible.
                                                                            </p>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                
                                                {!! getSettings()->google_ad_code !!}

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
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script type="text/javascript" src="https://sdk.monnify.com/plugin/monnify.js"></script>
<script>
    function loadWallet(){
        $.LoadingOverlay("show");
        document.forms["wallet_load"].submit();
    }
       
</script>
@endsection
