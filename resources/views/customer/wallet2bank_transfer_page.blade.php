<?php
    $verifiable = verifiableUniqueElements();
?>
@extends('layouts.app')
@section('title', $category->seo_title ?? getSettings()->seo_title)
@section('keywords', $category->seo_keywords ?? getSettings()->seo_keywords)
@section('description', $category->seo_description ?? getSettings()->seo_description)
@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
    #verify-link{
        text-transform: capitalize;
        text-decoration: underline;
    }

    .footnote {
        margin-top: 5px;
    }
    label {
        font-weight: 500 !important;
        color: black;
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
                                        <div class="col-sm-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h4 class="card-title">Wallet to Bank Transfer</h4>
                                                    @include('layouts.alerts')
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <form action="{{route('initialize.wallet2banktransaction', $product->id)}}" method="POST" onsubmit="return confirm('I have entered correct details');">
                                                            @csrf
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <p></p>
                                                                </div>
                                                                {{-- <div class="col-md-12">
                                                                    @php
                                                                        $bankCharge = (float) env('BANK_TRANSFER_CHARGES');
                                                                        $walletBal  = walletBalance(auth()->user());

                                                                        $minAmount  = 60; // Provider minimum is 50
                                                                        $maxAmount  = max(0, $walletBal - $bankCharge);
                                                                    @endphp
                                                                    <fieldset class="form-group">
                                                                        <label for="amount" class="">Amount to withdraw from wallet</label>
                                                                        <input class="form-control" id="amount" name="amount" placeholder="Enter Amount to transfer" required="" type="number" required min="{{ $minAmount }}" max="{{ $maxAmount }}">
                                                                        

                                                                        <div class="footnote">
                                                                            <small>
                                                                                <strong>
                                                                                    (Minimum amount: {!! getSettings()['currency'] !!}{{ number_format($minAmount) }} |
                                                                                    Maximum amount: {!! getSettings()['currency'] !!}{{ number_format($maxAmount) }})
                                                                                </strong>
                                                                                | <span style="color:red">
                                                                                    Bank charge of {!! getSettings()['currency'] !!}{{ number_format($bankCharge) }} applies
                                                                                </span>
                                                                            </small>
                                                                        </div>

                                                                    </fieldset>
                                                                </div>     --}}
                                                                <div class="col-md-12">
                                                                    @php
                                                                        $bankCharge   = (float) env('BANK_TRANSFER_CHARGES');
                                                                        $providerMin  = 60;
                                                                        $walletBal    = walletBalance(auth()->user());

                                                                        $minAmount = $bankCharge + $providerMin;

                                                                        $maxAmount = max(0, $walletBal);

                                                                        $canWithdraw = $walletBal >= $minAmount;
                                                                    @endphp

                                                                    <fieldset class="form-group">
                                                                        <label for="amount">Amount to withdraw from wallet</label>

                                                                        @if(!$canWithdraw)
                                                                            <div class="alert alert-warning">
                                                                                You do not have sufficient balance to use this service.
                                                                                <br>
                                                                                <small>
                                                                                    Minimum required wallet balance is
                                                                                    <strong>
                                                                                        {!! getSettings()['currency'] !!}
                                                                                        {{ number_format($minAmount) }}
                                                                                    </strong>
                                                                                    so that the provider can receive at least
                                                                                    <strong>
                                                                                        {!! getSettings()['currency'] !!}
                                                                                        {{ number_format($providerMin) }}
                                                                                    </strong>
                                                                                    after bank charges.
                                                                                </small>
                                                                            </div>
                                                                        @else
                                                                            <input
                                                                                class="form-control"
                                                                                id="amount"
                                                                                name="amount"
                                                                                type="number"
                                                                                placeholder="Enter Amount to transfer"
                                                                                required
                                                                                min="{{ $minAmount }}"
                                                                                max="{{ $maxAmount }}"
                                                                            >

                                                                            <div class="footnote">
                                                                                <small>
                                                                                    <strong>
                                                                                        (Minimum amount: {!! getSettings()['currency'] !!}{{ number_format($minAmount) }} |
                                                                                        Maximum amount: {!! getSettings()['currency'] !!}{{ number_format($maxAmount) }})
                                                                                    </strong>
                                                                                    | <span style="color:red">
                                                                                        Bank charge of {!! getSettings()['currency'] !!}{{ number_format($bankCharge) }} applies
                                                                                    </span>
                                                                                </small>
                                                                            </div>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>


                                                                <div class="col-md-12">
                                                                    <fieldset class="form-group">
                                                                        <label for="payment_method">Select Bank </label>
                                                                        <select class="form-control" name="bank" id="bank" required>
                                                                            <option value="">Select</option>
                                                                            @foreach($banks as $bank)
                                                                            <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </fieldset>
                                                                    <fieldset class="form-group">
                                                                        <label for="receive" class="">Account Number</label>
                                                                        <input class="form-control" id="account_number" name="account_number" type="text" maxlength="10" required>
                                                                    </fieldset>
                                                                    <fieldset class="form-group">
                                                                        <label for="receive" class="">Account Name</label>
                                                                        <input class="form-control" id="account_name" name="account_name" type="text" required>
                                                                    </fieldset>
                                                                    <small class="footnote" style="color:red">Please ensure that bank details entered are correct to enable us complete the transaction</small>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <button style="margin-top:4px" class="btn btn-primary" type="submit">PROCEED </button>
                                                                </div>
                                                                
                                                            </div>
                                                        </form>
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
<div class="modal fade" id="verify-modal" data-bs-backdrop="static" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog">
    <form class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="verify-title"></h5>
    </div>
    <div class="modal-body">
        <div id="verify-details">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
    </div>
    </form>
    </div>
</div>
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>

<script>
    function submitForm(){
        var inputs = document.getElementById("initialize").getElementsByTagName("input");
        // Loop through each input and perform validation
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            // Check if the input is required and empty
            if (input.hasAttribute("required") && input.value.trim() === "") {
                alert("The " + input.name + " field is required" );
                return;
            }
        }

        $.LoadingOverlay("show");
        document.forms["initialize"].submit();
    }


    $(document).ready(function () {
        $("#amount").val('');
        $('#product').val('');
        $('#amount-div').hide();
        $('#payment_method').val('');

        $('#product').on('change', function () {
            $('#agreement').prop('checked', false);
            var fixed_price = $('#product').find(':selected').data('fixed_price');
            var system_price = $('#product').find(':selected').data('system_price');
            var discounted_rate = $('#product').find(':selected').data('discounted_rate');
            var max = $('#product').find(':selected').data('max');
            var min = $('#product').find(':selected').data('min');
            var product = $('#product').val();
            var instruction = $('#product').find(':selected').data('instruction');
            $("#amount").val('');
            $('#receive-div').hide()
            $('#payment-div').hide()
            

            if (product == '') {
                $("#amount").val('');
                $('#rate').val('');
                $('#rate-div').hide();
                $("#instruction-div").hide();
                $('#receive').val('');
                $('#payment_method').val('');
                $('#amount-div').hide()

                return;
            }else{
                var image = $('#product').find(':selected').data('image');
                var title = $('#product').find(':selected').data('name');
                var description = $('#product').find(':selected').data('description');
                
                $('#product-image-div').show();
                $("#product-image").attr("src", image);
                $("#product-title").html(title);
                $("#product-description").html(description);
                $("#instruction-div").show();
                $("#rate-div").show();
                $("#rate").val(discounted_rate);
                $("#instruction").html(instruction);
                
                if(min != '' && max != ''){
                    $("#airtime-range").html('The Minimum and Maximum amount for '+title + ' is '+min + ' and ' +max + ' respectively');
                    $("#airtime-range").show();
                }else{
                    $("#airtime-range").hide();
                }
                
                $("#amount").attr({
                    "max": max,
                    "min": min,
                });
                $('#amount-div').show()

            }
        });

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
            var fixed_price = $('#product').find(':selected').data('fixed_price');
        });
        
        $("#amount").keyup(function(){        
            var rate = parseInt($('#rate').val());
            var amount = parseInt($('#amount').val());
            var min = parseInt($('#amount').attr('min')) ?? 50;
            var max = parseInt($('#amount').attr('max'));
            
            var receive = 0;
            if(amount > 0 && amount >= min && amount <= max){
                receive = amount - ((rate/100) * amount);
                $('#receive-div').show();
                $('#receive').val(receive);
                $('#payment-div').show();
            }else{
                $('#receive-div').hide();
                $('#payment-div').hide();
                $('#receive').val('');
            }
        });

    });
</script>

@endsection
