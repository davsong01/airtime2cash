<?php
    $verifiable = verifiableUniqueElements();
?>
@extends('sneat.layouts.app')
@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)
@section('page-css')
    <style>
        .conversion-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 48%, #0f766e 100%);
            color: #fff;
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 24px;
        }

        .conversion-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.92);
        }

        .mode-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .mode-option {
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #fff;
            padding: 20px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .mode-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }

        .mode-option.active {
            border-color: #16a34a;
            box-shadow: 0 14px 28px rgba(22, 163, 74, 0.14);
            background: #f0fdf4;
        }

        .network-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
        }

        .network-tile {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 20px;
            padding: 18px 14px;
            background: #fff;
            text-align: center;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .network-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        }

        .panel-label {
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #64748b;
            font-size: .78rem;
            margin-bottom: .5rem;
        }

        .section-title {
            font-size: 1.35rem;
            margin-bottom: .25rem;
            color: #0f172a;
        }
    </style>
@endsection
@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="conversion-hero">
                <div class="panel-label text-white-50">Airtime conversion</div>
                <h1 class="section-title text-white mb-2">Convert Airtime</h1>
                <p class="mb-0" style="color: rgba(255, 255, 255, 0.75);">Choose between manual transfer and auto transfer, then complete the request with a simpler flow.</p>
            </div>

            @include('layouts.alerts')

            <div class="row g-4">
                <div class="col-12">
                    <div class="conversion-card card">
                        <div class="card-body">
                            <div class="panel-label">Mode</div>
                            <div class="mode-grid mb-3">
                                <div class="mode-option active">
                                    <div class="fw-bold fs-5">Manual Transfer</div>
                                    <div class="text-muted">Send airtime to our number</div>
                                </div>
                                <div class="mode-option">
                                    <div class="fw-bold fs-5">Auto Transfer</div>
                                    <div class="text-muted">Instant airtime moved automatically</div>
                                </div>
                            </div>

                            <form action="{{route('initialize.airtime2cashtransaction')}}" method="POST" id="initialize">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        <div class="card border-0 bg-light-subtle mb-4">
                                            <div class="card-body">
                                                <div class="panel-label">Select network</div>
                                                <div class="network-grid">
                                                    @foreach ($category->products as $item)
                                                        <label class="network-tile">
                                                            <input type="radio" class="form-check-input mb-3" name="product" value="{{ $item->id }}" data-discounted_rate="{{ $item->discounted_rate }}" data-allow_quantity="{{ $item->allow_quantity }}" data-min="{{ $item->min}}" data-max="{{ $item->max}}" data-system_price="{{ $item->system_price }}" data-fixed_price="{{ $item->fixed_price}}" data-image="{{ asset($item->image) }}" data-name="{{ $item->name }}" data-instruction="{{ $item->instruction }}" data-rate="{{ $item->rate }}" data-description="{{ $item->description }}" {{ old('product') == $item->id ? 'checked' : '' }}>
                                                            <div class="fw-bold">{{ $item->display_name }}</div>
                                                            <div class="small text-success">{{ $item->discounted_rate }}%</div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="amount" class="form-label">Airtime Amount ({{ getSettings()['currency'] }})</label>
                                                        <input class="form-control form-control-lg" id="amount" name="amount" placeholder="Enter amount" required type="number">
                                                        <div class="form-text">Type the airtime value you are sending.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="phone" class="form-label">Phone Number(s) Sending Airtime From</label>
                                                        <input type="text" class="form-control form-control-lg" id="phone" name="phone" value="{{ old('phone')}}" required>
                                                        <div class="form-text">Separate multiple numbers with a comma.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="email" class="form-label">Email Address</label>
                                                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ auth()->user()->email ?? old('email')}}" required>
                                                    </div>
                                                    <div class="col-md-6" id="rate-div" style="display:none">
                                                        <label for="rate" class="form-label">Charge Rate (%)</label>
                                                        <input class="form-control form-control-lg" id="rate" name="rate" required type="number" disabled>
                                                    </div>
                                                    <div class="col-md-6" id="receive-div" style="display:none">
                                                        <label for="receive" class="form-label">Amount to receive ({{ getSettings()['currency'] }})</label>
                                                        <input class="form-control form-control-lg" id="receive" name="receive" required type="number" disabled>
                                                    </div>
                                                    <div class="col-12" id="amount-div" style="display:none">
                                                        <small class="text-muted">Minimum and maximum values will update after a network is selected.</small>
                                                        <div class="text-danger" id="airtime-range"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4" id="payment-div" style="display:none">
                                            <div class="card-body">
                                                <div class="panel-label">Payment method</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="mode-option active">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="payment_method" id="payment_wallet" value="Transfer to Wallet">
                                                                <label class="form-check-label fw-semibold" for="payment_wallet">Transfer to Wallet</label>
                                                            </div>
                                                            <div class="text-muted small">Receive in your airtime2cash wallet balance</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mode-option">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="Transfer to Bank Account">
                                                                <label class="form-check-label fw-semibold" for="payment_bank">Transfer to Bank Account</label>
                                                            </div>
                                                            <div class="text-muted small">Direct transfer to your bank</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row g-3 mt-3" id="bank-details-div" style="display:none">
                                                    <div class="col-md-4">
                                                        <label for="bank" class="form-label">Select Bank</label>
                                                        <select class="form-select" name="bank" id="bank">
                                                            <option value="">Select</option>
                                                            @foreach($banks as $bank)
                                                                <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="account_number" class="form-label">Account Number</label>
                                                        <input class="form-control" id="account_number" name="account_number" type="text">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="account_name" class="form-label">Account Name</label>
                                                        <input class="form-control" id="account_name" name="account_name" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button id="buy-buttonx" class="btn btn-success btn-lg w-100" type="submit" onclick="submitForm()">
                                            Submit Conversion
                                        </button>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div id="product-image-div" style="display:none">
                                                    <img class="img-fluid rounded-4 mb-3" id="product-image" src="" alt="">
                                                    <h5 id="product-title" class="mb-2"></h5>
                                                    <p id="product-description" class="text-muted"></p>
                                                </div>
                                                <div id="instruction-div" style="display:none">
                                                    <div class="panel-label">Instructions</div>
                                                    <p id="instruction" class="mb-3"></p>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="agreement" name="agreement" required>
                                                        <label class="form-check-label" for="agreement">I have read and agree to these instructions</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="verify-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verify-title"></h5>
            </div>
            <div class="modal-body">
                <div id="verify-details"></div>
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
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.hasAttribute("required") && input.value.trim() === "") {
                alert("The " + input.name + " field is required" );
                return;
            }
        }

        if ($('#agreement').prop('checked') == false){
            return alert('You must agree to the instructions');
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
        $('#amount-div').hide();
        $('#payment_method').val('');

        $('input[name="product"]').on('change', function () {
            $('#agreement').prop('checked', false);
            var fixed_price = $(this).data('fixed_price');
            var discounted_rate = $(this).data('discounted_rate');
            var max = $(this).data('max');
            var min = $(this).data('min');
            var instruction = $(this).data('instruction');
            $("#amount").val('');
            $('#receive-div').hide();
            $('#payment-div').hide();

            if (!this.value) {
                $("#rate").val('');
                $('#rate-div').hide();
                $("#instruction-div").hide();
                $('#receive').val('');
                $('#amount-div').hide();
                $('#product-image-div').hide();
                return;
            }

            var image = $(this).data('image');
            var title = $(this).data('name');
            var description = $(this).data('description');

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
            $('#amount-div').show();
        });

        $('input[name="payment_method"]').on('change', function () {
            if($(this).val() == 'Transfer to Bank Account'){
                $('#bank-details-div').show();
                $("#bank, #account_number, #account_name").attr('required', '');
            }else{
                $('#bank-details-div').hide();
                $("#bank, #account_number, #account_name").removeAttr('required');
            }
        });

        $("#amount").keyup(function(){
            var rate = parseInt($('#rate').val()) || 0;
            var amount = parseInt($('#amount').val()) || 0;
            var min = parseInt($('#amount').attr('min')) || 50;
            var max = parseInt($('#amount').attr('max')) || 0;

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
