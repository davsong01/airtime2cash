@extends('layouts.auth')
@section('body')
<!-- left section-login -->

<div class="col-md-12 col-12 px-0">
    <div class="card disable-rounded-right d-flex justify-content-center">
        <div class="card-header pb-1">
            <div class="card-title">
                <h4 class="text-center mb-2">Forgot your password?</h4>
                <p> No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one</p>
            </div>
        </div>
        <div class="card-content">
            <div class="card-body">
               @include('layouts.alerts')
                <form action="{{ route('password.email') }}" method="POST">
                    @csrf
                    <div class="row">

                    </div>
                    <div class="form-group mb-500">
                        <label class="text-bold-600" for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email')}}" placeholder="Enter your email address"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary glow w-100 position-relative">Email Password Reset Link<i id="icon-arrow" class="bx bx-right-arrow-alt"></i></button>
                </form>
                <div class="text-center mt-2"><small class="mr-25">I remember my password</small><a href="{{ route('login') }}"><small>Sign In</small></a></div>
                @include('layouts.support')
            </div>
        </div>
    </div>
</div>
@endsection

