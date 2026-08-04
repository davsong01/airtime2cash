@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh auth-refresh--compact col-12 px-0">
        <div class="auth-heading">
            <span class="auth-heading-icon"><i class="bx bx-lock-open-alt"></i></span>
            <h1>Choose a new password</h1>
            <p>Use a strong password that you have not used previously on this account.</p>
        </div>

        @include('layouts.alerts')

        <div class="auth-panel">
            <form action="{{ route('password.store') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="password">New password</label>
                    <div class="auth-password-wrap">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" autocomplete="new-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="auth-password-wrap">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repeat new password" autocomplete="new-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>
                <button type="submit" class="auth-submit btn">Update password <i class="bx bx-check"></i></button>
            </form>
        </div>

        <div class="auth-footer"><a href="{{ route('login') }}">Return to sign in</a></div>
    </div>
@endsection

@section('page-script')
    @include('auth.partials.password-toggle-script')
@endsection
