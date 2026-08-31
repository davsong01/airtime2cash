@extends('layouts.app')
@section('title', 'Edit KYC data')

@php
    $kycIsLocked = getFinalKycStatus(auth()->user()->customer->id) === 'awaiting-approval';
    $kycField = fn (string $key) => kycStatus($key, auth()->user()->customer->id);
    $statusLabel = fn ($status) => $status === 'verified' ? 'Verified' : ($status === 'declined' ? 'Declined' : 'Not Verified');
    $hasVerifiedKycField = collect(['FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'DOB', 'BVN', 'IDCARDTYPE', 'IDCARD', 'PHONE_NUMBER'])
        ->contains(fn ($key) => ($kycField($key)->status ?? 'unverified') === 'verified');
    $hasOpenKycField = collect(['FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'DOB', 'BVN', 'IDCARDTYPE', 'IDCARD', 'PHONE_NUMBER'])
        ->contains(fn ($key) => ($kycField($key)->status ?? 'unverified') === 'declined');
@endphp

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
    .declined{
        color: red !important;
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
                                                        @include('shared.kyc-rejection-alert')
                                                    </div>
                                                    <div class="px-3">
                                                        <div class="alert mb-3" style="background: linear-gradient(135deg, #fff4db 0%, #fffaf0 100%); border-left: 6px solid #f59e0b;">
                                                            <div class="d-flex align-items-start">
                                                                <div class="mr-3" style="width: 42px; height: 42px; border-radius: 50%; background: rgba(245, 158, 11, .16); color: #b45309; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;">
                                                                    <i class="fa fa-exclamation-circle"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-uppercase font-weight-bold small mb-1" style="letter-spacing: .08em; color: #92400e;">Important billing notice</div>
                                                                    <strong class="d-block mb-1" style="color: #92400e;">BVN verification fee applies.</strong>
                                                                    <span style="color: #92400e;">
                                                                        A one-time verification fee of {!! getSettings()->currency !!}{{ number_format((float) (getSettings()->bvn_verification_charge ?? 0), 2) }} will be debited from your wallet balance upon successful BVN verification and subsequent initial funding of your account.
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <form action="{{route('update.kyc.details.process')}}" method="POST" autocomplete="off" enctype="multipart/form-data">
                                                            @csrf
                                                            @if($kycIsLocked)
                                                                <div class="alert alert-warning">
                                                                    <strong>KYC awaiting review.</strong> Your submitted details are locked while the administrator reviews them. You can make changes if the submission is declined.
                                                                </div>
                                                            @endif
                                                            <fieldset>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $firstNameKyc = $kycField('FIRST_NAME'); @endphp
                                                                        @php $firstNameLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $firstNameKyc->status !== 'declined'; @endphp
                                                                        @if($firstNameKyc->status == 'verified' || $firstNameLocked)
                                                                        <label for="FIRST_NAME">First Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ $firstNameKyc->value }}" disabled>
                                                                        @else
                                                                            @if($firstNameKyc->status == 'declined')
                                                                            <label for="FIRST_NAME">First Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($firstNameKyc->review_note))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="First Name" data-reason="{{ e($firstNameKyc->review_note) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="FIRST_NAME">First Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif
                                                                            <input type="text" name="FIRST_NAME" class="form-control" value="{{ filled($firstNameKyc->value) ? $firstNameKyc->value : auth()->user()->firstname }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $middleNameKyc = $kycField('MIDDLE_NAME'); @endphp
                                                                        @php $middleNameLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $middleNameKyc->status !== 'declined'; @endphp
                                                                        @if($middleNameKyc->status == 'verified' || $middleNameLocked)
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ $middleNameKyc->value }}" disabled>
                                                                        @else
                                                                        @if($middleNameKyc->status == 'declined')
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($middleNameKyc->review_note))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Middle Name" data-reason="{{ e($middleNameKyc->review_note) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" name="MIDDLE_NAME" class="form-control" value="{{ filled($middleNameKyc->value) ? $middleNameKyc->value : auth()->user()->middlename }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $lastNameKyc = $kycField('LAST_NAME'); @endphp
                                                                        @php $lastNameLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $lastNameKyc->status !== 'declined'; @endphp
                                                                        @if($lastNameKyc->status == 'verified' || $lastNameLocked)
                                                                        <label for="LAST_NAME">Last Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ $lastNameKyc->value }}" disabled>
                                                                        @else
                                                                        @if($lastNameKyc->status == 'declined')
                                                                        <label for="LAST_NAME">Last Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($lastNameKyc->review_note))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Last Name" data-reason="{{ e($lastNameKyc->review_note) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="LAST_NAME">Last Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" name="LAST_NAME"  class="form-control" value="{{ filled($lastNameKyc->value) ? $lastNameKyc->value : auth()->user()->lastname }}" required>
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
                                                                        @php $phoneKyc = $kycField('PHONE_NUMBER'); @endphp
                                                                        @if($phoneKyc->status == 'verified')
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        @elseif($phoneKyc->status == 'declined')
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($phoneKyc->review_note))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Phone Number" data-reason="{{ e($phoneKyc->review_note) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" class="form-control" value="{{ auth()->user()->phone }}" disabled>
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $dobKyc = $kycField('DOB'); @endphp
                                                                        @php $dobLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $dobKyc->status !== 'declined'; @endphp
                                                                        @if($dobKyc->status == 'verified' || $dobLocked)
                                                                        <label for="DOB">Date of Birth</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input type="text" class="form-control" value="{{ $dobKyc->value }}" disabled>
                                                                        @else
                                                                        @if($dobKyc->status == 'declined')
                                                                        <label for="DOB">Date of Birth</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($dobKyc->review_note))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Date of Birth" data-reason="{{ e($dobKyc->review_note) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="DOB">Date of Birth</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="date" name="DOB" class="form-control" value="{{ $dobKyc->value }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $bvnKyc = $kycField('BVN'); @endphp
                                                                        @php $bvnLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $bvnKyc->status !== 'declined'; @endphp
                                                                        @if($bvnKyc->status == 'verified' || $bvnLocked)
                                                                        <label for="BVN">BVN</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                        <input autocomplete="off" type="text" class="form-control" value="{{ starMiddle($bvnKyc->value ) }}" disabled>
                                                                        @else
                                                                        @if($bvnKyc->status == 'declined')
                                                                        <label for="BVN">BVN</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($bvnKyc->review_note))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="BVN" data-reason="{{ e($bvnKyc->review_note) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="BVN">BVN</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" id="BVN" name="BVN" class="form-control" value="{{ filled($bvnKyc->value) ? $bvnKyc->value : '' }}" autocomplete="off" inputmode="numeric" pattern="[0-9]{11}" placeholder="Enter your 11-digit BVN" required maxlength="11" minlength="11">
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                @php $idCardKyc = $kycField('IDCARD'); @endphp
                                                                @php $idCardLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $idCardKyc->status !== 'declined'; @endphp
                                                                @if($idCardKyc->status == 'verified' || $idCardLocked)
                                                                <div class="col-md-6 mb-2">
                                                                    <label for="IDCARD">Identity document</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                    @if(filled($idCardKyc->value))
                                                                        <div class="mb-75">
                                                                            <img src="{{ asset($idCardKyc->value) }}" alt="Submitted identity document" onclick="zoomKycDocument(this)" style="max-width: 150px; width: 150px; cursor: zoom-in; border-radius: 8px; border: 1px solid #ddd;">
                                                                        </div>
                                                                    @endif
                                                                    <input autocomplete="false" type="text" class="form-control" value="{{ ($kycField('IDCARDTYPE')->value ) }}" disabled>
                                                                </div>
                                                                @else
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            @php $idTypeKyc = $kycField('IDCARDTYPE'); @endphp
                                                                            @php $idTypeLocked = ($kycIsLocked || $hasVerifiedKycField || $hasOpenKycField) && $idTypeKyc->status !== 'declined'; @endphp
                                                                            @if($idTypeKyc->status == 'declined')
                                                                            <label for="IDCARD">ID Card Type</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($idTypeKyc->review_note))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="ID Card Type" data-reason="{{ e($idTypeKyc->review_note) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="IDCARD">ID Card Type</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif

                                                                            <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-control" required @if($idTypeLocked) disabled @endif>
                                                                                <option value="">Select</option>
                                                                                <option value="Nin Slip" {{($idTypeKyc->value ==  "Nin Slip" ? 'selected' : '') }}>Nin Slip</option>
                                                                                <option value="International Passport" {{($idTypeKyc->value ==  "International Passport" ? 'selected' : '') }}>International Passport</option>
                                                                                <option value="Driver's Licence" {{($idTypeKyc->value ==  "Driver's Licence" ? 'selected' : '') }}>Driver's Licence</option>
                                                                                <option value="Voter's Card" {{($idTypeKyc->value ==  "Voter's Card" ? 'selected' : '') }}>Voter's Card</option>
                                                                            </select>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                            @php $idCardKyc = $kycField('IDCARD'); @endphp
                                                                            @if($idCardKyc->status == 'declined')
                                                                            <label for="IDCARD">Identity document </label> <small class="primary" style="font-weight: bold;">(JPEG only, maximum 500 KB)</small> <span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($idCardKyc->review_note))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="ID Card" data-reason="{{ e($idCardKyc->review_note) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="IDCARD">Identity document</label> <small class="primary" style="font-weight: bold;">(JPEG only, maximum 500 KB)</small> <span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif
                                                                            </label>
                                                                        @if(filled($idCardKyc->value))
                                                                            <div class="mb-75">
                                                                                <img src="{{ asset($idCardKyc->value) }}" alt="Submitted identity document" onclick="zoomKycDocument(this)" style="max-width: 150px; width: 150px; cursor: zoom-in; border-radius: 8px; border: 1px solid #ddd;">
                                                                            </div>
                                                                        @endif
                                                                        <input type="file" name="IDCARD" accept="image/jpg, image/jpeg" class="form-control" value="{{ $idCardKyc->value }}" required @if($idCardLocked) disabled @endif>
                                                                    </fieldset>
                                                                </div>
                                                                @endif

                                                            </div>

                                                            </fieldset>
                                                            @if(getFinalKycStatus(auth()->user()->customer->id) == 'verified')
                                                            <a href="{{ route('customer.load.wallet') }}" class="btn btn-success">Fund wallet</a>
                                                            @elseif($hasVerifiedKycField)
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <button class="btn btn-primary" type="submit">Review and resubmit KYC</button>
                                                                </div>
                                                            </div>
                                                            @elseif(getFinalKycStatus(auth()->user()->customer->id) == 'unverified' || $hasOpenKycField)
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <button class="btn btn-primary" type="submit">{{ $hasOpenKycField ? 'Review and resubmit KYC' : 'Submit KYC' }}</button>
                                                                </div>
                                                            </div>
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

<div class="modal fade" id="kycReasonModal" tabindex="-1" role="dialog" aria-labelledby="kycReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="kycReasonModalLabel">Field rejection reason</h5>
                    <small class="text-muted" id="kycReasonModalField"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0" id="kycReasonModalText"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kycDocumentModal" tabindex="-1" role="dialog" aria-labelledby="kycDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kycDocumentModalLabel">Identity document</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="kycDocumentModalImage" src="" alt="Identity document preview" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        </div>
    </div>
</div>

@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
    function zoomKycDocument(image) {
        if (!image || !image.src) {
            return;
        }

        $('#kycDocumentModalImage').attr('src', image.src);
        $('#kycDocumentModal').modal('show');
    }

    $(document).on('click', '.view-kyc-reason-btn', function () {
        $('#kycReasonModalField').text($(this).data('field') || '');
        $('#kycReasonModalText').text($(this).data('reason') || 'No rejection reason was provided.');
        $('#kycReasonModal').modal('show');
    });

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
