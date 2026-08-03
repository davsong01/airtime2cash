<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class RouteProtectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $curRouteName = Route::currentRouteName();
        
        $routes = auth()->user()->admin->rolepermissions();
        // dd($routes, $curRouteName);
        if (in_array($curRouteName, $routes) || in_array(1, auth()->user()->admin->roleIds())) {
            return $next($request);
        } else {
            return back()->with('error', 'You cannot access this resource');
        }

        return $next($request);
    }
}
