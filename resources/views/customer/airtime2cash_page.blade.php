<?php
    $verifiable = verifiableUniqueElements();
?>
@extends('layouts.app')
@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)
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
                                                    <h4 class="card-title">{{ $category->description }}</h4>
                                                    @include('layouts.alerts')
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <form action="{{route('initialize.airtime2cashtransaction')}}" method="POST"  id="initialize">
                                                            @csrf

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h5>Airtime to Cash</h5>
                                                                    <div class="d-flex pb-1 justify-content-start align-items-center w-100">
                                                                        <img class="product-images product-image" style="padding-right: 8px;height: 70px;" id="product-image" src="" alt="">
                                                                        <div>
                                                                            <h5 id="product-title" style="color:#174159;padding-top: 19px;"><strong></strong>
                                                                            </h5>
                                                                            <p style="" id="product-description" style="line-height: 1.4;"></p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <fieldset class="form-group">
                                                                                <label for="product">Select Network Provider</label>

                                                                                <select class="form-control js-example-basic-single" name="product" id="product" required>
                                                                                    <option value="">Select</option>
                                                                                    @foreach ($category->products as $item)
                                                                                        <option value="{{ $item->id  }}" data-discounted_rate = {{ $item->discounted_rate }} data-allow_quantity="{{ $item->allow_quantity }}" data-min="{{ $item->min}}" data-max="{{$item->max}}" data-system_price="{{ $item->system_price }}" data-fixed_price="{{ $item->fixed_price}}" data-image="{{ asset($item->image) }}" data-name="{{ $item->name }}" data-instruction="{{ $item->instruction }}" data-rate="{{ $item->rate }}" data-description="{{ $item->description }}" {{ old('product') == $item->id ? 'selected' : ''}} {{ old('product') == $item->id ? 'selected' : ''}}>{{ $item->display_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <div class="footnote">
                                                                                    <small>This clearly tells us the network you wish to convert.</small>
                                                                                </div>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12" id="rate-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="rate" class="">Charge Rate (%)</label>
                                                                                <input class="form-control" id="rate" name="rate" required="" type="number" disabled>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <fieldset class="form-group">
                                                                                <label for="email">Email Address</label>
                                                                                <input type="text" class="form-control" id="email" name="email" value="{{ auth()->user()->email ?? old('email')}}" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <fieldset class="form-group">
                                                                                <label for="phone">Phone Number(s)</label>
                                                                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone')}}" required>
                                                                                <div class="footnote">
                                                                                    <small>We need to know the phone number(s) you are sending from so as to confirm when your airtime gets to us. Seperate each number with a coma</small>
                                                                                </div>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <fieldset class="form-group" id="amount-div">
                                                                                <label for="amount" class="">Airtime Amount to sell {!! getSettings()['currency'] !!}</label>
                                                                                <input class="form-control" id="amount" name="amount" placeholder="Enter Amount to sell" required="" type="number" required>
                                                                                <div class="footnote">
                                                                                    <small>Type amount in Naira.</small><small style="color:red" id="airtime-range"></small>
                                                                                </div>
                                                                            </fieldset>
                                                                        </div>
                                                                        
                                                                        <div class="col-md-12" id="receive-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="receive" class="">Amount to receive  {!! getSettings()['currency'] !!}</label>
                                                                                <input class="form-control" id="receive" name="receive" required="" type="number" disabled>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12" id="payment-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="payment_method">Select Payment Method </label>
                                                                                <select class="form-control" name="payment_method" id="payment_method" required>
                                                                                    <option value="">Select</option>
                                                                                    <option value="Transfer to Bank Account">PAYMENT TO MY BANK ACCOUNT</option>
                                                                                    <option value="Transfer to Wallet">PAYMENT TO MY AIRTIME2CASH WALLET</option>
                                                                                </select>
                                                                                <div class="footnote">
                                                                                    <small>Where should your payment go to. If you select bank transfer, please ensure that you have entered your bank account details here</small>
                                                                                </div>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-12" id="bank-details-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="payment_method">Select Bank </label>
                                                                                <select class="form-control" name="bank" id="bank">
                                                                                    <option value="">Select</option>
                                                                                    @foreach($banks as $bank)
                                                                                    <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </fieldset>
                                                                            <fieldset class="form-group">
                                                                                <label for="receive" class="">Account Number</label>
                                                                                <input class="form-control" id="account_number" name="account_number" type="text">
                                                                            </fieldset>
                                                                            <fieldset class="form-group">
                                                                                <label for="receive" class="">Account Name</label>
                                                                                <input class="form-control" id="account_name" name="account_name" type="text">
                                                                            </fieldset>
                                                                            <small class="footnote" style="color:red">Please ensure that bank details entered are correct to enable us complete the transaction</small>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                                    <button id="buy-buttonx"style="margin-top:4px" class="btn btn-primary" type="submit" onclick="submitForm()">PROCEED </button>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <div id="instruction-div" style="display: none">
                                                                        <p style="background-color: rgb(220, 227, 231);padding: 15px;border-radius: 5px;margin-bottom: 15px;color: rgb(40, 83, 107);">Instructions</p>
                                                                        <p id="instruction"></p>
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
                alert("Please fill all inputs");
                return;
            }
        }

        if ($("#payment_method").val() === "") {
            alert("Please fill all inputs");
            return;
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
