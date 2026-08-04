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
                                <option value="50515">Moniepoint</option>
                                <option value="035">Wema Bank</option>
                                <option value="058">Guaranty Trust Bank</option>
                            </select>
                        </div>
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
@endif
