@extends('layouts.auth')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/auth-refresh.css') }}">
@endsection

@section('body')
    <div class="auth-refresh auth-refresh--compact col-12 px-0">
        <div class="auth-heading">
            <span class="auth-heading-icon"><i class="bx bx-shield-quarter"></i></span>
            <h1>Confirm your password</h1>
            <p>This is a protected area. Confirm your current password before continuing.</p>
        </div>

        @include('layouts.alerts')

        <div class="auth-panel">
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div class="form-group">
                    <label for="password">Current password</label>
                    <div class="auth-password-wrap">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter current password" autocomplete="current-password" autofocus required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>
                <button class="auth-submit btn" type="submit">Confirm and continue <i class="bx bx-right-arrow-alt"></i></button>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    @include('auth.partials.password-toggle-script')
@endsection
