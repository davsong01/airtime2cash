@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh auth-refresh--compact col-12 px-0">
        <div class="auth-heading">
            <span class="auth-heading-icon"><i class="bx bx-envelope"></i></span>
            <h1>Reset your password</h1>
            <p>Enter the email address connected to your account and we will send you a secure reset link.</p>
        </div>

        @include('layouts.alerts')

        <div class="auth-panel">
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" autofocus required>
                </div>
                <button type="submit" class="auth-submit btn">Send reset link <i class="bx bx-right-arrow-alt"></i></button>
            </form>
        </div>

        <div class="auth-footer"><a href="{{ route('login') }}"><i class="bx bx-left-arrow-alt align-middle"></i> Back to sign in</a></div>
        <div class="auth-support">@include('layouts.support')</div>
    </div>
@endsection
