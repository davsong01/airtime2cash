<?php 
use App\Models\PaymentGateway;
?>
@extends('layouts.app')
@section('page-css')
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
                                                        <h4 class="card-title">Settings</h4>
                                                        @include('layouts.alerts')
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <form action="{{route('settings.update')}}" method="POST" enctype="multipart/form-data">
                                                                @csrf
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="official_email">Official Email</label>
                                                                            <input type="text" class="form-control" id="official_email" name="official_email" value="{{ $settings->official_email ?? old('official_email') }}" placeholder="Official email">
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="whatsapp_number">Whatsapp Number</label>
                                                                            <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ $settings->whatsapp_number ?? old('whatsapp_number') }}" placeholder="Whatsapp number">
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="login_email_notification">Customer Login Email Notification</label>
                                                                            <select name="login_email_notification" class="form-control" id="login_email_notification" required>
                                                                                <option value="">Select</option>
                                                                                <option value="yes" {{ $settings->login_email_notification == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                <option value="no" {{ $settings->login_email_notification == 'no' ? 'selected' : ''}}>No</option>
                                                                                
                                                                            </select>
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="transaction_email_notification">Transaction Email Notification</label>
                                                                            <select name="transaction_email_notification" class="form-control" id="transaction_email_notification" required>
                                                                                <option value="">Select</option>
                                                                                <option value="yes" {{ $settings->transaction_email_notification == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                <option value="no" {{ $settings->transaction_email_notification == 'no' ? 'selected' : ''}}>No</option>
                                                                                
                                                                            </select>
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="currency">Currency</label>
                                                                            <select name="currency" class="form-control" id="currency" required>
                                                                                <option value="">Select</option>
                                                                                @foreach($currencies as $currency)
                                                                                    <option value="{{ $currency }}" {{ $currency == $settings->currency ? 'selected' : ''}}>{!! $currency !!}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="allow_fund_with_card" style="color:blue">Allow Wallet Funding with card</label>
                                                                            <select name="allow_fund_with_card" class="form-control" id="allow_fund_with_card">
                                                                                <option value="">Select</option>
                                                                                <option value="yes"{{ $settings->allow_fund_with_card == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                <option value="no" {{ $settings->allow_fund_with_card == 'no' ? 'selected' : ''}}>No</option>
                                                                            </select>
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="card_funding_extra_charge" style="color:blue">Card Funding Extra Charge({!! getSettings()->currency !!})</label>
                                                                            <input type="number" name="card_funding_extra_charge" value="{{ $settings->card_funding_extra_charge ?? old('card_funding_extra_charge') }}" class="form-control">
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="allow_fund_with_reserved_account">Allow Wallet Funding with reserved account</label>
                                                                            <select name="allow_fund_with_reserved_account" class="form-control" id="allow_fund_with_reserved_account">
                                                                                <option value="">Select</option>
                                                                                <option value="yes"{{ $settings->allow_fund_with_reserved_account == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                <option value="no" {{ $settings->allow_fund_with_reserved_account == 'no' ? 'selected' : ''}}>No</option>
                                                                            </select>
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="referral_system_status">Referral System Status</label>
                                                                            <select name="referral_system_status" class="form-control" id="referral_system_status">
                                                                                <option value="">Select</option>
                                                                                <option value="active"{{ $settings->referral_system_status == 'active' ? 'selected' : ''}}>Active</option>
                                                                                <option value="inactive" {{ $settings->referral_system_status == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                                            </select>
                                                                        </fieldset>
                                                                        {{-- <fieldset class="form-group" style="display:{{ $settings->referral_system_status == 'active' ? 'block' : 'none'}}" id="referral_percentage_div">
                                                                            <label for="referral_percentage">Referral Percentage(%)</label>
                                                                            <input type="number" class="form-control" id="referral_percentage" step="0.01" name="referral_percentage" value="{{ $settings->referral_percentage ?? old('referral_percentage') }}" placeholder="Enter percentage for referral earnings">
                                                                        </fieldset> --}}
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="">Payment Gateway</label>
                                                                            <input type="text" class="form-control" id="" value="{{ PaymentGateway::where('status', 'active')->value('name') }}" disabled>
                                                                        </fieldset>
                                                                        <div class="row">
                                                                            <div class="col-md-8">
                                                                                <fieldset class="form-group">
                                                                                    <label for="logo">Logo</label>
                                                                                    <div class="custom-file">
                                                                                        <input type="file" accept="image/*" class="custom-file-input" id="logo" name="logo">
                                                                                        <label class="custom-file-label" for="image">Replace Logo</label>
                                                                                    </div>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                @if(!empty(getSettings()->logo))
                                                                                    <img style="height:auto;width:120px" src="{{ asset(getSettings()->logo)}}" alt="">
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-8">
                                                                                <fieldset class="form-group">
                                                                                    <label for="favicon">Favicon</label>
                                                                                    <div class="custom-file">
                                                                                        <input type="file" accept="image/*" class="custom-file-input" id="favicon" name="favicon">
                                                                                        <label class="custom-file-label" for="image">Replace Favicon</label>
                                                                                    </div>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                @if(!empty(getSettings()->favicon))
                                                                                    <img style="height:62px;width:auto" src="{{ asset(getSettings()->favicon)}}" alt="">
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="row">
                                                                            <div class="col-md-8">
                                                                                <fieldset class="form-group">
                                                                                    <label for="dashboard_logo">Dashboard Logo</label>
                                                                                    <div class="custom-file">
                                                                                        <input type="file" style="height:auto;width:120px" accept="image/*" class="custom-file-input" id="dashboard_logo" name="dashboard_logo">
                                                                                        <label class="custom-file-label" for="dashboard_logo">Replace Dashboard Logo</label>
                                                                                    </div>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                @if(!empty(getSettings()->dashboard_logo))
                                                                                    <img style="height:62px;width:auto" src="{{ asset(getSettings()->dashboard_logo)}}" alt="">
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <fieldset class="form-group">
                                                                            <label for="seo_title">SEO Title</label>
                                                                            <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ $settings->seo_title ?? old('seo_title') }}" placeholder="SEO Title">
                                                                        </fieldset>
                                                                        <fieldset class="form-group">
                                                                            <label for="support_link">Support Link</label>
                                                                            <input type="text" class="form-control" id="support_link" name="support_link" value="{{ $settings->support_link ?? old('support_link') }}" placeholder="Support Link">
                                                                        </fieldset>
                                                                        
                                                                        <fieldset class="form-group">
                                                                            <label for="seo_description">SEO Description</label>
                                                                            <textarea class="form-control" id="seo_description" rows="3" name="seo_description" value="{{ $settings->seo_description ?? old('seo_description') }}" placeholder="SEO Description" required>{{ $settings->seo_description ?? old('seo_description') }}</textarea>
                                                                        </fieldset>
                                                                        {{-- <fieldset class="form-group">
                                                                            <label for="google_ad_code">Google Ad Code</label>
                                                                            <textarea class="form-control" id="google_ad_code" rows="3" name="google_ad_code" value="{{ $settings->google_ad_code ?? old('google_ad_code') }}" placeholder="Google ad code">{{ $settings->google_ad_code ?? old('google_ad_code') }}</textarea>
                                                                        </fieldset> --}}
                                                                    </div>
                                                                    <div class="col-md-12">
                                                                        <button class="btn btn-primary" type="submit">Update</button>
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
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
    $('#referral_system_status').on('change', function (e) {
        var referral_system_status = $('#referral_system_status').val();
       
        if (referral_system_status == '' || referral_system_status == 'inactive') {
            $('#referral_percentage_div').hide();
            $("#referral_percentage").attr({
                "required": false,
            });
            return;
        }else if(referral_system_status == 'active'){
            $('#referral_percentage_div').show();

            $("#referral_percentage").attr({
                "required": true,
            });
        }
    });
   
</script>

@endsection