@extends('sneat.layouts.app')
@section('title', 'Edit KYC data')

@section('content')
    @php
        $customer = auth()->user()->customer;
        $finalKycStatus = getFinalKycStatus($customer->id);
        $kycIsLocked = $finalKycStatus === 'awaiting-approval';
        $kycField = fn (string $key) => kycStatus($key, $customer->id);
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
        $fullKycVerified = $finalKycStatus === 'verified' && $allKycFieldsVerified;
        $kycSubmitLabel = $hasKycHistory ? 'Review and resubmit KYC' : 'Submit KYC';
        $canFundWallet = $fullKycVerified;
        $settings = getSettings();
        $adminWhatsappNumber = preg_replace('/\D+/', '', (string) $settings->whatsapp_number);
        $submittedIdType = data_get(kycStatus('IDCARDTYPE', $customer->id), 'value', 'Not provided');
        $customerFullName = trim(auth()->user()->name) ?: auth()->user()->username;
        $adminWhatsappMessage = implode("\n", [
            'I just completed my KYC on airtime2cash.com.',
            '',
            'Full name: ' . $customerFullName,
            'Email: ' . auth()->user()->email,
            'Phone number: ' . auth()->user()->phone,
            'Submitted ID type: ' . $submittedIdType,
        ]);
        $adminWhatsappUrl = $adminWhatsappNumber
            ? 'https://api.whatsapp.com/send?phone=' . $adminWhatsappNumber . '&text=' . urlencode($adminWhatsappMessage)
            : null;
    @endphp

    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Verification',
        'title' => 'Update KYC Data',
        'subtitle' => 'Complete the fields that still need verification.',
    ])

    @include('sneat.layouts.alerts')
    @include('shared.kyc-rejection-alert')

    <div class="alert border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(245, 158, 11, .16), rgba(251, 191, 36, .10)); border-left: 6px solid #f59e0b !important; color: #92400e;">
        <div class="d-flex align-items-start gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 42px; height: 42px; background: rgba(245, 158, 11, .18); color: #b45309; flex: 0 0 auto;">
                <i class="bx bx-error-circle fs-4"></i>
            </span>
            <div>
                <div class="text-uppercase fw-bold small mb-1" style="letter-spacing: .08em;">Important billing notice</div>
                <strong class="d-block mb-1">BVN verification fee applies.</strong>
                <span>
                    A one-time verification fee of {!! getSettings()->currency !!}{{ number_format((float) (getSettings()->bvn_verification_charge ?? 0), 2) }} will be debited from your wallet balance upon successful BVN verification and subsequent initial funding of your account.
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Status</h5>
                    <div class="d-grid gap-2">
                        <span class="badge bg-label-primary p-2">KYC: {{ ucfirst($finalKycStatus) }}</span>
                        <span class="badge bg-label-secondary p-2">Email: On file</span>
                        <span class="badge bg-label-secondary p-2">Phone: {{ data_get(kycStatus('PHONE_NUMBER', $customer->id), 'status', 'unverified') }}</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">If the submission is declined, the affected field will unlock so you can correct the details and submit it again.</p>

                    @if($finalKycStatus === 'awaiting-approval')
                        <div class="kyc-review-panel mt-4">
                            <div class="d-flex align-items-start gap-3">
                                <span class="kyc-review-icon">
                                    <i class="bx bx-time-five"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <span class="badge bg-label-warning mb-2">Awaiting review</span>
                                    <h6 class="mb-1">Verification details submitted</h6>
                                    <p class="text-muted small mb-0">Your KYC information has been received and is waiting for an administrator to review it.</p>
                                </div>
                            </div>
                            <div class="kyc-review-action mt-3 pt-3">
                                <p class="small mb-3">Notify our verification team on WhatsApp so they can locate your submission and begin the review.</p>
                            @if($adminWhatsappUrl)
                                <a href="{{ $adminWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success w-100">
                                    <i class="bx bxl-whatsapp me-1"></i> Notify verification team
                                </a>
                            @else
                                <div class="alert alert-danger py-2 px-3 mb-0 small">WhatsApp notifications are temporarily unavailable. Your submission remains safely queued for review.</div>
                            @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-id-card fs-4"></i></span>
                    <div><h5 class="mb-1">Verification details</h5><small class="text-muted">Submit accurate information that matches your identification.</small></div>
                </div>
                <div class="card-body">
                    <form action="{{ route('update.kyc.details.process') }}" method="POST" enctype="multipart/form-data" autocomplete="off" class="customer-modern-form">
                        @csrf
                        <div class="row g-3">
                            @php
                                $fields = [
                                    'FIRST_NAME' => ['label' => 'First name', 'value' => auth()->user()->firstname],
                                    'MIDDLE_NAME' => ['label' => 'Middle name', 'value' => auth()->user()->middlename],
                                    'LAST_NAME' => ['label' => 'Last name', 'value' => auth()->user()->lastname],
                                    'DOB' => ['label' => 'Date of birth', 'value' => data_get(kycStatus('DOB', $customer->id), 'value', '')],
                                    'BVN' => ['label' => 'BVN', 'value' => ''],
                                ];
                            @endphp

                            @foreach($fields as $key => $field)
                                @php
                                    $fieldData = $kycField($key);
                                    $status = data_get($fieldData, 'status', 'unverified');
                                    $value = data_get($fieldData, 'value', $field['value']);
                                    $reviewNote = data_get($fieldData, 'review_note');
                                    $displayValue = $key === 'BVN' ? starMiddle($value) : $value;
                                    $fieldLocked = in_array($status, ['verified', 'in-review'], true);
                                @endphp
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <label class="form-label mb-0">{{ $field['label'] }}</label>
                                            @if($key === 'BVN')
                                                <a
                                                    href="https://airtime2cash.com/what-the-cbns-circular-means-for-financial-services-in-nigeria/"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-muted"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Learn more about the BVN requirement"
                                                    aria-label="Learn more about the BVN requirement"
                                                >
                                                    <i class="icon-base bx bx-info-circle"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge {{ $status === 'verified' ? 'bg-label-success' : ($status === 'in-review' ? 'bg-label-info' : ($status === 'declined' ? 'bg-label-danger' : 'bg-label-warning')) }}">
                                                {{ $statusLabel($status) }}
                                            </span>
                                            @if($status === 'declined' && filled($reviewNote))
                                                <button type="button" class="btn btn-link btn-sm p-0 view-kyc-reason-btn" data-field="{{ $field['label'] }}" data-reason="{{ e($reviewNote) }}">View reason</button>
                                            @endif
                                        </div>
                                    </div>
                                    @if($fieldLocked)
                                        <input type="text" class="form-control" value="{{ $displayValue }}" disabled>
                                    @elseif($key === 'DOB')
                                        <input type="date" class="form-control" name="DOB" value="{{ $value }}" required>
                                    @elseif($key === 'BVN')
                                        <input type="text" class="form-control" name="BVN" value="" autocomplete="off" inputmode="numeric" pattern="[0-9]{11}" placeholder="Enter your 11-digit BVN" minlength="11" maxlength="11" required>
                                    @else
                                        <input type="text" class="form-control" name="{{ $key }}" value="{{ filled($value) ? $value : $field['value'] }}" required>
                                    @endif
                                </div>
                            @endforeach

                            <div class="col-md-6">
                                @php $phoneKyc = $kycField('PHONE_NUMBER'); @endphp
                                @php $phoneKycStatus = data_get($phoneKyc, 'status', 'unverified'); @endphp
                                @php $phoneKycNote = data_get($phoneKyc, 'review_note'); @endphp
                                <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                    <label class="form-label mb-0">Phone number</label>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge {{ $phoneKycStatus === 'verified' ? 'bg-label-success' : ($phoneKycStatus === 'in-review' ? 'bg-label-info' : ($phoneKycStatus === 'declined' ? 'bg-label-danger' : 'bg-label-warning')) }}">
                                            {{ $statusLabel($phoneKycStatus) }}
                                        </span>
                                        @if($phoneKycStatus === 'declined' && filled($phoneKycNote))
                                            <button type="button" class="btn btn-link btn-sm p-0 view-kyc-reason-btn" data-field="Phone number" data-reason="{{ e($phoneKycNote) }}">View reason</button>
                                        @endif
                                    </div>
                                </div>
                                <input type="text" class="form-control" value="{{ auth()->user()->phone }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email address</label>
                                <input class="form-control" value="{{ auth()->user()->email }}" disabled>
                            </div>

                            <div class="col-md-6">
                                @php $idTypeKyc = $kycField('IDCARDTYPE'); @endphp
                                @php $idTypeStatus = data_get($idTypeKyc, 'status', 'unverified'); @endphp
                                @php $idTypeNote = data_get($idTypeKyc, 'review_note'); @endphp
                                @php $idTypeLocked = in_array($idTypeStatus, ['verified', 'in-review'], true); @endphp
                                <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                    <label for="IDCARDTYPE" class="form-label mb-0">ID card type</label>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge {{ $idTypeStatus === 'verified' ? 'bg-label-success' : ($idTypeStatus === 'in-review' ? 'bg-label-info' : ($idTypeStatus === 'declined' ? 'bg-label-danger' : 'bg-label-warning')) }}">
                                            {{ $statusLabel($idTypeStatus) }}
                                        </span>
                                        @if($idTypeStatus === 'declined' && filled($idTypeNote))
                                            <button type="button" class="btn btn-link btn-sm p-0 view-kyc-reason-btn" data-field="ID card type" data-reason="{{ e($idTypeNote) }}">View reason</button>
                                        @endif
                                    </div>
                                </div>
                                @if($idTypeLocked)
                                    <input type="text" class="form-control" value="{{ $kycFieldValue('IDCARDTYPE') }}" disabled>
                                @else
                                    <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Nin Slip" {{ $kycFieldValue('IDCARDTYPE') == 'Nin Slip' ? 'selected' : '' }}>Nin Slip</option>
                                        <option value="International Passport" {{ $kycFieldValue('IDCARDTYPE') == 'International Passport' ? 'selected' : '' }}>International Passport</option>
                                        <option value="Driver's Licence" {{ $kycFieldValue('IDCARDTYPE') == "Driver's Licence" ? 'selected' : '' }}>Driver's Licence</option>
                                        <option value="Voter's Card" {{ $kycFieldValue('IDCARDTYPE') == "Voter's Card" ? 'selected' : '' }}>Voter's Card</option>
                                    </select>
                                @endif
                            </div>

                            <div class="col-md-6">
                                @php $idCardKyc = $kycField('IDCARD'); @endphp
                                @php $idCardStatus = data_get($idCardKyc, 'status', 'unverified'); @endphp
                                @php $idCardValue = data_get($idCardKyc, 'value', ''); @endphp
                                @php $idCardNote = data_get($idCardKyc, 'review_note'); @endphp
                                @php $idCardLocked = in_array($idCardStatus, ['verified', 'in-review'], true); @endphp
                                <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                    <label for="IDCARD" class="form-label mb-0">Identity document</label>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge {{ $idCardStatus === 'verified' ? 'bg-label-success' : ($idCardStatus === 'in-review' ? 'bg-label-info' : ($idCardStatus === 'declined' ? 'bg-label-danger' : 'bg-label-warning')) }}">
                                            {{ $statusLabel($idCardStatus) }}
                                        </span>
                                        @if($idCardStatus === 'declined' && filled($idCardNote))
                                            <button type="button" class="btn btn-link btn-sm p-0 view-kyc-reason-btn" data-field="Identity document" data-reason="{{ e($idCardNote) }}">View reason</button>
                                        @endif
                                    </div>
                                </div>

                                @if($idCardLocked)
                                    @if(filled($idCardValue))
                                        <div class="mb-75">
                                            <img src="{{ asset($idCardValue) }}" alt="Submitted identity document" onclick="zoomKycDocument(this)" style="max-width: 150px; width: 150px; cursor: zoom-in; border-radius: 8px; border: 1px solid #ddd;">
                                        </div>
                                    @endif
                                    <input type="text" class="form-control" value="{{ $kycFieldValue('IDCARDTYPE') }}" disabled>
                                @else
                                    <input
                                        type="file"
                                        name="IDCARD"
                                        accept=".jpg,.jpeg,image/jpeg"
                                        class="form-control"
                                        required
                                    >
                                @endif

                                <div class="form-text text-danger">
                                    You may upload a screenshot or clear photo of your ID card. Ensure all details are clearly visible.
                                    <strong>Maximum file size: 500 KB.</strong>
                                </div>
                            </div>
                        </div>

                        @if($canFundWallet)
                            <div class="customer-form-actions mt-4 mx-n4 mb-n4">
                                <a href="{{ route('customer.load.wallet') }}" class="btn btn-success customer-form-submit">Fund wallet</a>
                            </div>
                        @elseif($hasEditableKycField)
                            <div class="customer-form-actions mt-4 mx-n4 mb-n4">
                                <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-check-circle me-1"></i> {{ $kycSubmitLabel }}</button>
                            </div>
                        @else
                            <div class="alert alert-info mt-4 mb-0">
                                <strong>Your KYC submission is under review.</strong> The fields you submitted are locked until an administrator completes the review.
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kycReasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Field rejection reason</h5>
                        <small class="text-muted" id="kycReasonModalField"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-0" id="kycReasonModalText"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kycDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Identity document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="kycDocumentModalImage" src="" alt="Identity document preview" style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @parent
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

        if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }
    </script>
@endsection
