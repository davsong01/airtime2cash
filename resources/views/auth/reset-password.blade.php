@extends('layouts.auth')
@section('body')
<!-- left section-login -->

<div class="col-md-12 col-12 px-0">
    <div class="card disable-rounded-right d-flex justify-content-center">
        <div class="card-header pb-1">
            <div class="card-title">
                <h4 class="text-center mb-2">Reset Password</h4>
            </div>
        </div>
        <div class="card-content">
            <div class="card-body">
               @include('layouts.alerts')
                <form action="{{ route('password.store') }}" method="POST">
                    @csrf
                    <div class="row">

                    </div>
                    <div class="form-group mb-50">
                        <label class="text-bold-600" for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') ?? $request->email }}" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label class="text-bold-600" for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
                    </div>
                    <div class="form-group">
                        <label class="text-bold-600" for="password">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary glow w-100 position-relative">Reset Password<i id="icon-arrow" class="bx bx-right-arrow-alt"></i></button>
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                </form>
                <hr>
                <div class="text-center"><small class="mr-25">I remember my passsword</small><a href="{{ route('register') }}"><small>Sign up</small></a></div>
            </div>
        </div>
    </div>
</div>
@endsection

