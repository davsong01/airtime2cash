@extends('layouts.app')
@section('title', 'Edit KYC data')

@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
     .verified{
        color: green !important;
        font-size: 13px;
        margin-top: -6px;
        display: inline-block;
        margin-left: 5px;
    }
    .unverified{
        color: orange !important;
        font-size: 13px;
        margin-top: -6px;
        display: inline-block;
        margin-left: 5px;
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
                                                        <h4 class="card-title">Update KYC data</h4>
                                                        @include('layouts.alerts')
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <form action="{{route('update.kyc.details.process')}}" method="POST" autocomplete="off" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="row">
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('FIRST_NAME', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="FIRST_NAME">First Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('FIRST_NAME', auth()->user()->customer->id)['value'] }}" disabled>
                                                                        @else 
                                                                        <label for="FIRST_NAME">First Name</label>
                                                                        <input type="text" name="FIRST_NAME" class="form-control" value="{{ auth()->user()->firstname }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('MIDDLE_NAME', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('MIDDLE_NAME', auth()->user()->customer->id)['value'] }}" disabled>
                                                                        @else 
                                                                        <label for="MIDDLE_NAME">Middle Name</label>
                                                                        <input type="text" name="MIDDLE_NAME" class="form-control" value="{{ auth()->user()->middlename }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('LAST_NAME', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="LAST_NAME">Last Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('LAST_NAME', auth()->user()->customer->id)['value'] }}" disabled>
                                                                        @else 
                                                                        <label for="lastname">Last Name</label>
                                                                        <input type="text" name="LAST_NAME"  class="form-control" value="{{ auth()->user()->lastname }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        <label for="email">Email Address</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input autocomplete="false" class="form-control" disabled value="{{ auth()->user()->email }}">
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('PHONE_NUMBER', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('PHONE_NUMBER', auth()->user()->customer->id)['value'] }}" disabled>
                                                                        @else 
                                                                        <label for="lastname">Phone Number</label>
                                                                        <input type="text" name="PHONE_NUMBER" class="form-control" value="{{ auth()->user()->phone }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('COUNTRY', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="COUNTRY">Country</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" name="COUNTRY" class="form-control" value="{{ kycStatus('COUNTRY', auth()->user()->customer->id)['value']}}" disabled>
                                                                        @else
                                                                        <label for="COUNTRY">Country</label>
                                                                        <select name="COUNTRY" id="country" class="form-control" required>
                                                                            <option value="">Select...</option>
                                                                            <option value="Nigeria">Nigeria</option>
                                                                        </select>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        <label for="STATE">State</label>
                                                                        @if(kycStatus('STATE', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="STATE">State</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('STATE', auth()->user()->customer->id)['value'] }}" disabled/>
                                                                        @else
                                                                        <select name="STATE" id="state" class="form-control">
                                                                            @foreach (getStates() as $state)
                                                                                <option value="{{$state}}"  {{ kycStatus('STATE', auth()->user()->customer->id)['value'] ? 'selected' : '' }}>{{$state}}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        <label for="LGA">Local Government Area</label>
                                                                        @if(kycStatus('LGA', auth()->user()->customer->id)['status'] == 'verified')<span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ kycStatus('LGA', auth()->user()->customer->id)['value'] }}" disabled/>
                                                                        @else
                                                                        <select id="lga" name="LGA" class="form-control" required>
                                                                            <option value="">Select</option>
                                                                            @if (!empty($lgas))
                                                                                @foreach ($lgas as $item)
                                                                                    <option value="{{$item}}" {{ kycStatus('LGA', auth()->user()->customer->id)['value'] == $item ? 'selected' : '' }}>{{$item}}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('DOB', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="DOB">Date of Birth</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="date" class="form-control" value="{{ kycStatus('DOB', auth()->user()->customer->id)['value'] }}" disabled>
                                                                        @else 
                                                                        <label for="lastname">Date of Birth (As associated with your BVN)</label>
                                                                        <input type="date" name="DOB"  class="form-control" value="{{ kycStatus('DOB', auth()->user()->customer->id)['value'] }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        @if(kycStatus('BVN', auth()->user()->customer->id)['status'] == 'verified')
                                                                        <label for="bvn">BVN</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input autocomplete="false" type="text" class="form-control" value="{{ starMiddle(kycStatus('BVN', auth()->user()->customer->id)['value'] ) }}" disabled>
                                                                        @else 
                                                                        <label for="bvn">BVN</label>
                                                                        <input type="text" name="BVN"  class="form-control" value="{{kycStatus('BVN', auth()->user()->customer->id)['value'] }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                @if(kycStatus('IDCARD', auth()->user()->customer->id)['status'] == 'verified')
                                                                <div class="col-md-6 mb-2"> 
                                                                    <label for="idcard">ID CARD</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                    <input autocomplete="false" type="text" class="form-control" value="{{(kycStatus('IDCARDTYPE', auth()->user()->customer->id)['value'] ) }}" disabled>
                                                                </div>
                                                                @else 
                                                                    <div class="col-md-6">   
                                                                        <fieldset class="form-group">
                                                                            <label for="IDCARDTYPE">ID Card Type</label>
                                                                            <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-control" required>
                                                                                <option value="">Select</option>
                                                                                <option value="Driver's Licence">Driver's Licence</option>
                                                                                <option value="Voter's Card">Voter's Card</option>
                                                                                <option value="Nin Slip">Nin Slip</option>
                                                                                <option value="International Passport">International Passport</option>
                                                                            </select>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">   
                                                                    <fieldset class="form-group">
                                                                        <label for="IDCARD">ID CARD <small style="font-weight: bold;color:red">(Not more that 500 kilobytes)</small> </label>
                                                                        <input type="file" name="IDCARD"  class="form-control" required>
                                                                    </fieldset>
                                                                </div>
                                                                @endif
                                                                
                                                            </div>
                                                            
                                                            @if(getFinalKycStatus(auth()->user()->customer->id) == 'unverified')
                                                            <div class="row">
                                                                <div class="col-md-12"> 
                                                                    <button class="btn btn-primary" type="submit">Submit</button>
                                                                </div>
                                                            </div>
                                                            @else
                                                            <a href="{{ route('customer.load.wallet') }}" class="btn btn-success">Fund wallet</a>
                                                            @endif
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- <div class="col-md-3">
                                            {!! getSettings()->google_ad_code !!}
                                        </div> --}}
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
    $('#state').on('change',function () {
        var state = $('#state').val();
        $('#lga option:not(:first)').remove();
        $.ajax({
            type: "GET",
            url: "{{url('/')}}/get-lga-by-statename/"+state,
            beforeSend: function () {

            },
            success: function(data) {
                $("#lga").append(data);
            }
        });
    });
