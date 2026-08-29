@extends('sneat.layouts.app')

@section('title', 'Dashboard')

@section('page-css')
    <style>
        .dashboard-balance-card {
            background:
                radial-gradient(circle at 88% 12%, rgba(99, 102, 241, .55), transparent 30%),
                radial-gradient(circle at 8% 110%, rgba(20, 184, 166, .32), transparent 38%),
                linear-gradient(135deg, #111827 0%, #1e293b 52%, #252760 100%);
            overflow: hidden;
            min-height: 292px;
            box-shadow: 0 1.25rem 2.75rem rgba(15, 23, 42, .24);
        }

        .customer-dashboard {
            --dashboard-glass-border: rgba(255, 255, 255, .66);
            --dashboard-glass-shadow: 0 1.35rem 3rem rgba(34, 48, 62, .09),
                0 .3rem .85rem rgba(34, 48, 62, .055);
            position: relative;
            overflow-x: clip;
            isolation: isolate;
        }

        .customer-dashboard::before {
            content: "";
            position: absolute;
            z-index: -1;
            width: min(55vw, 720px);
            height: min(55vw, 720px);
            top: 8rem;
            right: -10rem;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(var(--bs-primary-rgb), .105), transparent 68%);
            pointer-events: none;
        }

        .customer-dashboard .card:not(.dashboard-balance-card) {
            border: 1px solid var(--dashboard-glass-border);
            background: rgba(255, 255, 255, .8);
            background: linear-gradient(145deg, rgba(255, 255, 255, .9), rgba(255, 255, 255, .7));
            box-shadow: var(--dashboard-glass-shadow);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            backdrop-filter: blur(18px) saturate(140%);
        }

        .customer-dashboard .card:not(.dashboard-balance-card)::before {
            content: "";
            position: absolute;
            height: 1px;
            inset: 0 1.25rem auto;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .92), transparent);
            pointer-events: none;
        }

        [data-bs-theme="dark"] .customer-dashboard {
            --dashboard-glass-border: rgba(255, 255, 255, .08);
            --dashboard-glass-shadow: 0 1.35rem 3rem rgba(0, 0, 0, .26),
                0 .3rem .85rem rgba(0, 0, 0, .14);
        }

        [data-bs-theme="dark"] .customer-dashboard .card:not(.dashboard-balance-card) {
            background: linear-gradient(145deg, rgba(47, 51, 73, .87), rgba(38, 41, 60, .74));
        }

        .dashboard-balance-card::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            right: -105px;
            top: -155px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow:
                0 0 0 38px rgba(255, 255, 255, .035),
                0 0 0 76px rgba(255, 255, 255, .02);
        }

        .dashboard-balance-card::after {
            content: "";
            position: absolute;
            width: 50%;
            height: 1px;
            right: -8%;
            bottom: 34%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .18), transparent);
            transform: rotate(-24deg);
        }

        .dashboard-balance-card .card-body {
            z-index: 1;
        }

        .dashboard-wallet-mark {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: .9rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, .2), rgba(255, 255, 255, .08));
            color: #fff;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .16);
            backdrop-filter: blur(12px);
        }

        .dashboard-wallet-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: rgba(255, 255, 255, .7);
            font-size: .75rem;
        }

        .dashboard-wallet-status::before {
            content: "";
            width: .45rem;
            height: .45rem;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 0 .2rem rgba(52, 211, 153, .15);
        }

        .dashboard-wallet-balance {
            color: #fff;
            font-size: clamp(2.2rem, 4vw, 3.15rem);
            font-weight: 700;
            letter-spacing: -.045em;
            line-height: 1;
        }

        .dashboard-wallet-visibility,
        .dashboard-wallet-secondary-action {
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .09);
            color: #fff;
            backdrop-filter: blur(12px);
        }

        .dashboard-wallet-visibility {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .dashboard-wallet-visibility:hover,
        .dashboard-wallet-secondary-action:hover {
            border-color: rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .dashboard-wallet-primary-action {
            border-color: #fff;
            background: #fff;
            color: #20235b;
            box-shadow: 0 .5rem 1.25rem rgba(2, 6, 23, .18);
        }

        .dashboard-wallet-primary-action:hover {
            border-color: #fff;
            background: rgba(255, 255, 255, .92);
            color: #20235b;
            transform: translateY(-1px);
        }

        .dashboard-wallet-footer {
            border-top: 1px solid rgba(255, 255, 255, .12);
        }

        .dashboard-wallet-history {
            color: rgba(255, 255, 255, .78);
        }

        .dashboard-wallet-history:hover {
            color: #fff;
        }

        .dashboard-action {
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .dashboard-action:hover {
            border-color: rgba(var(--bs-primary-rgb), .22);
            transform: translateY(-5px);
            box-shadow: 0 1.25rem 2.35rem rgba(34, 48, 62, .14) !important;
        }

        .dashboard-action .dashboard-action-arrow {
            transition: transform .2s ease;
        }

        .dashboard-action:hover .dashboard-action-arrow {
            transform: translateX(3px);
        }

        .dashboard-action-icon,
        .dashboard-service-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .dashboard-action-icon {
            width: 46px;
            height: 46px;
            border-radius: .75rem;
        }

        .dashboard-service-icon {
            width: 42px;
            height: 42px;
            border-radius: .65rem;
            font-size: 1.25rem;
            color: var(--bs-primary);
        }

        .dashboard-service-icon > i {
            color: currentColor !important;
            font-size: 1.25rem !important;
            line-height: 1 !important;
        }

        .dashboard-service-icon > svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            fill: currentColor !important;
        }

        .dashboard-service-icon > svg path:not([fill="none"]),
        .dashboard-service-icon > svg [fill]:not([fill="none"]) {
            fill: currentColor !important;
        }

        .dashboard-service-icon > svg [stroke]:not([stroke="none"]) {
            stroke: currentColor !important;
        }

        .dashboard-service {
            border: 1px solid transparent;
            transition: background-color .2s ease, transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .dashboard-service:hover {
            border-color: rgba(var(--bs-primary-rgb), .12);
            background: rgba(var(--bs-primary-rgb), .055);
            box-shadow: 0 .45rem 1rem rgba(34, 48, 62, .055);
            transform: translateX(3px);
        }

        .dashboard-referral-link {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-action:focus-visible,
        .dashboard-service:focus-visible {
            outline: 3px solid rgba(var(--bs-primary-rgb), .22);
            outline-offset: 2px;
        }

        @media (max-width: 575.98px) {
            .dashboard-balance-card .card-body {
                padding: 1.5rem !important;
            }

            .dashboard-wallet-actions .btn {
                flex: 1 1 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .dashboard-action,
            .dashboard-service,
            .dashboard-action .dashboard-action-arrow {
                transition: none;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $user = auth()->user();
        $settings = getSettings();
        $currency = $settings->currency;
        $balance = $user->type === 'customer' ? $currency . number_format(walletBalance($user), 2) : $currency . '0.00';
        $referralBalance = $user->type === 'customer' ? $currency . number_format(referralBalance($user), 2) : $currency . '0.00';
        $levelName = $user->customer?->level?->name ?? 'Not assigned';
        $kycStatus = $user->kyc_status ?? 'pending';
        $kycLabel = str($kycStatus)->replace('-', ' ')->title();
        $kycBadge = in_array($kycStatus, ['verified', 'approved'], true) ? 'success' : ($kycStatus === 'declined' ? 'danger' : 'warning');
        $services = getCategories();
        $airtimeToCashActive = \App\Models\Category::where('type', 'airtime2cash')
            ->where('status', 'active')
            ->exists();
        $walletToBankProduct = \App\Models\Product::where('id', env('TRANSFER_TO_BANK_PRODUCT_ID'))
            ->where('status', 'active')
            ->first();
        $serviceCount = $services->count() + ($airtimeToCashActive ? 1 : 0) + ($walletToBankProduct ? 1 : 0);
        $referralLink = url('/register') . '?referral=' . $user->username;
        $firstName = $user->firstname ?: $user->username;
        $quickActions = [
            ['route' => route('customer.load.wallet'), 'label' => 'Fund Wallet', 'detail' => 'Add money', 'icon' => 'bx-wallet', 'color' => 'primary'],
        ];

        if ($airtimeToCashActive) {
            $quickActions[] = ['route' => route('airtime-to-cash'), 'label' => 'Airtime to Cash', 'detail' => 'Convert airtime', 'icon' => 'bx-transfer-alt', 'color' => 'success'];
        }

        $quickActions[] = ['route' => route('customer.transaction.report'), 'label' => 'Transactions', 'detail' => 'View history', 'icon' => 'bx-receipt', 'color' => 'info'];
        $quickActions[] = ['route' => route('update.kyc.details'), 'label' => 'KYC', 'detail' => 'Verification', 'icon' => 'bx-id-card', 'color' => 'warning'];
        $quickActionColumn = count($quickActions) === 4 ? 'col-lg-3' : 'col-lg-4';
    @endphp

    <div class="customer-dashboard">

    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Welcome back, {{ $firstName }}</h4>
            <p class="text-muted mb-0">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('customer.transaction.report') }}" class="btn btn-label-primary align-self-start align-self-sm-center">
            <i class="bx bx-receipt me-1"></i> Transaction History
        </a>
    </div>

    @include('sneat.layouts.alerts')
    @include('shared.kyc-rejection-alert')

    @if($settings->google_dashboard_ad_enabled ?? true)
        {!! $settings->google_dashboard_ad_code !!}
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card dashboard-balance-card border-0 h-100 position-relative text-white">
                <div class="card-body p-4 p-lg-5 position-relative d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="dashboard-wallet-mark">
                                <i class="bx bx-wallet fs-4"></i>
                            </span>
                            <div>
                                <h6 class="text-white mb-1">Wallet</h6>
                                <span class="dashboard-wallet-status">Available</span>
                            </div>
                        </div>
                        <button
                            id="wallet-balance-toggle"
                            class="dashboard-wallet-visibility"
                            type="button"
                            aria-label="Hide wallet balance"
                            title="Hide wallet balance"
                        >
                            <i class="bx bx-show fs-5"></i>
                        </button>
                    </div>

                    <div class="mb-4">
                        <span class="d-block text-white text-opacity-50 small mb-2">Available balance</span>
                        <div id="wallet-balance-value" class="dashboard-wallet-balance" data-balance="{{ $balance }}">{{ $balance }}</div>
                    </div>

                    <div class="dashboard-wallet-actions d-flex flex-wrap gap-2 mb-4">
                        <a href="{{ route('customer.load.wallet') }}" class="dashboard-wallet-primary-action btn px-4">
                            <i class="bx bx-plus-circle me-1"></i> Fund Wallet For VTU Trade

                        </a>
                        @if($airtimeToCashActive)
                            <a href="{{ route('airtime-to-cash') }}" class="dashboard-wallet-secondary-action btn px-4">
                                <i class="bx bx-transfer-alt me-1"></i> Airtime to Cash
                            </a>
                        @endif
                        @if($walletToBankProduct)
                            <a href="{{ route('wallet-to-bank', $walletToBankProduct->slug) }}" class="dashboard-wallet-secondary-action btn px-4">
                                <i class="bx bx-transfer me-1"></i> Wallet 2 Bank Transfer
                            </a>
                        @endif
                    </div>

                    <div class="dashboard-wallet-footer d-flex align-items-end justify-content-between gap-3 pt-3 mt-auto">
                        <div>
                            <small class="d-block text-white text-opacity-50 mb-1">Account</small>
                            <span class="fw-semibold">{{ '@' . $user->username }}</span>
                        </div>
                        <a href="{{ route('customer.transaction.report') }}" class="dashboard-wallet-history small">
                            History <i class="bx bx-right-arrow-alt align-middle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="row g-4 h-100">
                <div class="col-sm-6 col-xl-12">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <span class="text-muted small">Referral earnings</span>
                                <h4 class="mb-0 mt-1">{{ $referralBalance }}</h4>
                                <a href="{{ route('downlines') }}" class="small stretched-link">Earning history</a>
                            </div>
                            <span class="avatar-initial rounded bg-label-success p-3">
                                <i class="bx bx-group fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-12">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <span class="text-muted small">Account status</span>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <span class="badge bg-label-{{ $kycBadge }}">KYC: {{ $kycLabel }}</span>
                                    <span class="badge bg-label-primary">{{ $levelName }}</span>
                                </div>
                                <a href="{{ route('update.kyc.details') }}" class="small stretched-link">Manage KYC</a>
                            </div>
                            <span class="avatar-initial rounded bg-label-primary p-3">
                                <i class="bx bx-shield-quarter fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Quick Actions</h5>
    </div>
    <div class="row g-3 mb-4">
        @foreach($quickActions as $action)
            <div class="col-6 {{ $quickActionColumn }}">
                <a href="{{ $action['route'] }}" class="dashboard-action card border-0 text-decoration-none h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <span class="dashboard-action-icon bg-label-{{ $action['color'] }}">
                                <i class="bx {{ $action['icon'] }} fs-4"></i>
                            </span>
                            <i class="dashboard-action-arrow bx bx-right-arrow-alt text-muted fs-5"></i>
                        </div>
                        <h6 class="mb-1">{{ $action['label'] }}</h6>
                        <small class="text-muted">{{ $action['detail'] }}</small>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Services</h5>
                    <span class="badge bg-label-secondary">{{ $serviceCount }} available</span>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-2">
                        @if($airtimeToCashActive)
                            <div class="col-sm-6">
                                <a href="{{ route('airtime-to-cash') }}" class="dashboard-service d-flex align-items-center gap-3 rounded p-3 text-decoration-none text-body">
                                    <span class="dashboard-service-icon bg-label-primary"><i class="bx bx-transfer-alt"></i></span>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Airtime to Cash</h6>
                                    </div>
                                    <i class="bx bx-chevron-right text-muted"></i>
                                </a>
                            </div>
                        @endif
                        @if($walletToBankProduct)
                            <div class="col-sm-6">
                                <a href="{{ route('wallet-to-bank', $walletToBankProduct->slug) }}" class="dashboard-service d-flex align-items-center gap-3 rounded p-3 text-decoration-none text-body">
                                    <span class="dashboard-service-icon bg-label-success"><i class="bx bx-transfer"></i></span>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Wallet 2 Bank Transfer</h6>
                                    </div>
                                    <i class="bx bx-chevron-right text-muted"></i>
                                </a>
                            </div>
                        @endif
                        @foreach($services as $service)
                            <div class="col-sm-6">
                                <a href="{{ route('open.transaction.page', $service->slug) }}" class="dashboard-service d-flex align-items-center gap-3 rounded p-3 text-decoration-none text-body">
                                    <span class="dashboard-service-icon bg-label-primary">
                                        @if($service->icon)
                                            {!! $service->icon !!}
                                        @else
                                            <i class="bx bx-grid-alt"></i>
                                        @endif
                                    </span>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $service->display_name }}</h6>
                                    </div>
                                    <i class="bx bx-chevron-right text-muted"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">Referral Link</h5>
                        <span class="avatar-initial rounded bg-label-success p-2"><i class="bx bx-share-alt"></i></span>
                    </div>
                    <div class="input-group">
                        <input
                            id="referral-link"
                            class="dashboard-referral-link form-control bg-body-tertiary"
                            type="text"
                            value="{{ $referralLink }}"
                            aria-label="Referral link"
                            readonly
                        >
                        <button id="copy-referral-link" class="btn btn-primary" type="button" data-link="{{ $referralLink }}">
                            <i class="bx bx-copy"></i>
                            <span class="d-none d-sm-inline ms-1">Copy</span>
                        </button>
                    </div>
                    <a href="{{ route('alldownlines') }}" class="btn btn-label-primary w-100 mt-3">View Referrals</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="dashboard-action-icon bg-label-primary"><i class="bx bx-user fs-4"></i></span>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Profile & Security</h6>
                        <small class="text-muted">Personal details and account PIN</small>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-icon btn-label-primary" aria-label="Open profile">
                        <i class="bx bx-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('page-script')
    <script>
        (function () {
            const toggle = document.getElementById('wallet-balance-toggle');
            const balance = document.getElementById('wallet-balance-value');

            if (!toggle || !balance) {
                return;
            }

            const storageKey = '2cash-wallet-balance-hidden';
            const applyVisibility = function (hidden) {
                const label = hidden ? 'Show wallet balance' : 'Hide wallet balance';

                balance.textContent = hidden ? '••••••' : balance.dataset.balance;
                balance.dataset.hidden = hidden ? 'true' : 'false';
                toggle.setAttribute('aria-label', label);
                toggle.setAttribute('title', label);
                toggle.innerHTML = `<i class="bx ${hidden ? 'bx-hide' : 'bx-show'} fs-5"></i>`;
            };

            try {
                applyVisibility(localStorage.getItem(storageKey) === 'true');
            } catch (error) {
                applyVisibility(false);
            }

            toggle.addEventListener('click', function () {
                const hidden = balance.dataset.hidden !== 'true';
                applyVisibility(hidden);

                try {
                    localStorage.setItem(storageKey, String(hidden));
                } catch (error) {}
            });
        })();

        document.getElementById('copy-referral-link')?.addEventListener('click', async function () {
            const button = this;
            const originalHtml = button.innerHTML;

            try {
                await navigator.clipboard.writeText(button.dataset.link);
                button.innerHTML = '<i class="bx bx-check"></i><span class="d-none d-sm-inline ms-1">Copied</span>';
                setTimeout(() => button.innerHTML = originalHtml, 2000);
            } catch (error) {
                window.prompt('Copy your referral link:', button.dataset.link);
            }
        });
    </script>
@endsection
