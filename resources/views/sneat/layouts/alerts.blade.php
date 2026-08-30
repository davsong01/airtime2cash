@php
    $alerts = [
        'message' => ['type' => 'success'],
        'error' => ['type' => 'danger'],
        'any' => ['type' => 'warning'],
        'warning' => ['type' => 'warning'],
        'unverified' => ['type' => 'warning'],
        'welcomeback' => ['type' => 'success'],
        'resent' => ['type' => 'success'],
        'verified' => ['type' => 'success'],
        'status' => ['type' => 'success'],
    ];
@endphp

@foreach($alerts as $key => $config)
    @if(session()->has($key))
        <div class="alert alert-{{ $config['type'] }} alert-dismissible fade show" role="alert">
            @if(is_array(session()->get($key)))
                @foreach(session()->get($key) as $item)
                    {{ $item }}<br>
                @endforeach
            @else
                {!! session()->get($key) !!}
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if(isset($errors) && $errors->any())
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        @foreach($errors->all() as $error)
            {{ $error }}<br>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php
    $shouldShowWalletBankAccountAlert = auth()->user()?->type === 'customer'
        && empty(auth()->user()?->customer?->wallet_bank_account)
        && (
            request()->routeIs('profile.edit')
            || request()->routeIs('wallet-to-bank')
        );
@endphp

@if($shouldShowWalletBankAccountAlert)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Hi, you have not filled your wallet to bank account details in order to do wallet to bank transfer, <strong>click <a href="{{ route('profile.edit') }}#wallet-to-bank-account">HERE</a> to fill it now</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
