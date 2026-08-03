<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use SebastianBergmann\Type\NullType;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\ExternalApiController;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() != "POST" && $request->method() != "GET") {
            $json = [
                'status' => 'error',
                'message' => 'METHOD NOT ALLOWED',
                'errors' => 'METHOD NOT ALLOWED',
                'data' => null
            ];
            return app('App\Http\Controllers\ExternalApiController')->toJson($json);
        }

        if (empty($request->header("api-key"))) {
            $json = [
                'status' => 'error',
                'message' => 'api-key is required in request header',
                'errors' => 'INVALID CREDENTIALS',
                'data' => null
            ];
            return app('App\Http\Controllers\ExternalApiController')->toJson($json);
        }

        if ($request->method() == "GET" && empty($request->header("public-key"))) {
            $json = [
                'status' => 'error',
                'message' => 'public-key is required in request header for GET requests',
                'errors' => 'INVALID CREDENTIALS',
                'data' => null
            ];
            return app('App\Http\Controllers\ExternalApiController')->toJson($json);
        }

        if ($request->method() == "POST" && empty($request->header("secret-key"))) {
            $json = [
                'status' => 'error',
                'message' => 'secret-key is required in request header for POST requests',
                'errors' => 'INVALID CREDENTIALS',
                'data' => null
            ];
            return app('App\Http\Controllers\ExternalApiController')->toJson($json);
        }

        $user = User::where("api_key", $request->header("api-key"))->first();
        
        if (empty($user)) {
            $json = [
                'status' => 'error',
                'message' => 'User not found',
                'errors' => 'INVALID CREDENTIALS',
                'data' => null
            ];
            return app('App\Http\Controllers\ExternalApiController')->toJson($json);
        }

        if ($request->method() == "GET") {
            if (!Hash::check($request->header("public-key"), $user->public_key)) {
                $json = [
                    'status' => 'error',
                    'message' => '',
                    'errors' => 'INVALID CREDENTIALS',
                    'data' => null
                ];
                return app('App\Http\Controllers\ExternalApiController')->toJson($json);
            }
        } elseif ($request->method() == "POST") {
            if (!Hash::check($request->header("secret-key"), $user->secret_key)) {
                $json = [
                    'status' => 'error',
                    'message' => '',
                    'errors' => 'INVALID CREDENTIALS',
                    'data' => null
                ];
                return app('App\Http\Controllers\ExternalApiController')->toJson($json);
            }
        }

        Auth::login($user);
        return $next($request);
    }
}
