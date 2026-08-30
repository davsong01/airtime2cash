@if($activeTab === 'reserved-account')
    <div class="modal fade text-left" id="reserved" tabindex="-1" role="dialog" aria-labelledby="reserved-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="reserved-title">Create reserved account</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                </div>
                <form action="{{ route('create.reserved.account', $customer->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Choose one or more banks for {{ $fullName }}.</p>
                        <div class="form-group">
                            <label for="bank">Banks</label>
                            <select class="form-control js-example-basic-single" name="bank[]" id="bank" required multiple>
                                @forelse($availableReservedBanks ?? collect() as $bank)
                                    <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                @empty
                                    <option value="" disabled>No additional banks available</option>
                                @endforelse
                            </select>
                        </div>
                        @if(empty(($availableReservedBanks ?? collect())->count()))
                            <div class="alert alert-info mt-2 mb-0">
                                This customer already has reserved accounts for all available banks.
                            </div>
                        @endif
                        <input type="hidden" name="bvn" value="{{ $kycData->get('BVN')->value ?? '' }}">
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create accounts</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($activeTab === 'actions' && hasAccess('admin.transaction.pin.reset'))
    <div class="modal fade text-left" id="reset-transaction-pin" tabindex="-1" role="dialog" aria-labelledby="reset-pin-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="reset-pin-title">Reset transaction PIN</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                </div>
                <form action="{{ route('admin.transaction.pin.reset', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Enter a new transaction PIN for {{ $fullName }}. The customer will receive an email notification.</p>
                        <div class="form-group mb-0">
                            <label for="new_transaction_pin">New transaction PIN</label>
                            <input type="text" class="form-control" id="new_transaction_pin" name="new_transaction_pin" value="{{ old('new_transaction_pin') }}" inputmode="numeric" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset PIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($activeTab === 'actions' && hasAccess('admin.password.reset'))
    <div class="modal fade text-left" id="reset-password" tabindex="-1" role="dialog" aria-labelledby="reset-password-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="reset-password-title">Reset customer password</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                </div>
                <form action="{{ route('admin.password.reset', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Set a temporary password for {{ $fullName }}. The customer will receive an email notification.</p>
                        <div class="form-group mb-0">
                            <label for="new_password">New password</label>
                            <input type="text" class="form-control" id="new_password" name="new_password" value="{{ old('new_password') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($activeTab === 'kyc')
    @php
        $kycBvnData = (array) ($customer->bvn_data ?? []);
        $kycBvnProfileName = data_get($kycBvnData, 'profile_name');
        $kycBvnVerifiedName = data_get($kycBvnData, 'verified_name');
        $kycBvnNameMatch = (bool) data_get($kycBvnData, 'name_match', false);
        $kycBvnResponse = data_get($kycBvnData, 'response', []);
    @endphp

    <div class="modal fade" id="kyc-document-modal" tabindex="-1" role="dialog" aria-labelledby="kyc-document-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kyc-document-title">Submitted identity document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="kyc-document-preview" src="" alt="Identity document preview" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kyc-field-review-modal" tabindex="-1" role="dialog" aria-labelledby="kyc-field-review-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="kyc-field-review-title">Review KYC field</h5>
                        <small class="text-muted">Approve or reject the selected field with a short note if needed.</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" id="kyc-field-review-feedback"></div>
                    <div class="form-group">
                        <label>Field</label>
                        <input type="text" class="form-control" id="kyc-review-field-label" disabled>
                    </div>
                    <div class="form-group mb-0">
                        <label for="kyc-review-reason">Rejection note <small class="text-muted">(optional)</small></label>
                        <textarea class="form-control" id="kyc-review-reason" rows="4" placeholder="Optional note for the customer." maxlength="2000"></textarea>
                    </div>
                    <input type="hidden" id="kyc-review-field">
                    <input type="hidden" id="kyc-review-action">
                    <input type="hidden" id="kyc-review-value">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="kyc-review-confirm-approve">Approve</button>
                    <button type="button" class="btn btn-danger" id="kyc-review-confirm-reject">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bvnVerificationResultModal" tabindex="-1" role="dialog" aria-labelledby="bvnVerificationResultTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bvnVerificationResultTitle">BVN verification result</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="bvn-verification-loading" class="{{ filled($kycBvnResponse) ? 'd-none' : '' }}">
                        <div class="d-flex align-items-center justify-content-center py-5">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                                <h6 class="mb-1">Verifying BVN details...</h6>
                                <p class="text-muted mb-0">Please wait while we contact the verification provider.</p>
                            </div>
                        </div>
                    </div>

                    <div id="bvn-verification-result" class="{{ filled($kycBvnResponse) ? '' : 'd-none' }}">
                        <div class="row">
                            <div class="col-md-4 mb-1">
                                <div class="border rounded p-1 h-100">
                                    <small class="text-muted d-block">Profile name</small>
                                    <strong id="bvn-result-profile-name">{{ $kycBvnProfileName ?: 'N/A' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4 mb-1">
                                <div class="border rounded p-1 h-100">
                                    <small class="text-muted d-block">BVN name</small>
                                    <strong id="bvn-result-verified-name">{{ $kycBvnVerifiedName ?: 'N/A' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4 mb-1">
                                <div class="border rounded p-1 h-100">
                                    <small class="text-muted d-block">Match status</small>
                                    <strong id="bvn-result-match-status" class="{{ $kycBvnNameMatch ? 'text-success' : 'text-danger' }}">{{ $kycBvnNameMatch ? 'Names match' : 'Names do not match' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-0">
                            <div class="card-body">
                                <pre id="bvn-result-json" class="mb-0" style="white-space: pre-wrap; word-break: break-word;">{!! json_encode($kycBvnResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.adminBvnVerificationState = {
            profileName: @json($kycBvnProfileName ?: ''),
            verifiedName: @json($kycBvnVerifiedName ?: ''),
            nameMatch: @json($kycBvnNameMatch),
            response: @json($kycBvnResponse),
            status: @json(data_get($kycBvnData, 'status', $customer->bvn_verification_status ?: 'unverified')),
            verifiedAt: @json(data_get($kycBvnData, 'verified_at', '')),
        };
    </script>
@endif
