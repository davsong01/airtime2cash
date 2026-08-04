<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->type !== 'customer' || $user->status === 'active') {
            return $next($request);
        }

        $status = $user->status;

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $message = match ($status) {
            'delete' => 'This account has been deleted. Please contact support.',
            'suspended' => 'This account has been suspended. Please contact support.',
            default => 'This account is not active. Please contact support.',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
