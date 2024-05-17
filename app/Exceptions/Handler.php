<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */

    public function render($request, \Throwable $exception)
    {

        if ($exception instanceof TokenMismatchException) {
            // Handle CSRF token mismatch (419 error) here
            \Log::error(['419 Error' => $exception->getMessage()]);
            return response()->view('errors.404', ['exception' => $exception], 419);
        }

        if ($exception instanceof NotFoundHttpException) {
            // Handle 404 errors here
            \Log::error(['404 Error' => $exception->getMessage()]);
            return response()->view('errors.404', ['exception' => $exception], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            // Handle Method Not Allowed errors here
            \Log::error(['405 Error' => $exception->getMessage()]);
            return response()->view('errors.404', ['exception' => $exception], 405);
        }

        return parent::render($request, $exception);
    }

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
