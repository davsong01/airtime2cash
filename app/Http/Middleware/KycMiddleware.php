<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class KycMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check()){
            $kyc_status = getFinalKycStatus(auth()->user()->customer->id);
            if($kyc_status != 'verified'){
                return redirect(route('dashboard'))->with(
                    'unverified',
                    'Identity verification is required to use this service. Please <a href="' . route('update.kyc.details') . '"><strong>complete your KYC now</strong></a> to continue.'
                );
            }else{
                return $next($request);
            }
        }
    }
}
