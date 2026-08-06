@extends('sneat.layouts.app')
@section('title', 'Edit KYC data')

@section('content')
    @php
        $customer = auth()->user()->customer;
        $finalKycStatus = getFinalKycStatus($customer->id);
        $kycIsLocked = $finalKycStatus === 'awaiting-approval';
        $settings = getSettings();
        $adminWhatsappNumber = preg_replace('/\D+/', '', (string) $settings->whatsapp_number);
        $submittedIdType = kycStatus('IDCARDTYPE', $customer->id)['value'] ?? 'Not provided';
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

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Status</h5>
                    <div class="d-grid gap-2">
                        <span class="badge bg-label-primary p-2">KYC: {{ ucfirst($finalKycStatus) }}</span>
                        <span class="badge bg-label-secondary p-2">Email: On file</span>
                        <span class="badge bg-label-secondary p-2">Phone: {{ kycStatus('PHONE_NUMBER', $customer->id)['status'] }}</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">If the submission is declined, this form will unlock so you can correct the details and submit it again.</p>

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
                                    'PHONE_NUMBER' => ['label' => 'Phone number', 'value' => auth()->user()->phone],
                                    'BVN' => ['label' => 'BVN', 'value' => ''],
                                ];
                            @endphp

                            @foreach($fields as $key => $field)
                                @php
                                    $status = kycStatus($key, $customer->id)['status'] ?? 'unverified';
                                    $value = kycStatus($key, $customer->id)['value'] ?? $field['value'];
                                    $displayValue = $key === 'BVN' ? starMiddle($value) : $value;
                                @endphp
                                <div class="col-md-6">
                                    <label class="form-label">{{ $field['label'] }}</label>
                                    @if($status === 'verified' || $kycIsLocked)
                                        <input type="text" class="form-control" value="{{ $displayValue }}" disabled>
                                    @elseif($key === 'BVN')
                                        <input type="text" class="form-control" name="BVN" value="" autocomplete="off" inputmode="numeric" pattern="[0-9]{11}" placeholder="Enter your 11-digit BVN" minlength="11" maxlength="11" required>
                                    @else
                                        <input type="text" class="form-control" name="{{ $key }}" value="{{ $field['value'] }}" required>
                                    @endif
                                </div>
                            @endforeach

                            <div class="col-md-6">
                                <label for="IDCARDTYPE" class="form-label">ID card type</label>
                                @if(kycStatus('IDCARD', $customer->id)['status'] == 'verified' || $kycIsLocked)
                                    <input type="text" class="form-control" value="{{ kycStatus('IDCARDTYPE', $customer->id)['value'] }}" disabled>
                                @else
                                    <select id="IDCARDTYPE" name="IDCARDTYPE" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Nin Slip" {{ kycStatus('IDCARDTYPE', $customer->id)['value'] == 'Nin Slip' ? 'selected' : '' }}>Nin Slip</option>
                                        <option value="International Passport" {{ kycStatus('IDCARDTYPE', $customer->id)['value'] == 'International Passport' ? 'selected' : '' }}>International Passport</option>
                                        <option value="Driver's Licence" {{ kycStatus('IDCARDTYPE', $customer->id)['value'] == "Driver's Licence" ? 'selected' : '' }}>Driver's Licence</option>
                                        <option value="Voter's Card" {{ kycStatus('IDCARDTYPE', $customer->id)['value'] == "Voter's Card" ? 'selected' : '' }}>Voter's Card</option>
                                    </select>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="IDCARD" class="form-label">ID Card</label>

                                @if(kycStatus('IDCARD', $customer->id)['status'] == 'verified' || $kycIsLocked)
                                    <input type="text" class="form-control" value="{{ kycStatus('IDCARDTYPE', $customer->id)['value'] }}" disabled>
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

                            <div class="col-12">
                                <label class="form-label">Email address</label>
                                <input class="form-control" value="{{ auth()->user()->email }}" disabled>
                            </div>
                        </div>

                        @if(in_array($finalKycStatus, ['unverified', 'verified'], true))
                            <div class="customer-form-actions mt-4 mx-n4 mb-n4">
                                @if($finalKycStatus == 'unverified')
                                <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-check-circle me-1"></i> Submit KYC</button>
                                @else
                                <a href="{{ route('customer.load.wallet') }}" class="btn btn-success customer-form-submit">Fund wallet</a>
                                @endif
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
