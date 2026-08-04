<?php use App\Models\Category; ?>
@if (auth()->user()->email_verified_at)
<aside class="d-none d-xl-flex flex-column position-fixed top-0 start-0 vh-100 bg-white border-end shadow-sm ui-side-shell" style="width: 300px; z-index: 1030;">
    <div class="p-4 border-bottom">
        <a class="d-flex align-items-center text-decoration-none" href="{{ route('dashboard') }}">
            <img src="{{ asset(getSettings()->dashboard_logo) }}" alt="{{ config('app.name') }}" style="max-height: 56px; max-width: 190px; object-fit: contain;">
        </a>
        <div class="mt-3 small text-uppercase text-muted">New UI Mode</div>
        <div class="fw-semibold text-dark">Customer workspace</div>
    </div>

    <div class="flex-grow-1 overflow-auto p-3">
        @php
            $balance = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(walletBalance(auth()->user()), 2) : 0;
            $categories = getCategories();
            $wallet2bank = App\Models\Product::where('id', env('TRANSFER_TO_BANK_PRODUCT_ID'))->where('status','active')->first();
        @endphp

        <div class="rounded-4 p-3 mb-3 text-white" style="background: linear-gradient(135deg, #0f172a, #0f766e);">
            <div class="small opacity-75">Wallet balance</div>
            <div class="fs-4 fw-bold">{{ $balance }}</div>
            <div class="small opacity-75">Level: {{ auth()->user()->customer?->level?->name ?? 'N/A' }}</div>
        </div>

        <div class="text-uppercase small text-muted fw-semibold mb-2">Make payment</div>
        <div class="d-grid gap-2 mb-4">
            @foreach($categories as $category)
                <a href="{{ route('open.transaction.page', $category->slug) }}" class="btn btn-light border text-start rounded-4 py-3">
                    @if($category->icon)
                        {!! $category->icon !!}
                    @endif
                    <span class="ms-2">{{ $category->display_name }}</span>
                </a>
            @endforeach
            @if(Category::where('type', 'airtime2cash')->where('status','active')->exists())
                <a href="{{ route('airtime-to-cash') }}" class="btn btn-success text-start rounded-4 py-3">
                    <i class="bi bi-lightning-charge"></i>
                    <span class="ms-2">Airtime to Cash</span>
                </a>
            @endif
            @if($wallet2bank)
                <a href="{{ route('wallet-to-bank', $wallet2bank->slug )}}" class="btn btn-outline-primary text-start rounded-4 py-3">
                    <i class="bi bi-bank"></i>
                    <span class="ms-2">{{ $wallet2bank->display_name }}</span>
                </a>
            @endif
        </div>

        <div class="text-uppercase small text-muted fw-semibold mb-2">Self service</div>
        <div class="list-group list-group-flush">
            <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action rounded-3 mb-1">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action rounded-3 mb-1">My Profile</a>
            <a href="{{ route('customer.load.wallet') }}" class="list-group-item list-group-item-action rounded-3 mb-1">Fund Wallet</a>
            <a href="{{ route('customer.transaction.history') }}" class="list-group-item list-group-item-action rounded-3 mb-1">Transactions</a>
            <a href="{{ route('customer.airtime2cash.transaction.history') }}" class="list-group-item list-group-item-action rounded-3 mb-1">A2Cash History</a>
            <a href="{{ route('update.kyc.details') }}" class="list-group-item list-group-item-action rounded-3 mb-1">KYC Info</a>
            @if(!empty(getSettings()->support_link))
                <a target="_blank" href="{{ getSettings()->support_link}}" class="list-group-item list-group-item-action rounded-3 mb-1">Contact Us</a>
            @endif
            <a href="{{ route('logout')}}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="list-group-item list-group-item-action rounded-3 mb-1 text-danger">Logout</a>
        </div>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside>
@endif
