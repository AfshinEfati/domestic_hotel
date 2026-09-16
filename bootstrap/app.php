<?php

use App\Helpers\StatusHelper;
use App\Http\Middleware\EnsureSwaggerConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', EnsureSwaggerConfig::class);
    })
    ->withProviders([
        App\Providers\AdminServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\HotelServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return StatusHelper::errorResponse(
                    'validation error',
                    422,
                    $exception->errors()
                );
            }

            if ($exception instanceof AuthenticationException) {
                return StatusHelper::errorResponse('unauthorized', 401);
            }

            if ($exception instanceof AuthorizationException) {
                return StatusHelper::errorResponse('forbidden', 403);
            }

            if ($exception instanceof ModelNotFoundException) {
                return StatusHelper::errorResponse('not found', 404);
            }

            if ($exception instanceof QueryException) {
                return StatusHelper::errorResponse(
                    'database operation failed',
                    422,
                    config('app.debug') ? $exception->getMessage() : null
                );
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $status = $status >= 500 ? 400 : $status;
                $message = trim($exception->getMessage()) !== ''
                    ? $exception->getMessage()
                    : 'request failed';

                return StatusHelper::errorResponse($message, $status);
            }

            if ($exception instanceof \InvalidArgumentException || $exception instanceof \RuntimeException) {
                return StatusHelper::errorResponse($exception->getMessage(), 422);
            }

            report($exception);

            return StatusHelper::errorResponse(
                'request failed',
                400,
                config('app.debug') ? $exception->getMessage() : null
            );
        });
    })->create();
