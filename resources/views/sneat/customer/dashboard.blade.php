@extends('sneat.layouts.app')

@section('page-css')
    <style>
        .dashboard-shell {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 28%),
                radial-gradient(circle at bottom left, rgba(34, 197, 94, 0.12), transparent 24%),
                linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 28px;
            padding: 24px;
        }

        .hero-panel {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 52%, #0f766e 100%);
            color: #fff;
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            position: relative;
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -120px -120px auto;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            filter: blur(10px);
        }

        .hero-kicker {
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .hero-title {
            font-size: clamp(1.8rem, 2vw, 2.8rem);
            line-height: 1.05;
            font-weight: 800;
            margin: 0.35rem 0 0.75rem;
        }

        .hero-copy {
            max-width: 620px;
            color: rgba(255, 255, 255, 0.82);
        }

        .stat-card,
        .feature-card,
        .mini-card,
        .list-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .stat-card {
            background: #fff;
            padding: 22px;
            height: 100%;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat-value {
            font-size: clamp(1.5rem, 2vw, 2.2rem);
            font-weight: 800;
            color: #0f172a;
            margin: 0.2rem 0 0;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: #0f172a;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease;
            min-height: 100%;
        }

        .quick-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
            color: #0f172a;
            text-decoration: none;
        }

        .quick-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            color: #fff;
            flex: 0 0 auto;
        }

        .feature-card {
            background: #fff;
            padding: 22px;
            height: 100%;
        }

        .feature-title {
            margin-bottom: 0.4rem;
            font-weight: 700;
            color: #0f172a;
        }

        .feature-copy {
            color: #64748b;
            margin-bottom: 0;
        }

        .tile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .service-tile {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 170px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .service-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.1);
            color: #0f172a;
            text-decoration: none;
        }

        .service-badge {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: #fff;
            margin-bottom: 12px;
            font-size: 1.4rem;
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        }

        .section-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .soft-divider {
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            margin: 0;
        }
    </style>
@endsection

@section('content')
    @php
        $balance = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(walletBalance(auth()->user()), 2) : '0';
        $ref = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(referralBalance(auth()->user()), 2) : '0';
        $levelName = auth()->user()->customer?->level?->name ?? 'N/A';
        $kycStatus = auth()->user()->kyc_status ?? 'pending';
        $supportLink = getSettings()->support_link;
    @endphp

    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="dashboard-shell">
                    <section class="hero-panel mb-4">
                        <div class="row align-items-center g-4 position-relative">
                            <div class="col-lg-8">
                                <h1 class="hero-title">Dashboard</h1>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('customer.load.wallet') }}" class="btn btn-light btn-lg">
                                        <i class="bi bi-wallet2 me-2"></i>Fund Wallet
                                    </a>
                                    <a href="{{ route('airtime-to-cash') }}" class="btn btn-outline-light btn-lg">
                                        <i class="bi bi-lightning-charge me-2"></i>Airtime to Cash
                                    </a>
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-light btn-lg">
                                        <i class="bi bi-person-gear me-2"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="stat-card bg-white">
                                    <div class="stat-label">Wallet balance</div>
                                    <div class="stat-value">{{ $balance }}</div>
                                    <p class="text-muted mb-3">Referral earnings: <strong>{{ $ref }}</strong></p>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge text-bg-success rounded-pill px-3 py-2">{{ ucfirst($kycStatus) }} KYC</span>
                                        <span class="badge text-bg-dark rounded-pill px-3 py-2">{{ $levelName }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    @include('layouts.alerts')

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Wallet</div>
                                <div class="stat-value">{{ $balance }}</div>
                                <p class="text-muted mb-0">Available funds ready for transfers and purchases.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Referral earnings</div>
                                <div class="stat-value">{{ $ref }}</div>
                                <p class="text-muted mb-0">Track what your network is bringing in.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Customer level</div>
                                <div class="stat-value">{{ $levelName }}</div>
                                <p class="text-muted mb-0">Your current tier and access level.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-lg-7">
                            <div class="feature-card">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="section-label">Quick actions</div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <a class="quick-action" href="{{ route('airtime-to-cash') }}">
                                            <span class="quick-icon" style="background: linear-gradient(135deg, #ef4444, #f97316);">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </span>
                                            <span>
                                                <strong>Airtime to Cash</strong><br>
                                                <small class="text-muted">Open the conversion page</small>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col-sm-6">
                                        <a class="quick-action" href="{{ route('customer.load.wallet') }}">
                                            <span class="quick-icon" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
                                                <i class="bi bi-wallet2"></i>
                                            </span>
                                            <span>
                                                <strong>Fund Wallet</strong><br>
                                                <small class="text-muted">Add funds to your balance</small>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col-sm-6">
                                        <a class="quick-action" href="{{ route('customer.transaction.history') }}">
                                            <span class="quick-icon" style="background: linear-gradient(135deg, #14b8a6, #059669);">
                                                <i class="bi bi-receipt"></i>
                                            </span>
                                            <span>
                                                <strong>Transactions</strong><br>
                                                <small class="text-muted">See your recent history</small>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col-sm-6">
                                        <a class="quick-action" href="{{ route('update.kyc.details') }}">
                                            <span class="quick-icon" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);">
                                                <i class="bi bi-person-vcard"></i>
                                            </span>
                                            <span>
                                                <strong>KYC Details</strong><br>
                                                <small class="text-muted">Review or complete verification</small>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="feature-card h-100">
                                <div class="section-label">Status</div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Referral link</span>
                                    <a href="{{ url('/register') . '?referral=' . auth()->user()->username }}" class="text-decoration-none small text-break ms-3 text-end">
                                        Copy from signup URL
                                    </a>
                                </div>
                                <hr class="soft-divider mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>KYC status</span>
                                    <span class="badge text-bg-{{ $kycStatus === 'verified' ? 'success' : 'warning' }} rounded-pill px-3 py-2">
                                        {{ ucfirst($kycStatus) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Support</span>
                                    @if(!empty($supportLink))
                                        <a href="{{ $supportLink }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Open support</a>
                                    @else
                                        <span class="text-muted">Not configured</span>
                                    @endif
                                </div>
                                <div class="alert alert-light border mb-0">
                                    Finish KYC early so wallet funding, transfers, and service access stay smooth.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="section-label">Services</div>
                                <h3 class="mb-0">What would you like to do?</h3>
                            </div>
                        </div>
                        <div class="tile-grid">
                            @foreach (getCategories() as $category)
                                <a href="{{ route('open.transaction.page', $category->slug) }}" class="service-tile">
                                    <div>
                                        <div class="service-badge">
                                            @if($category->icon)
                                                {!! $category->icon !!}
                                            @else
                                                <i class="bi bi-grid-1x2-fill"></i>
                                            @endif
                                        </div>
                                        <h5 class="mb-1">{{ $category->display_name }}</h5>
                                        <p class="text-muted mb-0">Open this service from the refreshed dashboard.</p>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-3">
                                        <span class="small text-muted">Open</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if(!empty($customer))
                        <div class="feature-card">
                            <div class="row align-items-center g-3">
                                <div class="col-lg-8">
                                    <div class="section-label">Customer of the month</div>
                                    <h3 class="mb-2">{{ $customer->customer->user->username ?? $customer->customer->user->firstname }}</h3>
                                    <p class="feature-copy mb-0">
                                        {{ number_format($customer->count) }} completed transactions and counting.
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <img src="{{ asset('app-assets/images/icon/cup.png') }}" alt="Customer of the month" class="img-fluid" style="max-height: 180px;">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
