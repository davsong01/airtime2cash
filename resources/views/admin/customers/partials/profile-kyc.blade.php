@php
    $kycField = fn (string $key) => $kycData->get($key) ?? (object) ['value' => '', 'status' => 'unverified'];
    $kycFields = [
        'FIRST_NAME' => 'First name',
        'MIDDLE_NAME' => 'Middle name',
        'LAST_NAME' => 'Last name',
        'PHONE_NUMBER' => 'Phone number',
        'BVN' => 'BVN',
    ];
    $idCard = $kycField('IDCARD');
    $idCardType = $kycField('IDCARDTYPE');
@endphp

<div class="customer-section-heading">
    <div>
        <h4>Identity verification</h4>
        <p>Review submitted identity details, correct data where necessary, and make the final verification decision.</p>
    </div>
    <div class="d-flex flex-wrap">
        <a href="{{ route('admin.customer.approve.kyc', $customer->id) }}" onclick="return confirm('Approve this KYC and create reserved accounts?')" class="btn btn-success btn-sm mr-50"><i class="bx bx-check mr-25"></i> Approve KYC</a>
        <a href="{{ route('admin.customer.decline.kyc', $customer->id) }}" onclick="return confirm('Decline this KYC submission?')" class="btn btn-outline-danger btn-sm"><i class="bx bx-x mr-25"></i> Decline</a>
    </div>
</div>

<div class="alert {{ $finalKycStatus === 'awaiting-approval' ? 'alert-warning' : ($finalKycStatus === 'verified' ? 'alert-success' : 'alert-secondary') }}">
    <strong>Current KYC status:</strong> {{ ucfirst(str_replace('-', ' ', $finalKycStatus)) }}
</div>

<form action="{{ route('admin.customer.update.kyc', $customer->id) }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Update this customer\'s KYC details?')" class="customer-form-panel">
    @csrf
    <div class="row">
        @foreach($kycFields as $key => $label)
            @php
                $field = $kycField($key);
                $fieldClass = $field->status === 'verified' ? 'text-success' : ($field->status === 'declined' ? 'text-danger' : 'text-warning');
            @endphp
            <div class="col-md-6 form-group">
                <label for="{{ $key }}">{{ $label }} <span class="kyc-field-status {{ $fieldClass }}">{{ ucfirst(str_replace('-', ' ', $field->status)) }}</span></label>
                <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" value="{{ $field->value }}" @if($key === 'BVN') maxlength="11" inputmode="numeric" @endif>
            </div>
        @endforeach

        <div class="col-md-6 form-group">
            <label for="kyc-email">Email address <span class="kyc-field-status text-success">On file</span></label>
            <input type="email" id="kyc-email" class="form-control" value="{{ $user->email }}" disabled>
        </div>
        <div class="col-md-6 form-group">
            <label for="IDCARDTYPE">ID card type <span class="kyc-field-status {{ $idCardType->status === 'verified' ? 'text-success' : ($idCardType->status === 'declined' ? 'text-danger' : 'text-warning') }}">{{ ucfirst($idCardType->status) }}</span></label>
            <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-control">
                <option value="">Select ID card type</option>
                @foreach(['Nin Slip', 'International Passport', "Driver's Licence", "Voter's Card"] as $type)
                    <option value="{{ $type }}" @selected($idCardType->value === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label for="IDCARD">Identity document <span class="kyc-field-status {{ $idCard->status === 'verified' ? 'text-success' : ($idCard->status === 'declined' ? 'text-danger' : 'text-warning') }}">{{ ucfirst($idCard->status) }}</span></label>
            @if($idCard->value)
                <div class="mb-75"><img src="{{ asset($idCard->value) }}" alt="Submitted identity document" class="kyc-document-thumb" onclick="zoomImg(this)"></div>
            @endif
            <input type="file" id="IDCARD" name="IDCARD" accept="image/jpg,image/jpeg" class="form-control">
            <small class="text-muted">JPEG only, maximum 1 MB.</small>
        </div>
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save mr-25"></i> Save KYC corrections</button>
        </div>
    </div>
</form>
