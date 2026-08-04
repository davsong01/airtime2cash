<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsVerified
{
    public function __construct(private readonly EnsureEmailIsVerified $emailVerification)
    {
    }

    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = $request->user();

        if ($user?->type === 'customer' && layoutIsModern('customer')) {
            return $next($request);
        }

        return $this->emailVerification->handle($request, $next, $redirectToRoute);
    }
}
