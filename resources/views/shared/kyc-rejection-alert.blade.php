@php
    $kycRejectionReason = auth()->user()->customer?->kyc_rejection_reason;
@endphp

@if(filled($kycRejectionReason))
    <div class="alert alert-danger kyc-rejection-alert" role="alert">
        <div class="d-flex align-items-start">
            <i class="bx bx-error-circle mr-75 me-2" aria-hidden="true"></i>
            <div class="flex-grow-1">
                <strong class="d-block mb-50 mb-1">Your KYC submission needs correction</strong>
                <p class="mb-75 mb-2">{{ $kycRejectionReason }}</p>
                @unless(request()->routeIs('update.kyc.details'))
                    <a href="{{ route('update.kyc.details') }}" class="btn btn-danger btn-sm">Review and resubmit KYC</a>
                @endunless
            </div>
        </div>
    </div>
@endif
