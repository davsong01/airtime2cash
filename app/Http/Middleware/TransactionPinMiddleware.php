<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TransactionPinMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(empty(auth()->user()->transaction_pin) && auth()->user()->type == 'customer'){
            $customer = auth()->user()->customer;

            if (layoutIsModern('customer') && $customer && getFinalKycStatus($customer->id) !== 'verified') {
                return $next($request);
            }

            return redirect(route('customer.create.pin'));
        }
        
        return $next($request);
    }
}
