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
        'airtime2cash-transactions' => ['label' => 'Airtime to Cash', 'icon' => 'bx bx-phone-call', 'view' => 'profile-airtime-transactions'],
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
                                <strong>{{ $customer?->level?->name ?? 'Unassigned' }}</strong>
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
            $('[data-toggle="tooltip"]').tooltip();
            bindBvnResultModal();
            bindBvnVerification();
            bindKycReviewActions();
            bindWalletBankVerification();
        });

        function zoomImg(image) {
            const modal = document.getElementById('kyc-document-modal');
            const modalImage = document.getElementById('kyc-document-preview');
            modalImage.src = image.src;
            $(modal).modal('show');
        }

        function bindWalletBankVerification() {
            const $button = $('#verify-wallet-account-draft-btn');
            const $result = $('#wallet-bank-verify-result');

            if (!$button.length) {
                return;
            }

            const normalize = (value) => String(value ?? '').trim().toLowerCase();

            const setWalletBankDraftField = (selector, value) => {
                if (value === undefined || value === null || value === '') {
                    return;
                }

                const $field = $(selector);
                if ($field.length) {
                    $field.val(value).trigger('change');
                }
            };

            const extractVerifiedPayload = (payload) => {
                const root = payload?.data ?? payload ?? {};
                const response = root?.raw_response ?? root?.data ?? root?.responseBody ?? root;
                const refined = root?.refined_data ?? response?.refined_data ?? {};

                return {
                    bankName: refined['Bank Name']
                        ?? response?.bank_name
                        ?? response?.bankName
                        ?? response?.data?.bank_name
                        ?? '',
                    accountName: refined['Account Name']
                        ?? response?.account_name
                        ?? response?.accountName
                        ?? response?.data?.account_name
                        ?? '',
                    accountNumber: refined['Account Number']
                        ?? response?.account_number
                        ?? response?.data?.account_number
                        ?? '',
                    rawResponse: response,
                };
            };

            const applyVerifiedDraft = (payload) => {
                const extracted = extractVerifiedPayload(payload);
                const currentBankValue = String($('#wallet_bank_bank').val() || '').trim();
                const currentBankText = String($('#wallet_bank_bank option:selected').text() || '').trim();
                const targetBank = extracted.bankName || currentBankText;

                if (targetBank) {
                    const $matchedBank = $('#wallet_bank_bank option').filter(function () {
                        const optionText = String($(this).text() || '').trim();
                        return normalize(optionText).includes(normalize(targetBank))
                            || normalize(String($(this).val() || '')).includes(normalize(targetBank));
                    }).first();

                    if ($matchedBank.length) {
                        $('#wallet_bank_bank').val($matchedBank.val()).trigger('change');
                    } else if (currentBankValue) {
                        $('#wallet_bank_bank').val(currentBankValue).trigger('change');
                    }
                }

                setWalletBankDraftField('#wallet_bank_account_number', extracted.accountNumber);
                setWalletBankDraftField('#wallet_bank_account_name', extracted.accountName);
                setWalletBankDraftField('#wallet_bank_verified_name', extracted.accountName);

                if (!$('#wallet_bank_profile_name').val()) {
                    setWalletBankDraftField('#wallet_bank_profile_name', $button.data('customer-name') || '');
                }

                const now = new Date();
                const isoLocal = now.toISOString().slice(0, 16);
                setWalletBankDraftField('#wallet_bank_verified_at', isoLocal);

                return extracted.rawResponse;
            };

            const renderResult = (payload, isError = false) => {
                const status = String(payload?.status ?? (isError ? false : true)).toLowerCase();
                const title = payload?.message || (isError ? 'Unable to verify bank details right now.' : 'Verification complete.');
                const response = payload?.raw_response ?? payload?.data ?? payload ?? {};
                const cardClass = isError || status === 'false' || status === 'failed'
                    ? 'alert-danger'
                    : 'alert-success';

                return `
                    <div class="alert ${cardClass} mb-0">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            <div>
                                <strong class="d-block mb-25">Bank verification result</strong>
                                <div class="mb-50">${escapeHtml(title)}</div>
                            </div>
                            <span class="badge badge-light-${cardClass === 'alert-success' ? 'success' : 'danger'}">${cardClass === 'alert-success' ? 'Verified' : 'Failed'}</span>
                        </div>
                        <pre class="mb-0 mt-1" style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(JSON.stringify(response, null, 2))}</pre>
                    </div>
                `;
            };

            $button.on('click', function () {
                const verifyUrl = String($button.data('verify-url') || '');
                const bankCode = String($('#wallet_bank_bank').val() || '').trim();
                const accountNumber = String($('#wallet_bank_account_number').val() || '').trim();
                const accountName = String($('#wallet_bank_account_name').val() || '').trim();
                const profileName = String($('#wallet_bank_profile_name').val() || '').trim();
                const verifiedName = String($('#wallet_bank_verified_name').val() || '').trim();
                const verifiedAt = String($('#wallet_bank_verified_at').val() || '').trim();
                const customerName = String($button.data('customer-name') || '').trim();

                if (!bankCode || !accountNumber) {
                    $result.html('<div class="alert alert-warning mb-0">Please choose a bank and enter an account number before verifying.</div>').show();
                    return;
                }

                $.ajax({
                    url: verifyUrl,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        bank: bankCode,
                        bank_code: bankCode,
                        account_number: accountNumber,
                        account_name: accountName,
                        profile_name: profileName,
                        verified_name: verifiedName,
                        verified_at: verifiedAt,
                        customer_name: customerName,
                    },
                    beforeSend: function () {
                        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-50" role="status" aria-hidden="true"></span> Verifying...');
                        $result.html('<div class="alert alert-info mb-0">Verifying the current account draft...</div>').show();
                    },
                    success: function (response) {
                        applyVerifiedDraft(response);
                        $result.html(renderResult(response, false)).show();
                    },
                    error: function (xhr) {
                        const payload = xhr.responseJSON || { status: false, message: 'Unable to verify bank details right now.' };
                        $result.html(renderResult(payload, true)).show();
                    },
                    complete: function () {
                        $button.prop('disabled', false).html('<i class="bx bx-search-alt mr-25"></i> Verify account only');
                    }
                });
            });
        }

        function bindBvnResultModal() {
            const $modal = $('#bvnVerificationResultModal');
            const $loading = $('#bvn-verification-loading');
            const $result = $('#bvn-verification-result');
            const $title = $('#bvnVerificationResultTitle');
            const $showResultBtn = $('.js-bvn-show-result-btn');
            const state = window.adminBvnVerificationState || {};

            if (!$modal.length) {
                return;
            }

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const getResponsePayload = () => state.response || {};

            const syncModal = () => {
                $('#bvn-result-profile-name').text(state.profileName || 'N/A');
                $('#bvn-result-verified-name').text(state.verifiedName || 'N/A');
                $('#bvn-result-match-status')
                    .text(state.nameMatch ? 'Names match' : 'Names do not match')
                    .toggleClass('text-success', !!state.nameMatch)
                    .toggleClass('text-danger', !state.nameMatch);
                $('#bvn-result-json').text(JSON.stringify(getResponsePayload(), null, 2));
            };

            const showLoading = () => {
                $title.text('Verifying BVN details...');
                $loading.removeClass('d-none');
                $result.addClass('d-none');
            };

            const showResult = () => {
                syncModal();
                $title.text('BVN verification result');
                $loading.addClass('d-none');
                $result.removeClass('d-none');
            };

            $modal.on('show.bs.modal', function () {
                if (state && Object.keys(getResponsePayload()).length) {
                    showResult();
                } else {
                    showLoading();
                }
            });

            $showResultBtn.on('click', function () {
                showResult();
            });

            window.adminBvnVerificationUi = {
                setLoading: showLoading,
                setResult: function (payload) {
                    state.profileName = payload.profile_name || state.profileName || '';
                    state.verifiedName = payload.verified_name || state.verifiedName || '';
                    state.nameMatch = !!payload.name_match;
                    state.response = payload.provider_response || payload.response || payload.stored_data?.response || payload;
                    state.status = payload.verification_status || payload.status || state.status || '';
                    state.verifiedAt = payload.stored_data?.verified_at || state.verifiedAt || '';
                    $showResultBtn.removeClass('d-none');
                    showResult();
                },
                open: function () {
                    $modal.modal('show');
                }
            };
        }

        function bindBvnVerification() {
            const $button = $('.js-bvn-verify-btn');

            if (!$button.length) {
                return;
            }

            $button.on('click', function () {
                const $currentButton = $(this);
                const verifyUrl = String($currentButton.data('verify-url') || '');
                const bvnField = String($currentButton.data('bvn-field') || '#BVN');
                const bvn = String($(bvnField).val() || '').trim();
                const bvnCharge = parseFloat(String($currentButton.data('bvn-charge') || '0')) || 0;
                const confirmMessage = bvnCharge > 0
                    ? `This BVN verification may charge the customer's wallet with ${bvnCharge.toFixed(2)}. Continue?`
                    : 'This BVN verification may charge the customer\'s wallet. Continue?';

                if (!window.confirm(confirmMessage)) {
                    return;
                }

                if (!verifyUrl) {
                    window.adminBvnVerificationUi?.open();
                    return;
                }

                if (!bvn) {
                    window.adminBvnVerificationUi?.open();
                    return;
                }

                window.adminBvnVerificationUi?.open();
                window.adminBvnVerificationUi?.setLoading();

                $.ajax({
                    url: verifyUrl,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        bvn: bvn,
                    },
                    beforeSend: function () {
                        $currentButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-50" role="status" aria-hidden="true"></span> Verifying...');
                    },
                    success: function (response) {
                        window.adminBvnVerificationUi?.setResult(response || {});
                    },
                    error: function (xhr) {
                        const payload = xhr.responseJSON || { status: false, message: 'Unable to verify BVN right now.' };
                        window.adminBvnVerificationUi?.setResult(payload);
                    },
                    complete: function () {
                        $currentButton.prop('disabled', false).html('Verify BVN');
                    }
                });
            });
        }

        function bindKycReviewActions() {
            const $modal = $('#kyc-field-review-modal');
            const $feedback = $('#kyc-field-review-feedback');
            const $fieldLabel = $('#kyc-review-field-label');
            const $field = $('#kyc-review-field');
            const $action = $('#kyc-review-action');
            const $value = $('#kyc-review-value');
            const $reason = $('#kyc-review-reason');
            const $approveBtn = $('#kyc-review-confirm-approve');
            const $rejectBtn = $('#kyc-review-confirm-reject');

            if (!$modal.length) {
                return;
            }

            let reviewUrl = '';

            const setFeedback = (type, message) => {
                const alertClass = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
                $feedback
                    .removeClass('d-none alert-success alert-warning alert-danger')
                    .addClass(alertClass)
                    .text(message || '');
            };

            const hideFeedback = () => {
                $feedback.addClass('d-none').removeClass('alert-success alert-warning alert-danger').text('');
            };

            const openModal = (data) => {
                reviewUrl = String(data.reviewUrl || '');
                $fieldLabel.val(data.fieldLabel || data.field || '');
                $field.val(data.field || '');
                $action.val(data.action || 'approve');
                $value.val(data.value || '');
                $reason.val('');
                hideFeedback();

                const isReject = String(data.action || '') === 'reject';
                $approveBtn.toggle(!isReject);
                $rejectBtn.toggle(isReject);
                $reason.closest('.form-group').toggle(isReject);

                $modal.modal('show');
            };

            const submitReview = (action) => {
                const field = String($field.val() || '');
                const value = String($value.val() || '');
                const reason = String($reason.val() || '').trim();

                if (!reviewUrl || !field) {
                    setFeedback('warning', 'This review action is missing the route or field name.');
                    return;
                }

                $.ajax({
                    url: reviewUrl,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        field: field,
                        action: action,
                        value: value,
                        reason: reason,
                    },
                    beforeSend: function () {
                        $approveBtn.prop('disabled', true);
                        $rejectBtn.prop('disabled', true);
                        $reason.prop('disabled', true);
                        setFeedback('warning', 'Processing field review...');
                    },
                    success: function (response) {
                        setFeedback('success', response.message || 'Field review completed.');
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'Unable to update this KYC field right now.';
                        setFeedback('danger', message);
                    },
                    complete: function () {
                        $approveBtn.prop('disabled', false);
                        $rejectBtn.prop('disabled', false);
                        $reason.prop('disabled', false);
                    }
                });
            };

            $(document).on('click', '.js-kyc-review-btn', function () {
                openModal({
                    reviewUrl: $(this).data('review-url'),
                    field: $(this).data('field'),
                    fieldLabel: $(this).data('field-label'),
                    action: $(this).data('action'),
                    value: $(this).data('value'),
                });
            });

            $approveBtn.on('click', function () {
                submitReview('approve');
            });

            $rejectBtn.on('click', function () {
                submitReview('reject');
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
@endsection
