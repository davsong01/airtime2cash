<div class="customer-section-heading">
    <div>
        <h4>Account information</h4>
        <p>Manage the customer's identity, account state, contact number, and service level.</p>
    </div>
    <span class="badge {{ $user->status === 'active' ? 'badge-light-success' : 'badge-light-danger' }} px-1 py-50">{{ ucfirst($user->status ?: 'Unknown') }}</span>
</div>

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
            <label for="email">Email address</label>
            <input type="email" class="form-control" id="email" value="{{ $user->email }}" disabled>
        </div>
        <div class="col-md-6 form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" value="{{ $user->username }}" disabled>
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
        <div class="col-md-6 form-group mb-md-0">
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
        <div class="col-md-6 d-flex align-items-end justify-content-md-end">
            <button type="submit" class="btn btn-success"><i class="bx bx-save mr-25"></i> Save account changes</button>
        </div>
    </div>
</form>
