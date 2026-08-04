@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh auth-refresh--compact col-12 px-0">
        <div class="auth-heading">
            <span class="auth-heading-icon"><i class="bx bx-log-in-circle"></i></span>
            <h1>Welcome back</h1>
            <p>Sign in securely to manage your wallet, transactions, and account verification.</p>
        </div>

        @include('layouts.alerts')

        <div class="auth-panel">
            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" autofocus required>
                </div>
                <div class="form-group">
                    <div class="d-flex align-items-center justify-content-between">
                        <label for="password">Password</label>
                        <a href="{{ route('password.request') }}" class="auth-link small">Forgot password?</a>
                    </div>
                    <div class="auth-password-wrap">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="auth-remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Keep me signed in</label>
                    </div>
                </div>
                <button type="submit" class="auth-submit btn">Sign in <i class="bx bx-right-arrow-alt"></i></button>
            </form>
        </div>

        <div class="auth-footer">New to {{ config('app.name') }}? <a href="{{ route('register') }}">Create an account</a></div>
        <div class="auth-support">@include('layouts.support')</div>
    </div>
@endsection

@section('page-script')
    @include('auth.partials.password-toggle-script')
@endsection
