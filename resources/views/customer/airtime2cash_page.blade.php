<?php
    $verifiable = verifiableUniqueElements();
    $manualProducts = $category->products->where('manual_status', 'active')->count();
    $autoShareProducts = $category->products->where('auto_share_status', 'active')->count();
    $defaultTransferMode = old('transfer_mode', $manualProducts ? 'manual' : 'auto_share');
    $autoTransferInstruction = $category->products->firstWhere('auto_share_status', 'active')?->auto_share_instruction;
    $activeProviderAvailability = $activeProvider?->availability_status_class;
    $activeProviderAvailabilityLabel = $activeProvider?->availability_status_label;
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
    .legacy-conversion-mode { display: flex; min-height: 82px; padding: 14px; align-items: center; gap: 10px; border: 1px solid #dfe5e8; border-radius: 8px; cursor: pointer; }
    .legacy-conversion-mode strong, .legacy-conversion-mode small { display: block; }
    .legacy-conversion-mode small { margin-top: 3px; color: #828d99; }
    .legacy-conversion-mode i { color: #168a67; font-size: 22px !important; }
    .legacy-mode-input:checked + .legacy-conversion-mode { border-color: #168a67; background: #f1faf7; box-shadow: 0 0 0 2px rgba(22,138,103,.1); }
    .legacy-mode-input:disabled + .legacy-conversion-mode { opacity: .5; cursor: not-allowed; }
    #initialize { padding: 1.25rem; border: 1px solid #e4e9ed; border-radius: 14px; background: rgba(255,255,255,.96); box-shadow: 0 18px 42px rgba(23,65,89,.09); }
    .legacy-auto-flow { padding: 1.5rem; border: 1px solid #dce9e4; border-radius: 14px; background: linear-gradient(145deg, #fff, #f2faf7); box-shadow: 0 16px 34px rgba(23,65,89,.1); }
    .legacy-auto-mark { display:flex; width:52px; height:52px; margin:0 auto 1rem; align-items:center; justify-content:center; border-radius:16px; color:#fff; background:linear-gradient(145deg,#168a67,#0f6c53); font-size:25px; }
    .legacy-auto-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:.65rem; margin:1rem 0; }
    .legacy-auto-summary span { padding:.75rem; border:1px solid #e2e9e6; border-radius:9px; background:#fff; }
    .legacy-auto-summary small, .legacy-auto-summary strong { display:block; }
    .legacy-secret-input { height:50px; text-align:center; font-size:18px; font-weight:700; letter-spacing:.25em; }
    .provider-health-strip { display:flex; margin-bottom:1rem; padding:.9rem 1rem; align-items:center; justify-content:space-between; gap:1rem; border:1px solid rgba(148,163,184,.18); border-radius:14px; background:linear-gradient(145deg,#fff, #f8fafc); }
    .provider-health-strip strong, .provider-health-strip small { display:block; }
    .provider-health-kicker { display:inline-flex; margin-bottom:.2rem; color:#64748b; font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .provider-health-strip small { color:#64748b; }
    .provider-health-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .7rem; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; white-space:nowrap; }
    .provider-health-badge.unstable { color:#991b1b; background:rgba(239,68,68,.14); }
    .provider-health-badge.degraded { color:#111827; background:rgba(17,24,39,.12); }
    .provider-health-badge.stable { color:#9a6700; background:rgba(245,158,11,.15); }
    .provider-health-badge.healthy { color:#166534; background:rgba(34,197,94,.14); }
    [data-bs-theme="dark"] .provider-health-strip { border-color:rgba(148,163,184,.24); background:rgba(43,44,64,.94); }
    [data-bs-theme="dark"] .provider-health-kicker, [data-bs-theme="dark"] .provider-health-strip small { color:#cbd5e1; }
    [data-bs-theme="dark"] .provider-health-badge.degraded { color:#e5e7eb; background:rgba(75,85,99,.4); }
    @media(max-width:767px) { .legacy-auto-summary { grid-template-columns:1fr; } #initialize { padding:.8rem; } }
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
                                                                <div class="col-md-6 order-2 order-sm-1">
                                                                    <h5>Airtime to Cash</h5>
                                                                        @if($activeProvider && $activeProviderAvailability && $activeProvider?->availability_checked_at)
                                                                            <div class="provider-health-strip">
                                                                                <div>
                                                                                    <span class="provider-health-kicker">Auto Transfer Status</span>
                                                                                    <small>Checked {{ $activeProvider->availability_checked_at->diffForHumans() }}</small>
                                                                                    @if($activeProviderAvailability === 'unstable')
                                                                                        <small class="text-danger mt-1">Auto transfer looks unstable right now, so manual processing may be the safer option.</small>
                                                                                    @endif
                                                                                </div>
                                                                                <span class="provider-health-badge {{ $activeProviderAvailability }}">
                                                                                    <i class="bx bx-pulse"></i>
                                                                                    {{ $activeProviderAvailabilityLabel }}
                                                                                </span>
                                                                            </div>
                                                                        @endif
                                                                        <div class="d-flex pb-1 justify-content-start align-items-center w-100" id="product-image-div" style="display:none !important">
                                                                            <img class="product-images product-image" style="padding-right: 8px;height: 70px;" id="product-image" src="" alt="">
                                                                            <div>
                                                                                <h5 id="product-title" style="color:#174159;padding-top: 19px;"><strong></strong>
                                                                                </h5>
                                                                                <p style="" id="product-description" style="line-height: 1.4;"></p>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-12 mb-1">
                                                                                <label>Transfer Method</label>
                                                                                <div class="row">
                                                                                    <div class="col-md-6 mb-1">
                                                                                        <input class="legacy-mode-input d-none" type="radio" name="transfer_mode" id="legacy-transfer-manual" value="manual" @checked($defaultTransferMode === 'manual') @disabled(!$manualProducts)>
                                                                                        <label class="legacy-conversion-mode" for="legacy-transfer-manual"><i class="bx bx-hand"></i><span><strong>Manual Transfer</strong><small>Send airtime manually to our number</small></span></label>
                                                                                    </div>
                                                                                    <div class="col-md-6 mb-1">
                                                                                        <input class="legacy-mode-input d-none" type="radio" name="transfer_mode" id="legacy-transfer-auto" value="auto_share" @checked($defaultTransferMode === 'auto_share') @disabled(!$autoShareProducts)>
                                                                                        <label class="legacy-conversion-mode" for="legacy-transfer-auto"><i class="bx bx-bolt-circle"></i><span><strong>Auto Transfer</strong><small>Airtime moved through Auto Share</small></span></label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <fieldset class="form-group">
                                                                                    <label for="product">Select Network Provider</label>
                                                                                    <select class="form-control js-example-basic-single" name="product" id="product" required>
                                                                                        <option value="">Select</option>
                                                                                        @foreach ($category->products as $item)
                                                                                            <option value="{{ $item->id }}" data-manual_status="{{ $item->manual_status }}" data-auto_share_status="{{ $item->auto_share_status }}" data-manual_rate="{{ $item->manual_discounted_rate }}" data-auto_share_rate="{{ $item->auto_share_discounted_rate }}" data-allow_quantity="{{ $item->allow_quantity }}" data-min="{{ $item->min}}" data-max="{{$item->max}}" data-system_price="{{ $item->system_price }}" data-fixed_price="{{ $item->fixed_price}}" data-image="{{ asset($item->image) }}" data-name="{{ $item->name }}" data-manual_instruction="{{ $item->instruction }}" data-auto_share_instruction="{{ $item->auto_share_instruction }}" data-description="{{ $item->description }}">{{ $item->display_name }}</option>
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

                                                                    <div class="form-group mb-50 mt-2">
                                                                        <div class="checkbox checkbox-success checkbox-glow">
                                                                            <input type="checkbox" id="agreement" name="agreement" required>
                                                                            <label for="agreement"><p>I have read and agree to the instructions</p></label>
                                                                        </div>
                                                                    </div>
                                                                    <button id="buy-buttonx" style="margin-top:4px" class="btn btn-primary" type="submit" onclick="return submitForm()">PROCEED</button>
                                                                </div>
                                                                <div class="col-md-6 order-1 order-sm-2">
                                                                    <div id="instruction-div" style="display: none">
                                                                        <p style="background-color: rgb(220, 227, 231);padding: 15px;border-radius: 5px;margin-bottom: 15px;color: rgb(40, 83, 107);">Instructions</p>
                                                                        <p id="instruction"></p>
                                                                    </div>
                                                                    <div id="legacy-auto-flow" class="legacy-auto-flow" style="display:none">
                                                                        <div class="alert alert-danger" id="legacy-auto-error" style="display:none"></div>
                                                                        <div id="legacy-pin-stage">
                                                                            <span class="legacy-auto-mark"><i class="bx bx-lock-alt"></i></span>
                                                                            <h3 class="text-center">Enter SIM PIN</h3>
                                                                            <p class="text-center text-muted"><strong id="legacy-pin-phone"></strong><br>Enter your airtime share PIN to authorize the transfer.</p>
                                                                            <div class="legacy-auto-summary"><span><small>Network</small><strong id="legacy-summary-network">-</strong></span><span><small>Airtime</small><strong id="legacy-summary-amount">-</strong></span><span><small>To wallet</small><strong id="legacy-summary-payout">-</strong></span></div>
                                                                            <label for="legacy-share-pin">Airtime Share PIN</label>
                                                                            <input type="password" class="form-control legacy-secret-input" id="legacy-share-pin" inputmode="numeric" maxlength="8" autocomplete="off" placeholder="----">
                                                                            <small class="d-block text-center mt-50 text-muted">The PIN you use to share or transfer airtime from your SIM.</small>
                                                                            <button type="button" class="btn btn-link px-0 mt-1" id="legacy-edit-details"><i class="bx bx-left-arrow-alt"></i> Edit conversion details</button>
                                                                        </div>
                                                                        <div id="legacy-otp-stage" style="display:none">
                                                                            <span class="legacy-auto-mark"><i class="bx bx-message-rounded-dots"></i></span>
                                                                            <h3 class="text-center">Enter OTP</h3>
                                                                            <p class="text-center text-muted">OTP sent to <strong id="legacy-otp-phone"></strong><br>Enter the OTP to authorize the airtime transfer.</p>
                                                                            <label for="legacy-auto-otp">OTP Code</label>
                                                                            <input type="text" class="form-control legacy-secret-input" id="legacy-auto-otp" inputmode="numeric" maxlength="10" autocomplete="one-time-code" placeholder="------">
                                                                            <div class="text-center mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" id="legacy-resend-otp"><i class="bx bx-refresh"></i> Resend OTP</button></div>
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
        return window.handleLegacyAirtimeToCashSubmit ? window.handleLegacyAirtimeToCashSubmit() : false;
    }


    $(document).ready(function () {
        var productSelect = $('#product');
        var allProductOptions = productSelect.find('option').clone();
        var defaultAutoInstruction = @json($autoTransferInstruction);
        var currency = @json(html_entity_decode(strip_tags(getSettings()['currency'])));
        var csrfToken = @json(csrf_token());
        var autoUrls = {
            initiate: @json(route('airtime2cash.auto.initiate')),
            complete: @json(route('airtime2cash.auto.complete')),
            resend: @json(route('airtime2cash.auto.resend-otp'))
        };
        var autoStage = 'details';
        var autoTransactionId = null;

        function isAutoTransfer() { return $('input[name="transfer_mode"]:checked').val() === 'auto_share'; }
        function setLegacyButton(label, disabled) { $('#buy-buttonx').text(label).prop('disabled', disabled); }
        function showLegacyError(message) { $('#legacy-auto-error').text(message).toggle(Boolean(message)); }
        function firstLegacyError(data) {
            if (data.errors) {
                var errors = Object.values(data.errors).reduce(function (all, item) { return all.concat(item); }, []);
                if (errors.length) return errors[0];
            }
            return data.message || 'The request could not be completed. Please try again.';
        }
        function postLegacyAuto(url, payload) {
            return fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok) throw new Error(firstLegacyError(data));
                    return data;
                });
            });
        }
        function legacyAutoPayload() {
            return {
                product: $('#product').val(), transfer_mode: 'auto_share', amount: $('#amount').val(),
                phone: $('#phone').val().replace(/\s+/g, ''), email: $('#email').val(),
                payment_method: 'Transfer to Wallet', agreement: $('#agreement').prop('checked') ? 1 : 0
            };
        }
        function validateLegacyDetails() {
            if (isAutoTransfer()) $('#payment_method').val('Transfer to Wallet').trigger('change');
            if (!document.getElementById('initialize').reportValidity()) return false;
            if (!$('#agreement').prop('checked')) { alert('You must agree to the transfer instructions'); return false; }
            return true;
        }
        function openLegacyPin() {
            var selected = productSelect.find(':selected');
            $('#legacy-pin-phone, #legacy-otp-phone').text($('#phone').val());
            $('#legacy-summary-network').text(selected.data('name'));
            $('#legacy-summary-amount').text(currency + Number($('#amount').val()).toLocaleString());
            $('#legacy-summary-payout').text(currency + Number($('#receive').val()).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
            $('#instruction-div').hide();
            $('#legacy-auto-flow, #legacy-pin-stage').show();
            $('#legacy-otp-stage').hide();
            $('#legacy-share-pin, #legacy-auto-otp').val('');
            autoStage = 'pin';
            showLegacyError('');
            setLegacyButton('SUBMIT PIN AND ENTER OTP', true);
            document.getElementById('legacy-auto-flow').scrollIntoView({behavior:'smooth', block:'center'});
        }
        function openLegacyOtp(data) {
            autoTransactionId = data.transaction_id;
            autoStage = 'otp';
            $('#legacy-pin-stage').hide();
            $('#legacy-otp-stage').show();
            $('#legacy-otp-phone').text(data.phone || $('#phone').val());
            setLegacyButton('SHARE AIRTIME', true);
            $('#legacy-auto-otp').focus();
        }
        window.handleLegacyAirtimeToCashSubmit = function () {
            if (!isAutoTransfer()) {
                if (!validateLegacyDetails()) return false;
                setLegacyButton('PROCESSING...', true);
                $.LoadingOverlay('show');
                document.getElementById('initialize').submit();
                return false;
            }
            if (autoStage === 'details') { if (validateLegacyDetails()) openLegacyPin(); return false; }
            if (autoStage === 'pin') {
                var pin = $('#legacy-share-pin').val();
                if (!/^\d{4,8}$/.test(pin)) return false;
                setLegacyButton('SENDING OTP...', true); showLegacyError('');
                postLegacyAuto(autoUrls.initiate, Object.assign(legacyAutoPayload(), {share_pin: pin}))
                    .then(openLegacyOtp).catch(function (error) { showLegacyError(error.message); setLegacyButton('SUBMIT PIN AND ENTER OTP', false); });
                return false;
            }
            var otp = $('#legacy-auto-otp').val();
            if (!/^\d{4,10}$/.test(otp)) return false;
            setLegacyButton('SHARING AIRTIME...', true); showLegacyError('');
            postLegacyAuto(autoUrls.complete, {transaction_id:autoTransactionId, otp:otp})
                .then(function (data) { window.location.href = data.redirect; })
                .catch(function (error) { showLegacyError(error.message); setLegacyButton('SHARE AIRTIME', false); });
            return false;
        };
        $('#legacy-share-pin').on('input', function () { this.value=this.value.replace(/\D/g,'').slice(0,8); if(autoStage==='pin') setLegacyButton('SUBMIT PIN AND ENTER OTP', !/^\d{4,8}$/.test(this.value)); });
        $('#legacy-auto-otp').on('input', function () { this.value=this.value.replace(/\D/g,'').slice(0,10); if(autoStage==='otp') setLegacyButton('SHARE AIRTIME', !/^\d{4,10}$/.test(this.value)); });
        $('#legacy-edit-details').on('click', function () { autoStage='details'; $('#legacy-auto-flow').hide(); showInstruction(defaultAutoInstruction); setLegacyButton('INITIATE AUTO TRANSFER', false); });
        $('#legacy-resend-otp').on('click', function () {
            var button=$(this).prop('disabled',true).text('Sending...'); showLegacyError('');
            postLegacyAuto(autoUrls.resend,{transaction_id:autoTransactionId})
                .then(function(){button.text('OTP sent');setTimeout(function(){button.prop('disabled',false).html('<i class="bx bx-refresh"></i> Resend OTP');},2500);})
                .catch(function(error){showLegacyError(error.message);button.prop('disabled',false).html('<i class="bx bx-refresh"></i> Resend OTP');});
        });

        function showInstruction(instruction) {
            if (!instruction) {
                $('#instruction-div').hide();
                return;
            }

            $('#instruction').html(instruction);
            $('#instruction-div').show();
        }

        function refreshNetworks() {
            var transferMode = $('input[name="transfer_mode"]:checked').val();
            var statusKey = transferMode === 'auto_share' ? 'auto_share_status' : 'manual_status';

            productSelect.empty();
            allProductOptions.each(function (index) {
                var option = $(this);
                if (index === 0 || option.data(statusKey) === 'active') {
                    productSelect.append(option.clone());
                }
            });

            productSelect.val('').trigger('change');
        }

        $('input[name="transfer_mode"]').on('change', function () {
            var auto = this.value === 'auto_share';
            autoStage = 'details'; autoTransactionId = null;
            $('#legacy-auto-flow').hide();
            $('#payment_method option[value="Transfer to Bank Account"]').prop('disabled', auto);
            setLegacyButton(auto ? 'INITIATE AUTO TRANSFER' : 'PROCEED', false);
            refreshNetworks();
        });
        $("#amount").val('');
        $('#amount-div').hide();
        $('#payment_method').val('');

        $('#product').on('change', function () {
            $('#agreement').prop('checked', false);
            var fixed_price = $('#product').find(':selected').data('fixed_price');
            var system_price = $('#product').find(':selected').data('system_price');
            var transferMode = $('input[name="transfer_mode"]:checked').val();
            var discounted_rate = transferMode === 'auto_share'
                ? $('#product').find(':selected').data('auto_share_rate')
                : $('#product').find(':selected').data('manual_rate');
            var max = $('#product').find(':selected').data('max');
            var min = $('#product').find(':selected').data('min');
            var product = $('#product').val();
            var instruction = transferMode === 'auto_share'
                ? $('#product').find(':selected').data('auto_share_instruction') || defaultAutoInstruction
                : $('#product').find(':selected').data('manual_instruction');
            $("#amount").val('');
            $('#receive-div').hide()
            $('#payment-div').hide()


            if (product == '') {
                $("#amount").val('');
                $('#rate').val('');
                $('#rate-div').hide();
                showInstruction(transferMode === 'auto_share' ? defaultAutoInstruction : null);
                $('#receive').val('');
                $('#payment_method').val('');
                $('#amount-div').hide()
                $('#product-image-div').hide();

                return;
            }else{
                var image = $('#product').find(':selected').data('image');
                var title = $('#product').find(':selected').data('name');
                var description = $('#product').find(':selected').data('description');

                $('#product-image-div').show();
                $("#product-image").attr("src", image);
                $("#product-title").html(title);
                $("#product-description").html(description);
                showInstruction(instruction);
                $("#rate-div").show();
                $("#rate").val(discounted_rate);

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

        $('input[name="transfer_mode"]:checked').trigger('change');

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
                if (isAutoTransfer()) $('#payment_method').val('Transfer to Wallet').trigger('change');
            }else{
                $('#receive-div').hide();
                $('#payment-div').hide();
                $('#receive').val('');
            }
        });

    });
</script>

@endsection
