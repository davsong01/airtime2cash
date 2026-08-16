@php
    $emailBlacklist = $blacklists->get($user->email);
    $phoneBlacklist = $blacklists->get($user->phone);
@endphp

<div class="customer-section-heading">
    <div>
        <h4>Security and risk controls</h4>
        <p>Manage blacklist rules and permission-gated account recovery actions.</p>
    </div>
</div>

<div class="row match-height">
    @foreach([
        ['title' => 'Email blacklist', 'value' => $user->email, 'type' => 'email', 'record' => $emailBlacklist, 'icon' => 'bx bx-envelope'],
        ['title' => 'Phone blacklist', 'value' => $user->phone, 'type' => 'biller', 'record' => $phoneBlacklist, 'icon' => 'bx bx-phone'],
    ] as $risk)
        <div class="col-md-6 mb-1">
            <div class="customer-risk-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="d-flex">
                        <span class="customer-risk-icon mr-75"><i class="{{ $risk['icon'] }}"></i></span>
                        <div><h6 class="mb-25">{{ $risk['title'] }}</h6><small class="text-muted">{{ $risk['value'] ?: 'Not available' }}</small></div>
                    </div>
                    @if($risk['record'])
                        <form action="{{ route('customer-blacklist.destroy', $risk['record']->id) }}" method="POST" onsubmit="return confirm('Remove this item from the blacklist?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash mr-25"></i> Remove</button>
                        </form>
                    @endif
                </div>
                @unless($risk['record'])
                    <form action="{{ route('customer-blacklist.store') }}" method="POST" class="mt-1">
                        @csrf
                        <input type="hidden" name="type" value="{{ $risk['type'] }}">
                        <input type="hidden" name="value" value="{{ $risk['value'] }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm" @disabled(!$risk['value'])><i class="bx bx-block mr-25"></i> Add to blacklist</button>
                    </form>
                @endunless
            </div>
        </div>
    @endforeach

    @if(hasAccess('admin.password.reset'))
        <div class="col-md-6 mb-1">
            <div class="customer-risk-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center"><span class="customer-risk-icon mr-75"><i class="bx bx-lock-open"></i></span><div><h6 class="mb-25">Password reset</h6><small class="text-muted">Set a temporary customer password.</small></div></div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#reset-password">Reset</button>
            </div>
        </div>
    @endif

    @if(hasAccess('admin.transaction.pin.reset'))
        <div class="col-md-6 mb-1">
            <div class="customer-risk-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center"><span class="customer-risk-icon mr-75"><i class="bx bx-key"></i></span><div><h6 class="mb-25">Transaction PIN</h6><small class="text-muted">Replace the customer's transaction PIN.</small></div></div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#reset-transaction-pin">Reset</button>
            </div>
        </div>
    @endif
</div>
