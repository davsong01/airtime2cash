@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh auth-refresh--compact col-12 px-0">
        <div class="auth-heading">
            <span class="auth-heading-icon"><i class="bx bx-mail-send"></i></span>
            <h1>Verify your email</h1>
            <p>We sent a verification link to your registered email address. Open the message and follow the link to continue.</p>
        </div>

        @include('layouts.alerts')

        <div class="auth-panel">
            <div class="auth-notice">
                The link may take a few minutes to arrive. Check your spam or promotions folder before requesting another email.
            </div>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="auth-submit btn" type="submit">Resend verification email <i class="bx bx-refresh"></i></button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="auth-submit auth-submit--secondary btn" type="submit">Sign out</button>
            </form>
        </div>

        <div class="auth-support">@include('layouts.support')</div>
    </div>
@endsection
