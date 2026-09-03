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
        if (Auth::check()) {
            $user = auth()->user();

            if (($user?->type ?? null) !== 'customer') {
                return $next($request);
            }

            $customer = $user?->customer;

            if (! $customer) {
                abort(403, 'Customer profile not found.');
            }

            $kyc_status = getFinalKycStatus($customer->id);
    
            if ($kyc_status != 'verified') {
                $redirectToKyc = in_array($kyc_status, ['pending', 'awaiting-approval'], true);

                return redirect($redirectToKyc ? route('update.kyc.details') : route('dashboard'))->with(
                    'unverified',
                    $redirectToKyc
                        ? 'Identity verification is required to use this service. Please complete your KYC to continue.'
                        : 'Identity verification is required to use this service. Please <a href="' . route('update.kyc.details') . '"><strong>complete your KYC now</strong></a> to continue.'
                );
            }

            return $next($request);
        }

        return $next($request);
    }
}
