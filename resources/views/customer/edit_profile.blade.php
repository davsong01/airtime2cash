@extends('layouts.app')
@section('title', 'Edit Profile')

@section('page-css')
    <style>
        .reset-pin {
            font-size: 10px;
            float: right;
        }

        .key-field {
            padding: 10px;
            margin-bottom: 20px;
            width: 100%;
            border: #5A8DEE 2px solid;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .key-field i {
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
    </style>
@endsection
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <!-- Basic Inputs start -->
                <section id="basic-input">
                    <div class="row">

                        <div class="col-md-6 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Refer and Earn</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p>
                                            Share your referral links with friends to earn handsome rewards
                                            <div class="text-primary">{{ url('/register'). '?referral='.auth()->user()->username }}
                                            </div>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">KYC Status</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        @if (getFinalKycStatus(auth()->user()->customer->id) == 'verified')
                                            <button class="btn btn-success">Verified</button>
                                        @else
                                            <button class="btn btn-danger">Unverified</button>
                                        @endif
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card">
                                <div class="content-body">
                                    <!-- Nav Filled Starts -->
                                    <section id="nav-filled">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <div class="col-md-12">
                                                        <div class="card-header" style="padding:1.4rem 0.7rem">
                                                            <h4 class="card-title">Edit Profile</h4>
                                                            @include('layouts.alerts')
                                                        </div>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <form action="{{ route('profile.update') }}" method="POST"
                                                                autocomplete="off">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="firstname">First Name</label>
                                                                            <input autocomplete="false" type="firstname"
                                                                                class="form-control" id="firstname"
                                                                                name="firstname"
                                                                                value="{{ auth()->user()->firstname }}"
                                                                                required>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="middlename">Middle Name</label>
                                                                            <input autocomplete="false" type="middlename"
                                                                                class="form-control" id="middlename"
                                                                                name="middlename"
                                                                                value="{{ auth()->user()->middlename }}">
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="lastname">Last Name</label>
                                                                            <input autocomplete="false" type="lastname"
                                                                                class="form-control" id="lastname"
                                                                                name="lastname"
                                                                                value="{{ auth()->user()->lastname }}"
                                                                                required>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="phone">Phone Number</label>
                                                                            <input autocomplete="false" type="phone"
                                                                                class="form-control" id="phone"
                                                                                name="phone"
                                                                                value="{{ auth()->user()->phone }}">
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="email">Email Address</label>
                                                                            <input autocomplete="false" type="phone"
                                                                                class="form-control" disabled
                                                                                value="{{ auth()->user()->email }}">
                                                                        </fieldset>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="new_transaction_pin">New Transaction PIN</label>
                                                                            <input type="text" class="form-control" name="new_transaction_pin">
                                                                        </fieldset>
                                                                    </div>

                                                                    @if (auth()->user()->type == 'customer')
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="email">Customer Level</label>
                                                                                <input autocomplete="false" type="phone"
                                                                                    class="form-control" disabled
                                                                                    value="Level {{ auth()->user()->customer?->level?->name }}">
                                                                            </fieldset>
                                                                        </div>
                                                                    @endif
                                                                    <div class="col-md-6">
                                                                        <fieldset class="form-group">
                                                                            <label for="new_password">New Password</label>
                                                                            <input type="text" class="form-control" name="new_password">
                                                                        </fieldset>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <button class="btn btn-primary"
                                                                            type="submit">Update Profile</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 mt-3">
                                                    <div class="card">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h4 class="card-title mb-0">Wallet to Bank Account</h4>
                                                                <small class="text-muted">This account is locked after verification and can only be changed by admin deletion.</small>
                                                            </div>
                                                            @if(!empty($walletBankAccount) && $walletBankAccountMatchesProfile)
                                                                <span class="badge badge-success">Locked</span>
                                                            @else
                                                                <span class="badge badge-warning">Setup required</span>
                                                            @endif
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="alert alert-warning">
                                                                Any name you use to register is the name we will pay to, and it must match the name on your profile.
                                                            </div>

                                                            @if(!empty($walletBankAccount) && $walletBankAccountMatchesProfile)
                                                                <div class="row">
                                                                    <div class="col-md-4">
                                                                        <fieldset class="form-group">
                                                                            <label>Bank</label>
                                                                            <input type="text" class="form-control" value="{{ data_get($walletBankAccount, 'bank_name', 'Not set') }}" disabled>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <fieldset class="form-group">
                                                                            <label>Account name</label>
                                                                            <input type="text" class="form-control" value="{{ data_get($walletBankAccount, 'account_name', 'Not set') }}" disabled>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <fieldset class="form-group">
                                                                            <label>Account number</label>
                                                                            <input type="text" class="form-control" value="{{ data_get($walletBankAccount, 'account_number', 'Not set') }}" disabled>
                                                                        </fieldset>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted d-block">Profile name: <strong>{{ data_get($walletBankAccount, 'profile_name', auth()->user()->name) }}</strong></small>
                                                            @elseif(!empty($walletBankAccount) && ! $walletBankAccountMatchesProfile)
                                                                <div class="alert alert-danger">
                                                                    Your saved bank account no longer matches your current profile name. Please contact admin to delete it before you add a new one.
                                                                    @if($adminWhatsappLink)
                                                                        <div class="mt-2">
                                                                            <a class="btn btn-success" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">Click to contact admin on WhatsApp</a>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <form action="{{ route('profile.wallet-bank-account.store') }}" method="POST" autocomplete="off">
                                                                    @csrf
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="wallet_bank_bank">Select bank</label>
                                                                                <select class="form-control" name="bank" id="wallet_bank_bank" required>
                                                                                    <option value="">Select</option>
                                                                                    @foreach($banks as $bank)
                                                                                        <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </fieldset>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <fieldset class="form-group">
                                                                                <label for="wallet_bank_account_number">Account number</label>
                                                                                <input class="form-control" id="wallet_bank_account_number" name="account_number" type="text" maxlength="20" required>
                                                                            </fieldset>
                                                                        </div>
                                                                    </div>
                                                                    <div class="alert alert-info">
                                                                        We will verify the account number and only save it if it matches your profile name.
                                                                    </div>
                                                                    <button class="btn btn-success" type="submit">Verify and save bank details</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                    <!-- Nav Filled Ends -->
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    <script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>

    <script>
        $('.api-key-btn').click(function() {
            $.ajax({
                url: '{{ route('profile.keys') }}',
                beforeSend: () => {},
                success: res => {
                    let data = res.data;

                    $('#api_key span').html(data.api_key);
                    $('#public span').html(data.public);
                    $('#secret span').html(data.secret);
                },
            });
        });

        $('.copy').click(function () {
            (async () => {
                try {
                    var copyText = $(this).prev('span');
                    let text = copyText.html();
                    await navigator.clipboard.writeText(text);
                    copyText.html('Key copied to clipboard!').css({color: 'green'});
                    setTimeout(() => {
                        copyText.html(text).css({color: '#555'});
                    }, 3000);
                } catch (error) {
                    alert(error.message)
                }
            })();
        })
    </script>
@endsection
