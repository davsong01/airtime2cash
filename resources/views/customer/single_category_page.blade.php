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
                                                        <form action="{{route('initialize.transaction')}}" method="POST"  id="initialize">
                                                            @csrf

                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="d-flex pb-1 justify-content-start align-items-center w-100" id="product-image-div" style="display:none !important">
                                                                        <img class="product-images" style="padding-right: 8px;height: 70px;" id="product-image" src="" alt="" class="product-image">
                                                                        <div>
                                                                            <h5 id="product-title" style="color:#174159;padding-top: 19px;"><strong></strong>
                                                                            </h5>
                                                                            <p style="" id="product-description" style="line-height: 1.4;"></p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="product">Select Service</label>

                                                                                <select class="form-control js-example-basic-single" name="product" id="product" required>
                                                                                    <option value="">Select</option>
                                                                                    @foreach ($category->products as $item)
                                                                                        <option value="{{ $item->id  }}" data-allow_subscription_type="{{ $item->allow_subscription_type }}" data-allow_quantity="{{ $item->allow_quantity }}" data-min="{{ $item->min}}" data-max="{{$item->max}}" data-system_price="{{ $item->system_price }}" data-fixed_price="{{ $item->fixed_price}}" data-has_variation="{{$item->has_variations}}" data-image="{{ asset($item->image) }}" data-name="{{ $item->name }}" data-quantity_graduation="{{ $item->quantity_graduation }}" data-description="{{ $item->description }}" {{ old('product') == $item->id ? 'selected' : ''}} {{ old('product') == $item->id ? 'selected' : ''}}>{{ $item->display_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-6" id="variation-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="name" class="">Select Variation </label>
                                                                                <select class="form-control" id="variation" name="variation" required="">
                                                                                    <option value="">Select...</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>

                                                                        @if($category->slug == 'electricity')
                                                                        <div class="col-md-6 unique_element_div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="unique_element">Meter Number  &nbsp;&nbsp;<a href="#" id="verify-link" onclick="verify(this)" class="" type="submit" style="display:none">Verify {{ ucfirst(str_replace("_"," ",$category->unique_element)) }}</a></label>
                                                                                <input type="text" class="form-control" id="unique_element" name="unique_element" value="{{ old('unique_element')}}" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        @elseif($category->slug == 'tv')
                                                                        <div class="col-md-6 unique_element_div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="unique_element">IUC Number &nbsp;&nbsp;<a href="#" id="verify-link" onclick="verify(this)" class="" type="submit" style="display:none">Verify {{ ucfirst(str_replace("_"," ",$category->unique_element)) }}</a></label>
                                                                                <input type="text" class="form-control" id="unique_element" name="unique_element" value="{{ old('unique_element')}}" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        @else
                                                                        <div class="col-md-6 unique_element_div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="unique_element">Profile ID &nbsp;&nbsp;<a href="#" id="verify-link" onclick="verify(this)" class="" type="submit" style="display:none"></a></label>
                                                                                <input type="text" class="form-control" id="unique_element" name="unique_element" value="{{ old('unique_element')}}">
                                                                            </fieldset>
                                                                        </div>
                                                                        @endif
                                                                        <div class="col-md-6" id="bouquet-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="name" class="">Select Subscription Type</label>
                                                                                <select class="form-control" id="bouquet" name="bouquet" required="">
                                                                                    <option value="">Select</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="email">Email Address</label>
                                                                                <input type="text" class="form-control" id="email" name="email" value="{{ auth()->user()->email ?? old('email')}}" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="phone">Phone Number</label>
                                                                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone')}}" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        {{-- <fieldset class="form-group position-relative has-icon-left">
                                                                            <input type="text" class="form-control" id="iconLeft1" placeholder="Icon Left, Default Input">
                                                                            <div class="form-control-position">
                                                                                <i class="bx bx-user"></i>
                                                                            </div>
                                                                        </fieldset> --}}
                                                                        <div class="col-md-6" id="amount-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="amount" class="">Amount {!! getSettings()['currency'] !!}</label>
                                                                                <input class="form-control" id="amount" name="amount" placeholder="Enter Amount" required="" type="number" required>
                                                                            </fieldset>
                                                                            <small><span id="discount" style="display:none;color: red;"></span></small> <br>
                                                                        </div>
                                                                        <div class="col-md-6" id="quantity-div" style="display:none">
                                                                            <fieldset class="form-group">
                                                                                <label for="name" class="">Select quantity </label>
                                                                                <select class="form-control" id="quantity" name="quantity" required="">
                                                                                    <option value="">Select...</option>
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                        @if (auth()->user()->transaction_pin)
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="transaction_pin">Transaction PIN</label><span class="reset-pin"><a href="{{ route('customer.reset.pin') }}"> Reset Transaction Pin</a></span>
                                                                                <input type="password" class="form-control" id="transaction_pin" name="transaction_pin" required>
                                                                            </fieldset>
                                                                        </div>
                                                                        @endif
                                                                    </div>



                                                                  </form>
                                                                    <button id="buy-buttonx"style="margin-top:4px" class="btn btn-primary" type="submit" onclick="submitForm()">Buy now</button>
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
    function verify(e){
        $("#amount").attr({
            "required": true,
        });

        var unique_element = $("#unique_element").val();
        var category_slug = $("#category_slug").val();

        if(unique_element == ''){
            alert("Please enter biller to verify");
            return;
        }

        $.LoadingOverlay("show");
        $('#verify-modal').modal('hide');
        var amount = $('#amount').val();

        var url = "{{ url('customer-verify') }}";
        var element = "{{ $category->unique_element }}";
        var allow_subscription_type = $('#product').find(':selected').data('allow_subscription_type');

        var formData =  {
            category_id: {{ $category->id }},
            unique_element: $("#unique_element").val(),
            variation: $("#variation").val(),
            product_id: $("#product").val(),
        };

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data:formData,

            success: function (data) {
                $.LoadingOverlay("hide");
                $("#verify-title").html(data.title);
                $("#verify-details").html(data.message);

                if(data.status == '1'){
                    if(allow_subscription_type == 'yes' && element == 'iuc_number'){
                        $('#bouquet').append(`<option value="change" data-amount="${amount}">Change Bouquet</option><option data-amount="${data.renewal_amount}" value="renew">Renew Bouquet</option>`);
                        $("#bouquet-div").show();
                        $("#bouquet").attr({
                            "required":true
                        });
                    }else{
                        $("#bouquet-div").hide();
                        $("#bouquet").attr({
                            "required":false
                        });
                    }

                    $("#continue_payment").show();

                }else{
                    $("#continue_payment").hide();
                }
                $('#verify-modal').modal('show');
            }
        });
    }

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

        $.LoadingOverlay("show");
        document.forms["initialize"].submit();
    }

    $("#amount").keyup(function(){        
        var has_variation = $('#product').find(':selected').data('has_variation');
        var product_id = $('#product').val();
        var variation_id = $('#variation').val();
        var amount = $('#amount').val();
       
        if(amount > 0){
            if(has_variation == 'yes'){
                formData = {
                    variation_id:variation_id,
                    amount:amount
                };
            }else{
                formData = {
                    product_id:product_id,
                    amount:amount
                };
            }
            
            $.ajax({
                url: "{{ url('customer-get-discount') }}",
                method: 'POST',
                dataType: 'json',
                data:formData,
                success: function (data) {
                    if(data.discount > 0){
                        $('#discount').show();
                        var amount = $('#discount').html(data.message);
                    }
                }
            });
        }else{
            $('#discount').hide()
        }
    });

    $(document).ready(function () {
        $("#amount").val('');
        $('#product').val('');
                
        var variations = [];

        $('#product').on('change', function () {
            var fixed_price = $('#product').find(':selected').data('fixed_price');
            var has_variation= $('#product').find(':selected').data('has_variation');
            var system_price = $('#product').find(':selected').data('system_price');
            var allow_quantity = $('#product').find(':selected').data('allow_quantity');
            var max = $('#product').find(':selected').data('max');
            var min = $('#product').find(':selected').data('min');
            var quantity_graduation = $('#product').find(':selected').data('quantity_graduation');
            var product = $('#product').val();

            $("#verify-link").hide();
            $(".unique_element_div").hide();

            if (product == '') {
                $('#variation-div').hide();
                $('#amount-div').hide();
                $('#quantity-div').hide();
                $('#amount').hide();
                $('#quantity').hide();
                $('#product-image-div').hide();

                return;
            }

            var image = $('#product').find(':selected').data('image');
            var title = $('#product').find(':selected').data('name');
            var description = $('#product').find(':selected').data('description');
            var bulk = $('#product').find(':selected').data('bulk');
            if (bulk == 'yes') {
                $("#bulk-purchase").show();
            } else {
                $("#bulk-purchase").hide();
            }

            $('#product-image-div').show();
            $("#product-image").attr("src", image);
            $("#product-title").html(title);
            $("#product-description").html(description);

            if(has_variation == 'yes'){
                $('#variation-div').show();
                $('#variation').show();
                $('#amount-div').hide();

                $("#amount").prop('readonly', false);
                $("#amount").val('');

                $('#variation').find('option').not(':first').remove();

                $.ajax({
                    url: "{{ url('customer-get-variations') }}/" + product,
                    success: function (data) {
                        if (data && data.length > 0) {
                            for (t = 0; t <= data.length; t++) {
                                $('#variation').append(`<option value="${data[t].id}" data-min="${data[t].min}" data-max="${data[t].max}" data-isFixed="${data[t].fixed_price}" data-amount="${data[t].system_price}"> ${data[t].system_name}</option>`);

                                variations.push({
                                    "id": data[t].id,
                                    "verifiable": data[t].verifiable,
                                    "unique_element": data[t].unique_element,
                                    "max": data[t].max,
                                    "min": data[t].min,
                                    "fixedPrice": data[t].fixed_price,
                                    "variation_amount": data[t].system_price,
                                    "discount": data[t].discount.discount > 0 ? data[t].discount.message : '',
                                    "discount_rate":data[t].discount.discount
                                });
                            }
                        }
                    }
                });
            }else{
                $('#amount-div').show();
                $('#amount').show();
                $('#quantity option:not(:first)').remove();
                $("#variation").hide();
                $("#variation-div").hide();

                if(fixed_price == 'yes'){
                    $("#amount").attr({
                        "max": "",
                        "min": ""
                    });

                    $('#amount').val(system_price);
                    $("#amount").attr({
                        "readonly": "true",
                    });
                }else{
                    $("#amount").prop('readonly', false);
                    $("#amount").attr({
                        "max": max,
                        "min": min,
                    });
                }
            }

            if(allow_quantity == 'yes'){
                $('#quantity-div').show();
                $('#quantity').show();
                var data = quantity_graduation.split(",");

                if (data && data.length > 0) {
                    for (t = 0; t < data.length; t++) {
                        $('#quantity').append(`<option value="${data[t]}"> ${data[t]}</option>`);
                    }
                }
            }else{
                $('#quantity-div').hide();
                $('#quantity').hide();
            }

        });

        $('#bouquet').on('change', function (e) {
            var amount = $('#bouquet').find(':selected').data('amount');
            var old_amount = $('#amount').val();
            if($('#bouquet') == 'change'){
                $('#amount').val(old_amount);
            }else{
                $('#amount').val(amount);
            }
        });

        $('#variation').on('change', function (e) {
            $('#amount-div').show();
            $('#amount').show();
            $("#bouquet-div").hide();
            $("#bouquet").attr({
                "required":false
            });
            // $("#unique_element").hide();

            var v = e.target.value;
            var selected = variations.filter((item) => {
                return item.id == v;
            });
            if (selected[0].verifiable == 'yes') {
                showAllUniqueElement();
                $("#verify-link").html('Verify '+ selected[0].unique_element.replace("_", " "));
                if (selected[0].unique_element == 'profile_id') {
                    $("#profile_id").show();
                    $("#unique_element").show();
                }
            }else{
                hideAllUniqueElement();
            }
            var currency = "{!! getSettings()['currency'] !!}";
            if (selected[0].discount_rate > 0) {
                if(selected[0].fixedPrice == 'Yes'){
                    $("#discount").html(selected[0].discount);
                    $("#discount").show();
                }
            }else{
                $("#discount").html("");
                $("#discount").hide();
            }
            if (selected[0].fixedPrice == 'Yes') {
                $("#amount").attr({
                    "max": "",
                    "min": ""
                });

                $('#amount').val(selected[0].variation_amount);
                $("#amount").attr({
                    "readonly": "true",
                });

            } else {
                $("#amount").prop('readonly', false);
                $("#amount").attr({
                    "max": selected[0].max,
                    "min": selected[0].min,
                });
            }
        });

        function showAllUniqueElement(){
            $("#verify-link").show();
            $(".unique_element_div").show();
            $("#unique_element").attr({
                "required": true,
            });
        }

        function hideAllUniqueElement(){
            $("#profile_id").hide();
            $("#verify-link").hide();
            $(".unique_element_div").hide();
            $("#buy-button").show();
            $("#unique_element").attr({
                "required": false,
            });
        }

        $('.select2').select2();

        
    });
</script>

@endsection
