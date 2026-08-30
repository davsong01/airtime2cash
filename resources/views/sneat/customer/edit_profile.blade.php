@extends('sneat.layouts.app')
@section('title', 'Edit Profile')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .profile-dashboard-shell {
            position: relative;
        }

        .profile-summary-card,
        .profile-panel-card {
            border: 1px solid rgba(67, 89, 113, 0.12);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(67, 89, 113, 0.08);
        }

        .profile-summary-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 249, 255, 0.96));
        }

        .profile-summary-top {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.12), rgba(14, 165, 233, 0.08));
            border-bottom: 1px solid rgba(67, 89, 113, 0.08);
        }

        .profile-avatar {
            width: 68px;
            height: 68px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(145deg, #2563eb, #0ea5e9);
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.28);
        }

        .profile-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .profile-meta {
            padding: .9rem 1rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(67, 89, 113, 0.08);
        }

        .profile-meta span {
            display: block;
        }

        .profile-meta-label {
            color: var(--bs-secondary-color);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: .2rem;
        }

        .profile-meta-value {
            color: var(--bs-heading-color);
            font-weight: 700;
            word-break: break-word;
        }

        .profile-summary-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .75rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, .1);
            color: #1d4ed8;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .profile-section-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .profile-section-title .icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, #2563eb, #0ea5e9);
        }

        .profile-section-title h5 {
            margin-bottom: .15rem;
        }

        .profile-note {
            border-radius: 18px;
            border: 1px solid rgba(245, 158, 11, .18);
            background: rgba(255, 251, 235, .85);
            color: #92400e;
        }

        .locked-account-card {
            border-radius: 24px;
            border: 1px solid rgba(67, 89, 113, 0.12);
            background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,255,.98));
            box-shadow: 0 18px 45px rgba(67, 89, 113, 0.08);
        }

        .locked-account-header {
            padding: 1.25rem 1.4rem;
            border-bottom: 1px solid rgba(67, 89, 113, .08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .locked-account-header strong {
            display: block;
            margin-bottom: .2rem;
        }

        .locked-account-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .locked-account-badge.is-locked {
            background: rgba(34, 197, 94, .12);
            color: #166534;
        }

        .locked-account-badge.is-pending {
            background: rgba(245, 158, 11, .14);
            color: #92400e;
        }

        .locked-account-body {
            padding: 1.4rem;
        }

        .locked-account-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .9rem;
        }

        .locked-account-field {
            padding: .9rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(67, 89, 113, .08);
            background: rgba(255,255,255,.84);
        }

        .locked-account-field span {
            display: block;
        }

        .locked-account-field-label {
            color: var(--bs-secondary-color);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: .25rem;
        }

        .locked-account-field-value {
            color: var(--bs-heading-color);
            font-weight: 700;
            word-break: break-word;
        }

        .bank-setup-actions {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .referral-link-box {
            border: 1px dashed rgba(67, 89, 113, .18);
            background: linear-gradient(180deg, rgba(248, 250, 255, .95), rgba(241, 245, 255, .95));
            border-radius: 18px;
        }

        .copy-link-btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .wallet-account-anchor {
            scroll-margin-top: 100px;
        }

        .select2-container .select2-selection--single {
            min-height: 42px;
            border-radius: 14px;
            border-color: rgba(67, 89, 113, .22);
        }

        .select2-container--open .select2-selection--single {
            border-color: rgba(37, 99, 235, .45);
            box-shadow: 0 0 0 .15rem rgba(37, 99, 235, .08);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 1rem;
            padding-right: 2.25rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: .75rem;
        }

        .select2-dropdown {
            border-color: rgba(67, 89, 113, .16);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(67, 89, 113, .16);
        }

        @media (max-width: 991.98px) {
            .profile-meta-grid,
            .locked-account-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Profile',
        'title' => 'Edit Profile',
        'subtitle' => 'Keep your identity, security, and payout details in sync from one modern control panel.',
    ])

    @include('sneat.layouts.alerts')

    @php
        $profileName = trim(collect([
            auth()->user()->firstname,
            auth()->user()->middlename,
            auth()->user()->lastname,
        ])->filter()->implode(' ')) ?: auth()->user()->name;
        $kycVerified = getFinalKycStatus(auth()->user()->customer->id) == 'verified';
        $emailVerified = !empty(auth()->user()->email_verified_at);
        $walletBankAccount = $walletBankAccount ?? auth()->user()?->customer?->wallet_bank_account ?? null;
        $walletBankAccountReady = (bool) (($walletBankAccountMatchesProfile ?? false) && ! empty($walletBankAccount));
    @endphp

    <div class="profile-dashboard-shell">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card profile-summary-card h-100">
                    <div class="profile-summary-top">
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->firstname ?: auth()->user()->username, 0, 2)) }}</div>
                            <div>
                                <div class="text-muted text-uppercase small fw-semibold mb-1">Account profile</div>
                                <h4 class="mb-1">{{ $profileName }}</h4>
                                <div class="text-muted">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="profile-summary-chip mb-3">
                            <i class="bx bx-shield-quarter"></i>
                            Trusted profile
                        </div>

                        <div class="profile-meta-grid">
                            <div class="profile-meta">
                                <span class="profile-meta-label">Username</span>
                                <span class="profile-meta-value">{{ '@' . auth()->user()->username }}</span>
                            </div>
                            <div class="profile-meta">
                                <span class="profile-meta-label">Phone</span>
                                <span class="profile-meta-value">{{ auth()->user()->phone ?: 'Not set' }}</span>
                            </div>
                            <div class="profile-meta">
                                <span class="profile-meta-label">KYC</span>
                                <span class="profile-meta-value {{ $kycVerified ? 'text-success' : 'text-muted' }}">{{ $kycVerified ? 'Verified' : 'Pending' }}</span>
                            </div>
                            <div class="profile-meta">
                                <span class="profile-meta-label">Email</span>
                                <span class="profile-meta-value {{ $emailVerified ? 'text-success' : 'text-muted' }}">{{ $emailVerified ? 'Verified' : 'Unverified' }}</span>
                            </div>
                            <div class="profile-meta">
                                <span class="profile-meta-label">Customer level</span>
                                <span class="profile-meta-value">Level {{ auth()->user()->customer?->level?->name }}</span>
                            </div>
                            <div class="profile-meta">
                                <span class="profile-meta-label">Referral</span>
                                <span class="profile-meta-value text-truncate d-block">{{ auth()->user()->username }}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Referral link</div>
                            <div class="referral-link-box p-3">
                                <div class="d-flex align-items-start gap-3 flex-wrap">
                                    <div class="flex-grow-1 text-break fw-medium" id="referral-link-text">{{ url('/register') . '?referral=' . auth()->user()->username }}</div>
                                    <button type="button" class="btn btn-outline-primary btn-sm copy-link-btn" id="copy-referral-link">
                                        <i class="bx bx-copy me-1"></i> Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card profile-panel-card">
                    <div class="card-header d-flex align-items-center gap-3">
                        <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-user fs-4"></i></span>
                        <div>
                            <h5 class="mb-1">Profile details</h5>
                            <small class="text-muted">Update your personal information and security settings.</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profile.update') }}" method="POST" autocomplete="off" class="customer-modern-form">
                            @csrf
                            @method('PATCH')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstname" class="form-label">First name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" value="{{ auth()->user()->firstname }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="middlename" class="form-label">Middle name</label>
                                    <input type="text" class="form-control" id="middlename" name="middlename" value="{{ auth()->user()->middlename }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="lastname" class="form-label">Last name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" value="{{ auth()->user()->lastname }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone number</label>
                                    <input type="text" class="form-control" id="phone" value="{{ auth()->user()->phone }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email address</label>
                                    <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Customer level</label>
                                    <input type="text" class="form-control" value="Level {{ auth()->user()->customer?->level?->name }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_transaction_pin" class="form-label">New transaction PIN</label>
                                    <input type="text" class="form-control" name="new_transaction_pin">
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">New password</label>
                                    <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="customer-form-actions mt-4 mx-n4 mb-n4">
                                <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-save me-1"></i> Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card locked-account-card mt-4 wallet-account-anchor" id="wallet-to-bank-account">
                    <div class="locked-account-header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="purchase-heading-icon bg-label-warning"><i class="bx bx-lock-alt fs-4"></i></span>
                            <div>
                                <strong>Wallet to Bank Account</strong>
                                <small class="text-muted d-block">This payout account is locked after verification and can only be changed by admin deletion.</small>
                            </div>
                        </div>
                        @if($walletBankAccountReady)
                            <span class="locked-account-badge is-locked">Locked</span>
                        @else
                            <span class="locked-account-badge is-pending">Setup required</span>
                        @endif
                    </div>
                    <div class="locked-account-body">
                        <div class="profile-note alert mb-4">
                            Any name you use to register is the name we will pay to, and it must match the name on your profile.
                        </div>

                        @if($walletBankAccountReady)
                            <div class="locked-account-grid">
                                <div class="locked-account-field">
                                    <span class="locked-account-field-label">Bank</span>
                                    <span class="locked-account-field-value">{{ data_get($walletBankAccount, 'bank_name', 'Not set') }}</span>
                                </div>
                                <div class="locked-account-field">
                                    <span class="locked-account-field-label">Account name</span>
                                    <span class="locked-account-field-value">{{ data_get($walletBankAccount, 'account_name', 'Not set') }}</span>
                                </div>
                                <div class="locked-account-field">
                                    <span class="locked-account-field-label">Account number</span>
                                    <span class="locked-account-field-value">{{ data_get($walletBankAccount, 'account_number', 'Not set') }}</span>
                                </div>
                            </div>
                            <div class="mt-3 text-muted">Profile name: <strong>{{ data_get($walletBankAccount, 'profile_name', $profileName) }}</strong></div>
                        @elseif(!empty($walletBankAccount) && ! $walletBankAccountMatchesProfile)
                            <div class="alert alert-danger border-0">
                                Your saved bank account no longer matches your current profile name. Please contact admin to delete it before you add a new one.
                                @if($adminWhatsappLink)
                                    <div class="bank-setup-actions mt-3">
                                        <a class="btn btn-success" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">Click to contact admin on WhatsApp</a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <form action="{{ route('profile.wallet-bank-account.store') }}" method="POST" autocomplete="off" class="customer-modern-form">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="wallet_bank_bank" class="form-label">Select bank</label>
                                        <select class="form-select modern-select2" name="bank" id="wallet_bank_bank" data-placeholder="Search banks" required>
                                            <option value="">Select a bank</option>
                                            @foreach($banks as $bank)
                                                <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="wallet_bank_account_number" class="form-label">Account number</label>
                                        <input class="form-control" id="wallet_bank_account_number" name="account_number" type="text" maxlength="20" required>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3 mb-0">
                                    Account details, <strong>click</strong> <a href="{{ url('/profile#wallet-to-bank-account') }}">HERE</a> to fill it now.
                                    We will verify the account number and only save it if it matches your profile name.
                                </div>
                                <div class="bank-setup-actions mt-4">
                                    <button class="btn btn-success customer-form-submit" type="submit"><i class="bx bx-check-circle me-1"></i> Verify and save bank details</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.modern-select2').select2({
                width: '100%',
                placeholder: function () { return $(this).data('placeholder'); },
                allowClear: true,
                minimumResultsForSearch: 0,
                dropdownAutoWidth: true,
                dropdownParent: $('#wallet-to-bank-account')
            });

            const copyButton = document.getElementById('copy-referral-link');
            const referralLinkText = document.getElementById('referral-link-text');

            if (copyButton && referralLinkText) {
                copyButton.addEventListener('click', async function () {
                    const link = referralLinkText.textContent.trim();
                    try {
                        await navigator.clipboard.writeText(link);
                        const original = copyButton.innerHTML;
                        copyButton.innerHTML = '<i class="bx bx-check me-1"></i> Copied';
                        setTimeout(() => {
                            copyButton.innerHTML = original;
                        }, 1800);
                    } catch (error) {
                        window.prompt('Copy referral link', referralLinkText.textContent.trim());
                    }
                });
            }
        });
    </script>
@endsection
