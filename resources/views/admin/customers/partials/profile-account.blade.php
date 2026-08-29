<div class="customer-section-heading">
    <div>
        <h4>Account information</h4>
        <p>Manage the customer's identity, account state, contact number, and service level.</p>
    </div>
    <span class="badge {{ $user->status === 'active' ? 'badge-light-success' : 'badge-light-danger' }} px-1 py-50">{{ ucfirst($user->status ?: 'Unknown') }}</span>
</div>

@php
    $walletBankAccount = is_array($customer->wallet_bank_account ?? null) ? $customer->wallet_bank_account : [];
    $walletBankAccountReady = ! empty($walletBankAccount);
    $walletVerifiedAtValue = filled(data_get($walletBankAccount, 'verified_at'))
        ? \Carbon\Carbon::parse(data_get($walletBankAccount, 'verified_at'))->format('Y-m-d\TH:i')
        : '';
    $walletVerificationResponseValue = data_get($walletBankAccount, 'verification_response');
    $walletVerificationResponseValue = is_array($walletVerificationResponseValue)
        ? json_encode($walletVerificationResponseValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        : (string) $walletVerificationResponseValue;
    $walletBankVerifyUrl = route('admin.verify.bank.details');
@endphp

<form action="{{ route('customers.update', $user->id) }}" method="POST" class="customer-form-panel">
    @csrf
    <div class="row">
        <div class="col-md-6 form-group">
            <label for="firstname">First name</label>
            <input type="text" class="form-control" id="firstname" name="firstname" value="{{ old('firstname', $user->firstname) }}" required>
        </div>
        <div class="col-md-6 form-group">
            <label for="lastname">Last name</label>
            <input type="text" class="form-control" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname) }}" required>
        </div>
        <div class="col-md-6 form-group">
            <label for="phone">Phone number</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="col-md-6 form-group">
            <label for="status">Account status</label>
            <select name="status" class="form-control" id="status" required>
                <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                <option value="delete" @selected(old('status', $user->status) === 'delete')>Deleted</option>
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label for="customerlevel">Customer level</label>
            <select name="customerlevel" class="form-control" id="customerlevel">
                @foreach ($customerLevels as $level)
                    <option value="{{ $level->id }}" @selected((int) old('customerlevel', $customer->customer_level) === (int) $level->id)>{{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label for="can_access_a2c">Airtime 2 Cash access</label>
            <select name="can_access_a2c" class="form-control" id="can_access_a2c">
                <option value="0" @selected(! (bool) old('can_access_a2c', $customer->can_access_a2c ?? false))>Disabled</option>
                <option value="1" @selected((bool) old('can_access_a2c', $customer->can_access_a2c ?? false))>Enabled</option>
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label for="can_access_w2bank_auto">Auto Wallet 2 Bank access</label>
            <select name="can_access_w2bank_auto" class="form-control" id="can_access_w2bank_auto">
                <option value="0" @selected(! (bool) old('can_access_w2bank_auto', $customer->can_access_w2bank_auto ?? false))>Disabled</option>
                <option value="1" @selected((bool) old('can_access_w2bank_auto', $customer->can_access_w2bank_auto ?? false))>Enabled</option>
            </select>
        </div>
        <div class="col-md-6 form-group">
            <label for="can_access_w2bank">Manual Wallet 2 Bank access</label>
            <select name="can_access_w2bank" class="form-control" id="can_access_w2bank">
                <option value="0" @selected(! (bool) old('can_access_w2bank', $customer->can_access_w2bank ?? true))>Disabled</option>
                <option value="1" @selected((bool) old('can_access_w2bank', $customer->can_access_w2bank ?? true))>Enabled</option>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-success"><i class="bx bx-save mr-25"></i> Save account changes</button>
        </div>
    </div>
</form>

<div class="customer-section-heading mt-4">
    <div>
        <h4>Wallet to bank account</h4>
        <p>Edit the customer's locked payout destination, verification metadata, or remove it entirely.</p>
    </div>
    <span class="badge {{ $walletBankAccountReady ? 'badge-light-success' : 'badge-light-warning' }} px-1 py-50">{{ $walletBankAccountReady ? 'Saved' : 'Not set' }}</span>
</div>

<div class="rounded-4" style="background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,255,.98)); border: 1px solid rgba(67,89,113,.12); box-shadow: 0 10px 28px rgba(67,89,113,.06);">

    <form action="{{ route('customers.wallet-bank-account.update', $customer->id) }}" method="POST" class="customer-form-panel">
        @csrf
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="wallet_bank_bank">Bank</label>
                <select class="form-control js-example-basic-single" id="wallet_bank_bank" name="wallet_bank_bank" required>
                    <option value="">Select bank</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank->cbn_code }}" @selected((string) old('wallet_bank_bank', data_get($walletBankAccount, 'bank_code')) === (string) $bank->cbn_code)>
                            {{ $bank->bank_name }} ({{ $bank->cbn_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="wallet_bank_account_number">Account number</label>
                <input type="text" class="form-control" id="wallet_bank_account_number" name="wallet_bank_account_number" value="{{ old('wallet_bank_account_number', data_get($walletBankAccount, 'account_number')) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="wallet_bank_account_name">Account name</label>
                <input type="text" class="form-control" id="wallet_bank_account_name" name="wallet_bank_account_name" value="{{ old('wallet_bank_account_name', data_get($walletBankAccount, 'account_name')) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="wallet_bank_profile_name">Profile name</label>
                <input type="text" class="form-control" id="wallet_bank_profile_name" name="wallet_bank_profile_name" value="{{ old('wallet_bank_profile_name', data_get($walletBankAccount, 'profile_name')) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="wallet_bank_verified_name">Verified name</label>
                <input type="text" class="form-control" id="wallet_bank_verified_name" name="wallet_bank_verified_name" value="{{ old('wallet_bank_verified_name', data_get($walletBankAccount, 'verified_name')) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="wallet_bank_verified_at">Verified at</label>
                <input type="datetime-local" class="form-control" id="wallet_bank_verified_at" name="wallet_bank_verified_at" value="{{ old('wallet_bank_verified_at', $walletVerifiedAtValue) }}">
            </div>
            
            <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3 mt-1">
                <small class="text-muted">Use the current draft values to verify the account before saving.</small>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-info" id="verify-wallet-account-draft-btn" data-verify-url="{{ $walletBankVerifyUrl }}" data-customer-name="{{ trim($user->firstname . ' ' . $user->middlename . ' ' . $user->lastname) }}"><i class="bx bx-search-alt mr-25"></i> Verify account only</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-check-shield mr-25"></i> Save</button>
                </div>
            </div>
        </div>
    </form>

    <div class="px-3 pb-3">
        <div id="wallet-bank-verify-result" class="mt-3" style="display:none;"></div>
    </div>

    @if($walletBankAccountReady)
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4 pt-3 border-top p-1">
            <div class="text-muted">
                Delete this locked wallet account if the customer needs to register a new destination.
            </div>
            <form action="{{ route('customers.wallet-bank-account.delete', $customer->id) }}" method="POST" onsubmit="return confirm('Delete the locked wallet to bank account details for this customer?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash mr-25"></i> Delete locked account</button>
            </form>
        </div>
    @endif
</div>