</script>
<script>
    $(document).ready(function () {
        var variations = [];
        
        $('#product').on('change', function () {
            $('#variation-div').show();
            $('#amount-div').hide();
    
            $("#amount").prop('readonly', false);
            $("#amount").val('');
    
            $('#variation').find('option').not(':first').remove();
    
            var product = $('#product').val();
            if (product == '') {
                return;
            } else {
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
    
                $.ajax({
                    url: "{{ url('customer-get-variations') }}/" + product,
                    success: function (data) {
                        
                        if (data && data.length > 0) {
                            for (t = 0; t <= data.length; t++) {
                                console.log(data[t]);
                                $('#variation').append(
                                    `<option value="${data[t].id}" data-isFixed="${data[t].fixed_price}" data-amount="${data[t].system_price}"> ${data[t].system_name}</option>`
                                    );
                                variations.push({
                                    "id": data[t].id,
                                    "max": data[t].max,
                                    "min": data[t].min,
                                    "fixedPrice": data[t].fixed_price,
                                    "variation_amount": data[t].system_price
                                });
                            }
                        }
                    }
                });
            }
    
        });
    
        $('#variation').on('change', function (e) {
            $('#amount-div').show();
            var v = e.target.value;
            var selected = variations.filter((item) => {
                return item.id == v;
            });
            console.log('sss=>', selected[0]);
            if (selected[0].fixedPrice == 'Yes') {
                $("#amount").attr({
                    "max": "",
                    "min": ""
                });
    
                $('#amount').val(selected[0].variation_amount);
                // $('#amount-label').text(selected[0].charged_currency+selected[0].charged_amount);
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
    
    
        $('.select2').select2();
    });
</script>

@endsection
