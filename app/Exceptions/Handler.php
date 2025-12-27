<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // default reporting
        });
    }

    public function render($request, Throwable $e)
    {
        // Always return JSON for API
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Resource not found',
                ], 404);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Forbidden',
                ], 403);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage() ?: 'Request error',
                ], $e->getStatusCode());
            }

            // Fallback
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return parent::render($request, $e);
    }
}
