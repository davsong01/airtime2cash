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
        $permissionRoutes = match ($curRouteName) {
            'admin.kyc.customer-suggestions' => ['admin.kyc'],
            'admin.autosync.webhooks.index' => ['admin.autosync.webhooks.index', 'admin.autosync.index'],
            'admin.autosync.webhooks.resolve' => ['admin.autosync.webhooks.resolve', 'admin.autosync.webhooks.index', 'admin.autosync.index'],
            'admin.autosync.api-logs.index' => ['admin.autosync.api-logs.index', 'admin.autosync.index'],
            default => [$curRouteName],
        };

        $routes = auth()->user()->admin->rolepermissions();
        if (array_intersect($permissionRoutes, $routes) || in_array(1, auth()->user()->admin->roleIds())) {
            return $next($request);
        } else {
            return back()->with('error', 'You cannot access this resource');
        }

        return $next($request);
    }
}
