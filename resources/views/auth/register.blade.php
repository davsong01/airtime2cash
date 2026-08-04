@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh col-12 px-0">
        <div class="auth-heading">
            <span class="auth-eyebrow"><i class="bx bx-shield-quarter"></i> Secure registration</span>
            <h1>Create your account</h1>
            <p>Enter accurate details so your account and KYC review can be completed without delays.</p>
        </div>

        @include('layouts.alerts')

        <form action="{{ route('register') }}" method="POST" autocomplete="on">
            @csrf

            <section class="auth-section">
                <div class="auth-section-header">
                    <span class="auth-step">01</span>
                    <div>
                        <h2>Personal details</h2>
                        <p>Use the same information shown on your identification documents.</p>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="firstName">First name <span class="auth-required">*</span></label>
                        <input type="text" class="form-control" id="firstName" name="first_name" value="{{ old('first_name') }}" placeholder="Enter first name" autocomplete="given-name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="lastName">Last name <span class="auth-required">*</span></label>
                        <input type="text" class="form-control" id="lastName" name="last_name" value="{{ old('last_name') }}" placeholder="Enter last name" autocomplete="family-name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email address <span class="auth-required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="phone">Phone number <span class="auth-required">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number" autocomplete="tel" inputmode="tel" required>
                    </div>
                </div>
            </section>

            <section class="auth-section">
                <div class="auth-section-header">
                    <span class="auth-step">02</span>
                    <div>
                        <h2>Account access</h2>
                        <p>Choose your login details and add a referral username if you have one.</p>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="username">Username <span class="auth-required">*</span></label>
                        <div class="auth-username-wrap">
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Choose or generate one" autocomplete="username" required>
                            <button type="button" class="auth-username-suggest" id="suggestUsername" aria-label="Generate a username">
                                <i class="bx bx-refresh"></i><span>Suggest</span>
                            </button>
                        </div>
                        <small class="auth-field-help">We can suggest one from your name, or you can enter your own.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="referral">Referral username</label>
                        <input type="text" class="form-control" id="referral" name="referral" value="{{ old('referral', request()->referral) }}" placeholder="Optional">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="password">Password <span class="auth-required">*</span></label>
                        <div class="auth-password-wrap">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" autocomplete="new-password" required>
                            <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                                <i class="bx bx-show"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <x-captcha />

            <div class="auth-consent">
                <input type="checkbox" id="privacy" name="privacy" value="1" required>
                <label for="privacy">
                    I agree to the <a target="_blank" rel="noopener" href="https://airtime2cash.com/terms-of-service/">Terms of Service</a> and confirm that the information provided is accurate.
                </label>
            </div>

            <button type="submit" class="auth-submit btn">
                Create account <i class="bx bx-right-arrow-alt"></i>
            </button>
        </form>

        <div class="auth-footer">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
        <div class="auth-support">@include('layouts.support')</div>
    </div>
@endsection

@section('page-script')
    @include('auth.partials.password-toggle-script')
    @include('auth.partials.username-suggestion-script')
@endsection
