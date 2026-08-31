@php
    $kycField = fn (string $key) => $kycData->get($key) ?? (object) ['value' => '', 'status' => 'unverified', 'review_note' => null];
    $kycFields = [
        'FIRST_NAME' => 'First name',
        'MIDDLE_NAME' => 'Middle name',
        'LAST_NAME' => 'Last name',
        'PHONE_NUMBER' => 'Phone number',
        'DOB' => 'Date of birth',
        'BVN' => 'BVN',
    ];
    $idCard = $kycField('IDCARD');
    $idCardType = $kycField('IDCARDTYPE');
    $bvnRawData = $customer->getRawOriginal('bvn_data');
    $bvnData = (array) ($customer->bvn_data ?? []);
    $bvnMode = $settings->bvn_verification_mode ?? 'manual';
    $bvnStoredMode = data_get($bvnData, 'verification_mode');
    $bvnDisplayMode = filled($bvnStoredMode) ? $bvnStoredMode : $bvnMode;
    $bvnVerificationCharge = (float) ($settings->bvn_verification_charge ?? 0);
    $bvnNameMatch = (bool) data_get($bvnData, 'name_match', false);
    $bvnVerifiedName = data_get($bvnData, 'verified_name');
    $bvnProfileName = data_get($bvnData, 'profile_name');
    $bvnResponse = data_get($bvnData, 'response', []);
    $bvnHasSavedData = filled($bvnRawData) && $bvnRawData !== 'null' && $bvnRawData !== '[]' && $bvnRawData !== '{}';
    $kycReviewUrl = route('customers.kyc-field-review', $customer->id);
@endphp

<div class="customer-section-heading">
    <div>
        <h4>Identity verification</h4>
        <p>Review submitted identity details, correct data where necessary, and make the final verification decision.</p>
    </div>
    <div class="d-flex flex-wrap">
        <a href="{{ route('admin.customer.approve.kyc', $customer->id) }}" onclick="return confirm('Approve this KYC and create reserved accounts?')" class="btn btn-success btn-sm mr-50"><i class="bx bx-check mr-25"></i> Approve KYC</a>
        <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#rejectKycModal"><i class="bx bx-x mr-25"></i> Reject KYC</button>
    </div>
</div>

@if(filled($customer->kyc_rejection_reason))
    <div class="alert alert-danger">
        <strong>Latest rejection reason:</strong>
        <div class="mt-50">{{ $customer->kyc_rejection_reason }}</div>
    </div>
@endif

<div class="modal fade" id="rejectKycModal" tabindex="-1" role="dialog" aria-labelledby="rejectKycModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.customer.decline.kyc', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="rejectKycModalLabel">Reject KYC submission</h5>
                        <small class="text-muted">Explain what the customer must correct before resubmitting.</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="kyc_rejection_reason">Rejection reason <span class="text-danger">*</span></label>
                        <textarea
                            class="form-control @error('kyc_rejection_reason', 'kycRejection') is-invalid @enderror"
                            id="kyc_rejection_reason"
                            name="kyc_rejection_reason"
                            rows="5"
                            minlength="10"
                            maxlength="2000"
                            placeholder="For example: The uploaded ID is unclear. Upload a readable copy showing all four corners."
                            required>{{ old('kyc_rejection_reason', $customer->kyc_rejection_reason) }}</textarea>
                        @error('kyc_rejection_reason', 'kycRejection')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">This message will be visible to the customer and included in their notification email.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bx bx-x mr-25"></i> Reject and notify customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->kycRejection->has('kyc_rejection_reason'))
    <script>
        window.addEventListener('load', function () {
            $('#rejectKycModal').modal('show');
        });
    </script>
@endif

<div
    id="customer-kyc-status-alert"
    class="alert {{ in_array($finalKycStatus, ['awaiting-approval', 'in-review', 'pending'], true) ? 'alert-warning' : ($finalKycStatus === 'verified' ? 'alert-success' : 'alert-secondary') }}"
