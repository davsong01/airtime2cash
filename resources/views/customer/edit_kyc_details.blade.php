@extends('layouts.app')
@section('title', 'Edit KYC data')

@php
    $kycIsLocked = getFinalKycStatus(auth()->user()->customer->id) === 'awaiting-approval';
    $kycField = fn (string $key) => kycStatus($key, auth()->user()->customer->id);
    $statusLabel = fn ($status) => $status === 'verified' ? 'Verified' : ($status === 'in-review' ? 'In Review' : ($status === 'declined' ? 'Declined' : 'Not Verified'));
    $kycFieldStatus = fn (string $key) => data_get($kycField($key), 'status', 'unverified');
    $kycFieldValue = fn (string $key) => data_get($kycField($key), 'value', '');
    $kycFieldReviewNote = fn (string $key) => data_get($kycField($key), 'review_note');
    $kycReviewableFields = ['FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'DOB', 'BVN', 'IDCARDTYPE', 'IDCARD', 'PHONE_NUMBER'];
    $hasKycHistory = collect($kycReviewableFields)
        ->contains(fn ($key) => in_array($kycFieldStatus($key), ['verified', 'declined'], true));
    $hasEditableKycField = collect($kycReviewableFields)
        ->contains(fn ($key) => in_array($kycFieldStatus($key), ['unverified', 'declined'], true));
    $allKycFieldsVerified = collect($kycReviewableFields)
        ->every(fn ($key) => $kycFieldStatus($key) === 'verified');
    $fullKycVerified = getFinalKycStatus(auth()->user()->customer->id) === 'verified' && $allKycFieldsVerified;
    $kycSubmitLabel = $hasKycHistory ? 'Review and resubmit KYC' : 'Submit KYC';
    $canFundWallet = $fullKycVerified;
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
    .in-review{
        color: #2563eb !important;
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
                                                                        @php $firstNameStatus = data_get($firstNameKyc, 'status', 'unverified'); @endphp
                                                                        @php $firstNameValue = data_get($firstNameKyc, 'value', ''); @endphp
                                                                        @php $firstNameNote = data_get($firstNameKyc, 'review_note'); @endphp
                                                                        @php $firstNameLocked = in_array($firstNameStatus, ['verified', 'in-review'], true); @endphp
                                                                        @if($firstNameLocked)
                                                                        <label for="FIRST_NAME">First Name</label><span class="{{ $firstNameStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $firstNameStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        <input type="text" class="form-control" value="{{ $firstNameValue }}" disabled>
                                                                        @else
                                                                            @if($firstNameStatus == 'declined')
                                                                            <label for="FIRST_NAME">First Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($firstNameNote))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="First Name" data-reason="{{ e($firstNameNote) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="FIRST_NAME">First Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif
                                                                            <input type="text" name="FIRST_NAME" class="form-control" value="{{ filled($firstNameValue) ? $firstNameValue : auth()->user()->firstname }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $middleNameKyc = $kycField('MIDDLE_NAME'); @endphp
                                                                        @php $middleNameStatus = data_get($middleNameKyc, 'status', 'unverified'); @endphp
                                                                        @php $middleNameValue = data_get($middleNameKyc, 'value', ''); @endphp
                                                                        @php $middleNameNote = data_get($middleNameKyc, 'review_note'); @endphp
                                                                        @php $middleNameLocked = in_array($middleNameStatus, ['verified', 'in-review'], true); @endphp
                                                                        @if($middleNameLocked)
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="{{ $middleNameStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $middleNameStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        <input type="text" class="form-control" value="{{ $middleNameValue }}" disabled>
                                                                        @else
                                                                        @if($middleNameStatus == 'declined')
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($middleNameNote))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Middle Name" data-reason="{{ e($middleNameNote) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="MIDDLE_NAME">Middle Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" name="MIDDLE_NAME" class="form-control" value="{{ filled($middleNameValue) ? $middleNameValue : auth()->user()->middlename }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $lastNameKyc = $kycField('LAST_NAME'); @endphp
                                                                        @php $lastNameStatus = data_get($lastNameKyc, 'status', 'unverified'); @endphp
                                                                        @php $lastNameValue = data_get($lastNameKyc, 'value', ''); @endphp
                                                                        @php $lastNameNote = data_get($lastNameKyc, 'review_note'); @endphp
                                                                        @php $lastNameLocked = in_array($lastNameStatus, ['verified', 'in-review'], true); @endphp
                                                                        @if($lastNameLocked)
                                                                        <label for="LAST_NAME">Last Name</label><span class="{{ $lastNameStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $lastNameStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        <input type="text" class="form-control" value="{{ $lastNameValue }}" disabled>
                                                                        @else
                                                                        @if($lastNameStatus == 'declined')
                                                                        <label for="LAST_NAME">Last Name</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($lastNameNote))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Last Name" data-reason="{{ e($lastNameNote) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="LAST_NAME">Last Name</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" name="LAST_NAME"  class="form-control" value="{{ filled($lastNameValue) ? $lastNameValue : auth()->user()->lastname }}" required>
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
                                                                        @php $phoneStatus = data_get($phoneKyc, 'status', 'unverified'); @endphp
                                                                        @php $phoneNote = data_get($phoneKyc, 'review_note'); @endphp
                                                                        @if($phoneStatus == 'verified' || $phoneStatus == 'in-review')
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="{{ $phoneStatus == 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $phoneStatus == 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        @elseif($phoneStatus == 'declined')
                                                                        <label for="PHONE_NUMBER">Phone Number</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($phoneNote))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Phone Number" data-reason="{{ e($phoneNote) }}">View reason</button>
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
                                                                        @php $dobStatus = data_get($dobKyc, 'status', 'unverified'); @endphp
                                                                        @php $dobValue = data_get($dobKyc, 'value', ''); @endphp
                                                                        @php $dobNote = data_get($dobKyc, 'review_note'); @endphp
                                                                        @php $dobLocked = in_array($dobStatus, ['verified', 'in-review'], true); @endphp
                                                                        @if($dobLocked)
                                                                        <label for="DOB">Date of Birth</label><span class="{{ $dobStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $dobStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        <input type="text" class="form-control" value="{{ $dobValue }}" disabled>
                                                                        @else
                                                                        @if($dobStatus == 'declined')
                                                                        <label for="DOB">Date of Birth</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($dobNote))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="Date of Birth" data-reason="{{ e($dobNote) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="DOB">Date of Birth</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="date" name="DOB" class="form-control" value="{{ $dobValue }}" required>
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        @php $bvnKyc = $kycField('BVN'); @endphp
                                                                        @php $bvnStatus = data_get($bvnKyc, 'status', 'unverified'); @endphp
                                                                        @php $bvnValue = data_get($bvnKyc, 'value', ''); @endphp
                                                                        @php $bvnNote = data_get($bvnKyc, 'review_note'); @endphp
                                                                        @php $bvnLocked = in_array($bvnStatus, ['verified', 'in-review'], true); @endphp
                                                                        @if($bvnLocked)
                                                                        <label for="BVN" class="mb-0">BVN</label>
                                                                        <a href="https://airtime2cash.com/what-the-cbns-circular-means-for-financial-services-in-nigeria/" target="_blank" rel="noopener noreferrer" class="ml-1" data-toggle="tooltip" title="Learn more about the BVN requirement">
                                                                            <i class="fa fa-info-circle"></i>
                                                                        </a>
                                                                        <span class="{{ $bvnStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $bvnStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                        <input autocomplete="off" type="text" class="form-control" value="{{ starMiddle($bvnValue) }}" disabled>
                                                                        @else
                                                                        @if($bvnStatus == 'declined')
                                                                        <label for="BVN" class="mb-0">BVN</label>
                                                                        <a href="https://airtime2cash.com/what-the-cbns-circular-means-for-financial-services-in-nigeria/" target="_blank" rel="noopener noreferrer" class="ml-1" data-toggle="tooltip" title="Learn more about the BVN requirement">
                                                                            <i class="fa fa-info-circle"></i>
                                                                        </a>
                                                                        <span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                        @if(filled($bvnNote))
                                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="BVN" data-reason="{{ e($bvnNote) }}">View reason</button>
                                                                        @endif
                                                                        @else
                                                                        <label for="BVN" class="mb-0">BVN</label>
                                                                        <a href="https://airtime2cash.com/what-the-cbns-circular-means-for-financial-services-in-nigeria/" target="_blank" rel="noopener noreferrer" class="ml-1" data-toggle="tooltip" title="Learn more about the BVN requirement">
                                                                            <i class="fa fa-info-circle"></i>
                                                                        </a>
                                                                        <span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                        @endif
                                                                        <input type="text" id="BVN" name="BVN" class="form-control" value="{{ filled($bvnValue) ? $bvnValue : '' }}" autocomplete="off" inputmode="numeric" pattern="[0-9]{11}" placeholder="Enter your 11-digit BVN" required maxlength="11" minlength="11">
                                                                        @endif
                                                                    </fieldset>
                                                                </div>
                                                                @php $idCardKyc = $kycField('IDCARD'); @endphp
                                                                @php $idCardStatus = data_get($idCardKyc, 'status', 'unverified'); @endphp
                                                                @php $idCardValue = data_get($idCardKyc, 'value', ''); @endphp
                                                                @php $idCardNote = data_get($idCardKyc, 'review_note'); @endphp
                                                                @php $idCardLocked = in_array($idCardStatus, ['verified', 'in-review'], true); @endphp
                                                                @if($idCardStatus == 'verified' || $idCardLocked)
                                                                <div class="col-md-6 mb-2">
                                                                    <label for="IDCARD">Identity document</label><span class="{{ $idCardStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $idCardStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                    @if(filled($idCardValue))
                                                                        <div class="mb-75">
                                                                            <img src="{{ asset($idCardValue) }}" alt="Submitted identity document" onclick="zoomKycDocument(this)" style="max-width: 150px; width: 150px; cursor: zoom-in; border-radius: 8px; border: 1px solid #ddd;">
                                                                        </div>
                                                                    @endif
                                                                    <input autocomplete="false" type="text" class="form-control" value="{{ $kycFieldValue('IDCARDTYPE') }}" disabled>
                                                                </div>
                                                                @else
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            @php $idTypeKyc = $kycField('IDCARDTYPE'); @endphp
                                                                            @php $idTypeStatus = data_get($idTypeKyc, 'status', 'unverified'); @endphp
                                                                            @php $idTypeValue = data_get($idTypeKyc, 'value', ''); @endphp
                                                                            @php $idTypeNote = data_get($idTypeKyc, 'review_note'); @endphp
                                                                            @php $idTypeLocked = in_array($idTypeStatus, ['verified', 'in-review'], true); @endphp
                                                                            @if($idTypeLocked)
                                                                            <label for="IDCARD">ID Card Type</label><span class="{{ $idTypeStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $idTypeStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                            @elseif($idTypeStatus == 'declined')
                                                                            <label for="IDCARD">ID Card Type</label><span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($idTypeNote))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="ID Card Type" data-reason="{{ e($idTypeNote) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="IDCARD">ID Card Type</label><span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif

                                                                            <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-control" required @if($idTypeLocked) disabled @endif>
                                                                                <option value="">Select</option>
                                                                                <option value="Nin Slip" {{ ($idTypeValue ==  "Nin Slip" ? 'selected' : '') }}>Nin Slip</option>
                                                                                <option value="International Passport" {{ ($idTypeValue ==  "International Passport" ? 'selected' : '') }}>International Passport</option>
                                                                                <option value="Driver's Licence" {{ ($idTypeValue ==  "Driver's Licence" ? 'selected' : '') }}>Driver's Licence</option>
                                                                                <option value="Voter's Card" {{ ($idTypeValue ==  "Voter's Card" ? 'selected' : '') }}>Voter's Card</option>
                                                                            </select>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                            @php $idCardKyc = $kycField('IDCARD'); @endphp
                                                                            @php $idCardStatus = data_get($idCardKyc, 'status', 'unverified'); @endphp
                                                                            @php $idCardValue = data_get($idCardKyc, 'value', ''); @endphp
                                                                            @php $idCardNote = data_get($idCardKyc, 'review_note'); @endphp
                                                                            @if($idCardLocked)
                                                                            <label for="IDCARD">Identity document</label> <small class="primary" style="font-weight: bold;">(JPEG only, maximum 500 KB)</small> <span class="{{ $idCardStatus === 'in-review' ? 'in-review' : 'verified' }}"><i class="fa fa-check"></i> {{ $idCardStatus === 'in-review' ? 'In Review' : 'Verified' }}</span>
                                                                            @elseif($idCardStatus == 'declined')
                                                                            <label for="IDCARD">Identity document </label> <small class="primary" style="font-weight: bold;">(JPEG only, maximum 500 KB)</small> <span class="declined"><i class="fa fa-times"></i> Declined</span>
                                                                            @if(filled($idCardNote))
                                                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2 view-kyc-reason-btn" data-field="ID Card" data-reason="{{ e($idCardNote) }}">View reason</button>
                                                                            @endif
                                                                            @else
                                                                            <label for="IDCARD">Identity document</label> <small class="primary" style="font-weight: bold;">(JPEG only, maximum 500 KB)</small> <span class="unverified"><i class="fa fa-times"></i> Not Verified</span>
                                                                            @endif
                                                                            </label>
                                                                        @if(filled($idCardValue))
                                                                            <div class="mb-75">
                                                                                <img src="{{ asset($idCardValue) }}" alt="Submitted identity document" onclick="zoomKycDocument(this)" style="max-width: 150px; width: 150px; cursor: zoom-in; border-radius: 8px; border: 1px solid #ddd;">
                                                                            </div>
                                                                        @endif
                                                                        <input type="file" name="IDCARD" accept="image/jpg, image/jpeg" class="form-control" value="{{ $idCardValue }}" required @if($idCardLocked) disabled @endif>
                                                                    </fieldset>
                                                                </div>
                                                                @endif

                                                            </div>

                                                            </fieldset>
                                                            @if($canFundWallet)
                                                            <a href="{{ route('customer.load.wallet') }}" class="btn btn-success">Fund wallet</a>
                                                            @elseif($hasEditableKycField)
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <button class="btn btn-primary" type="submit">{{ $kycSubmitLabel }}</button>
                                                                </div>
                                                            </div>
                                                            @else
                                                            <div class="alert alert-info mb-0">
                                                                <strong>Your KYC submission is under review.</strong> The fields you submitted are locked until an administrator completes the review.
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

    $(function () {
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
        }
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
