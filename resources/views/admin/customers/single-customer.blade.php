@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-customer-profile.css') }}">
@endsection

@php
    $customer = $user->customer;
    $fullName = trim(collect([$user->firstname, $user->middlename, $user->lastname])->filter()->implode(' ')) ?: $user->username;
    $initials = strtoupper(substr((string) $user->firstname, 0, 1) . substr((string) $user->lastname, 0, 1));
    $initials = $initials ?: strtoupper(substr((string) $user->username, 0, 2));
    $finalKycStatus = $customer->kyc_status ?: 'unverified';
    $tabs = [
        'account' => ['label' => 'Account', 'icon' => 'bx bx-user-circle', 'view' => 'profile-account'],
        'transactions' => ['label' => 'Transactions', 'icon' => 'bx bx-receipt', 'view' => 'profile-transactions'],
        'downlines' => ['label' => 'Referrals', 'icon' => 'bx bx-git-branch', 'view' => 'profile-referrals'],
        'kyc' => ['label' => 'KYC', 'icon' => 'bx bx-id-card', 'view' => 'profile-kyc'],
        'reserved-account' => ['label' => 'Reserved accounts', 'icon' => 'bx bx-building-house', 'view' => 'profile-reserved-accounts'],
        'actions' => ['label' => 'Security & risk', 'icon' => 'bx bx-shield-quarter', 'view' => 'profile-actions'],
    ];
@endphp

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row mb-1">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <ol class="breadcrumb p-0 mb-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customers') }}">Customers</a></li>
                            <li class="breadcrumb-item active">Customer profile</li>
                        </ol>
                    </div>
                </div>
            </div>

            @include('layouts.alerts')

            <section class="customer-admin-hero mb-2">
                <div class="row align-items-center position-relative" style="z-index:1;">
                    <div class="col-lg-7">
                        <div class="d-flex align-items-center">
                            <div class="customer-admin-avatar mr-1">{{ $initials }}</div>
                            <div>
                                <div class="customer-admin-kicker">Customer #{{ $user->id }}</div>
                                <h2 class="mb-25">{{ $fullName }}</h2>
                                <div class="d-flex flex-wrap align-items-center customer-admin-meta">
                                    <span><i class="bx bx-at mr-25"></i>{{ $user->username }}</span>
                                    <span><i class="bx bx-envelope mr-25"></i>{{ $user->email }}</span>
                                    <span><i class="bx bx-phone mr-25"></i>{{ $user->phone ?: 'No phone number' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 mt-1 mt-lg-0">
                        <div class="customer-admin-statuses">
                            <div>
                                <small>Account status</small>
                                <span class="badge {{ $user->status === 'active' ? 'badge-light-success' : 'badge-light-danger' }}">{{ ucfirst($user->status ?: 'unknown') }}</span>
                            </div>
                            <div>
                                <small>KYC status</small>
                                <span class="badge {{ $finalKycStatus === 'verified' ? 'badge-light-success' : ($finalKycStatus === 'awaiting-approval' ? 'badge-light-warning' : 'badge-light-secondary') }}">{{ ucfirst(str_replace('-', ' ', $finalKycStatus)) }}</span>
                            </div>
                            <div>
                                <small>Customer level</small>
                                <strong>{{ $customer->level->name ?? 'Level 1' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="row match-height customer-balance-grid">
                @foreach ($balances as $key => $balance)
                    @php
                        $balanceIcons = [
                            'Wallet Balance' => 'bx bx-wallet',
                            'Referral Earning' => 'bx bx-gift',
                            'Transaction Total' => 'bx bx-transfer-alt',
                            'Funds Total' => 'bx bx-credit-card',
                        ];
                    @endphp
                    <div class="col-xl-3 col-sm-6">
                        <div class="card customer-balance-card">
                            <div class="card-body d-flex align-items-center">
                                <span class="customer-balance-icon mr-1"><i class="{{ $balanceIcons[$key] ?? 'bx bx-bar-chart' }}"></i></span>
                                <div><small>{{ $key }}</small><strong>{!! $balance !!}</strong></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card customer-workspace">
                <div class="card-header border-bottom p-0">
                    <ul class="nav nav-tabs customer-workspace-tabs border-0" role="tablist">
                        @foreach($tabs as $tabId => $tab)
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === $tabId ? 'active' : '' }}" href="{{ route('customers.edit', ['id' => $user->id, 'tab' => $tabId]) }}" aria-current="{{ $activeTab === $tabId ? 'page' : 'false' }}">
                                    <i class="{{ $tab['icon'] }}"></i><span>{{ $tab['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="customer-workspace-content">
                        @include('admin.customers.partials.' . $tabs[$activeTab]['view'])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.customers.partials.profile-modals')
@endsection

@section('page-script')
    <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            $('.js-example-basic-single').select2({ width: '100%' });

        });

        function zoomImg(image) {
            const modal = document.getElementById('kyc-document-modal');
            const modalImage = document.getElementById('kyc-document-preview');
            modalImage.src = image.src;
            $(modal).modal('show');
        }

        function toggleBlacklist(input) {
            if (!window.confirm('Are you sure you want to change this blacklist status?')) {
                input.checked = !input.checked;
                return;
            }

            $.get('{{ route('black.list.status') }}', {
                id: input.dataset.id,
                status: input.dataset.status
            }).done(function (response) {
                if (response.code === 1) {
                    input.dataset.status = response.status;
                } else {
                    input.checked = !input.checked;
                    window.alert(response.message || 'The status could not be changed.');
                }
            }).fail(function () {
                input.checked = !input.checked;
                window.alert('The status could not be changed.');
            });
        }
    </script>
@endsection