>
    <strong>Current KYC status:</strong>
    <span id="customer-kyc-status-text">{{ ucfirst(str_replace('-', ' ', $finalKycStatus)) }}</span>
</div>

<form action="{{ route('admin.customer.update.kyc', $customer->id) }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Update this customer\'s KYC details?')" class="customer-form-panel">
    @csrf
    <div class="row">
        @foreach($kycFields as $key => $label)
            @php
                $field = $kycField($key);
                $fieldClass = $field->status === 'verified' ? 'text-success' : ($field->status === 'in-review' ? 'text-info' : ($field->status === 'declined' ? 'text-danger' : 'text-warning'));
                $fieldValue = trim((string) $field->value);
                $bvnHasVerification = $key === 'BVN' && (filled($bvnVerifiedName) || filled(data_get($bvnData, 'verified_at')) || filled($customer->bvn_verification_status) || filled($bvnResponse));
                $bvnFieldClass = $key === 'BVN' && $bvnHasVerification
                    ? ($bvnNameMatch ? 'is-valid' : 'is-invalid')
                    : '';
            @endphp
            <div class="col-md-6 form-group">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-50">
                    <label for="{{ $key }}" class="mb-0">
                        {{ $label }}
                        @if($key === 'BVN' && $bvnHasVerification && ! $bvnNameMatch)
                            <i
                                class="bx bx-info-circle text-danger ml-25"
                                data-toggle="tooltip"
                                data-placement="top"
                                title="BVN name does not match profile name."
                                style="cursor: help;"
                            ></i>
                        @endif
                        <span class="kyc-field-status {{ $fieldClass }}">{{ ucfirst(str_replace('-', ' ', $field->status ?: 'pending')) }}</span>
                    </label>
                    <div class="d-flex align-items-center flex-wrap">
                        @if($key === 'BVN' && $bvnMode === 'auto' && $field->status !== 'verified')
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm mr-25 js-bvn-verify-btn"
                                data-verify-url="{{ route('customers.verify-bvn', $customer->id) }}"
                                data-bvn-field="#BVN"
                                data-bvn-charge="{{ $bvnVerificationCharge }}">
                                Verify BVN
                            </button>
                        @endif
                        @if($key === 'BVN')
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm mr-25 js-bvn-show-result-btn {{ $bvnHasSavedData ? '' : 'd-none' }}"
                                data-toggle="modal"
                                data-target="#bvnVerificationResultModal">
                                Show result
                            </button>
                        @endif
                        <button
                            type="button"
                            class="btn btn-outline-success btn-sm mr-25 js-kyc-review-btn"
                            data-field="{{ $key }}"
                            data-field-label="{{ $label }}"
                            data-action="approve"
                            data-review-url="{{ $kycReviewUrl }}"
                            data-value="{{ e($fieldValue) }}">
                            Approve
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm js-kyc-review-btn"
                            data-field="{{ $key }}"
                            data-field-label="{{ $label }}"
                            data-action="reject"
                            data-review-url="{{ $kycReviewUrl }}"
                            data-value="{{ e($fieldValue) }}">
                            Reject
                        </button>
                    </div>
                </div>
                <input type="text" class="form-control {{ $bvnFieldClass }}" id="{{ $key }}" name="{{ $key }}" value="{{ $field->value }}" @if($key === 'BVN') maxlength="11" inputmode="numeric" @endif>
                @if($key === 'BVN')
                    <small class="text-muted d-block mt-50">
                        Mode:
                        <strong class="{{ $bvnDisplayMode === 'auto' ? 'text-success' : 'text-warning' }}">
                            {{ ucfirst($bvnDisplayMode) }}
                        </strong>
                    </small>
                    <small class="text-muted d-block mt-50">
                        Verification status:
                        <strong class="{{ $bvnHasVerification ? ($bvnNameMatch ? 'text-success' : 'text-danger') : 'text-warning' }}">
                            {{ $bvnHasVerification ? ($bvnNameMatch ? 'Names match profile' : 'Names do not match') : 'Not verified yet' }}
                        </strong>
                    </small>
                @endif
                @if(filled($field->review_note))
                    <small class="text-danger d-block mt-50">Rejection note: {{ $field->review_note }}</small>
                @endif
            </div>
        @endforeach

        <div class="col-md-6 form-group">
            <label for="kyc-email">Email address <span class="kyc-field-status text-success">On file</span></label>
            <input type="email" id="kyc-email" class="form-control" value="{{ $user->email }}" disabled>
        </div>
        <div class="col-md-6 form-group">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-50">
                <label for="IDCARDTYPE" class="mb-0">ID card type <span class="kyc-field-status {{ $idCardType->status === 'verified' ? 'text-success' : ($idCardType->status === 'in-review' ? 'text-info' : ($idCardType->status === 'declined' ? 'text-danger' : 'text-warning')) }}">{{ ucfirst($idCardType->status ?: 'pending') }}</span></label>
                <div class="d-flex align-items-center flex-wrap">
                    <button
                        type="button"
                        class="btn btn-outline-success btn-sm mr-25 js-kyc-review-btn"
                        data-field="IDCARDTYPE"
                        data-field-label="ID card type"
                        data-action="approve"
                        data-review-url="{{ $kycReviewUrl }}"
                        data-value="{{ e(trim((string) $idCardType->value)) }}">
                        Approve
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm js-kyc-review-btn"
                        data-field="IDCARDTYPE"
                        data-field-label="ID card type"
                        data-action="reject"
                        data-review-url="{{ $kycReviewUrl }}"
                        data-value="{{ e(trim((string) $idCardType->value)) }}">
                        Reject
                    </button>
                </div>
            </div>
            <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-control">
                <option value="">Select ID card type</option>
                @foreach(['Nin Slip', 'International Passport', "Driver's Licence", "Voter's Card"] as $type)
                    <option value="{{ $type }}" @selected($idCardType->value === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 form-group">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-50">
                <label for="IDCARD" class="mb-0">Identity document <span class="kyc-field-status {{ $idCard->status === 'verified' ? 'text-success' : ($idCard->status === 'in-review' ? 'text-info' : ($idCard->status === 'declined' ? 'text-danger' : 'text-warning')) }}">{{ ucfirst($idCard->status ?: 'pending') }}</span></label>
                <div class="d-flex align-items-center flex-wrap">
                    <button
                        type="button"
                        class="btn btn-outline-success btn-sm mr-25 js-kyc-review-btn"
                        data-field="IDCARD"
                        data-field-label="Identity document"
                        data-action="approve"
                        data-review-url="{{ $kycReviewUrl }}"
                        data-value="{{ e(trim((string) $idCard->value)) }}">
                        Approve
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm js-kyc-review-btn"
                        data-field="IDCARD"
                        data-field-label="Identity document"
                        data-action="reject"
                        data-review-url="{{ $kycReviewUrl }}"
                        data-value="{{ e(trim((string) $idCard->value)) }}">
                        Reject
                    </button>
                </div>
            </div>
            @if($idCard->value)
                <div class="mb-75"><img src="{{ asset($idCard->value) }}" alt="Submitted identity document" class="kyc-document-thumb" onclick="zoomImg(this)"></div>
            @endif
            <input type="file" id="IDCARD" name="IDCARD" accept="image/jpg,image/jpeg" class="form-control">
            <small class="text-muted">JPEG only, maximum 1 MB.</small>
        </div>
        <div class="col-12 form-group">
            @if($bvnHasSavedData)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#bvnVerificationResultModal">
                    Show result
                </button>
            @endif
        </div>
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save mr-25"></i> Save KYC corrections</button>
        </div>
    </div>
</form>
