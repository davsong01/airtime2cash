<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ReservedAccountCreationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(isset(auth()->user()->customer) && auth()->user()->customer->kyc_status == 'verified'){
            if(auth()->user()->customer->reserved_accounts->count() < 1){
                $name = auth()->user()->firstname . ' ' . auth()->user()->lastname . ' ' . auth()->user()->middlename;

                $data = [
                        'BVN' => $request->BVN ?? kycStatus('BVN', auth()->user()->customer->id)['value'],
                        'customerName' => $name,
                        'accountName' => auth()->user()->firstname,
                        'customerEmail' => auth()->user()->email,
                        'customer_id' => auth()->user()->customer->id,
                        'getAllAvailableBanks' => true,
                    ];

                $reserved = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->createReservedAccount($data);
            }
        }   

        return $next($request);
    }

}
