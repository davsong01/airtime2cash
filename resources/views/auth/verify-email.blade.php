@extends('layouts.auth')
@section('body')
<div class="col-md-12 col-12 px-0">
    <div class="card disable-rounded-right d-flex justify-content-center">
        <div class="card-header pb-1">
            <div class="card-title">
                <h4 class="text-center mb-2">Email Verification</h4>
            </div>
        </div>
        <div class="card-content">
            <div class="card-body">
               @include('layouts.alerts')
               <form method="POST" action="{{ route('verification.send') }}" class="text-center center">
                            @csrf
                            <p class="text-center text-muted">
                                Thanks for signing up! Before getting started, please verify your email address by clicking on the link that was sent to your registered email account. If you didn't receive the email, you can click the button bellow to resend verification email.
                            </p>
                            <div>
                                <button class="btn btn-primary btn-large mt-3" type="submit">
                                    Resend Verification Email
                                </button>
                            </div>
                        </form>
                <hr>
                <div class="text-center"><small class="mr-25">Don't have an account?</small><a href="{{ route('register') }}"><small>Sign up</small></a></div>
                @include('layouts.support')
            </div>
        </div>
    </div>
</div>
@endsection