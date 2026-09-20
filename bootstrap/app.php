
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

            $debug = app()->environment('local')
                && (bool) config('app.debug');

            $respond = static function (
                string $message,
                int $status,
                mixed $errors,
                \Throwable $exception
            ) use ($debug) {

                $response = StatusHelper::errorResponse(
                    $message,
                    $status,
                    $errors
                );

                if (!$debug || $status < 500) {
                    return $response;
                }

                $payload = $response->getData(true);

                $payload['debug'] = [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];

                return response()->json($payload, $status);
            };

            if ($exception instanceof ValidationException) {
                return $respond(
                    'validation error',
                    422,
                    $exception->errors(),
                    $exception
                );
            }

            if ($exception instanceof AuthenticationException) {
                return $respond(
                    'unauthorized',
                    401,
                    null,
                    $exception
                );
            }

            if ($exception instanceof AuthorizationException) {
                return $respond(
                    'forbidden',
                    403,
                    null,
                    $exception
                );
            }

            if ($exception instanceof ModelNotFoundException) {
                return $respond(
                    'not found',
                    404,
                    null,
                    $exception
                );
            }

            if ($exception instanceof QueryException) {
                report($exception);

                return $respond(
                    'database operation failed',
                    500,
                    $debug ? $exception->getMessage() : null,
                    $exception
                );
            }

            if ($exception instanceof HttpExceptionInterface) {

                $status = $exception->getStatusCode();

                $message = $status >= 500
                    ? 'request failed'
                    : (
                    trim($exception->getMessage()) !== ''
                        ? $exception->getMessage()
                        : 'request failed'
                    );

                return $respond(
                    $message,
                    $status,
                    null,
                    $exception
                );
            }

            if ($exception instanceof \InvalidArgumentException) {
                return $respond(
                    $exception->getMessage(),
                    422,
                    null,
                    $exception
                );
            }

            if ($exception instanceof \RuntimeException) {
                report($exception);

                return $respond(
                    $debug ? $exception->getMessage() : 'request failed',
                    422,
                    null,
                    $exception
                );
            }

            report($exception);

            return $respond(
                'request failed',
                500,
                $debug ? $exception->getMessage() : null,
                $exception
            );
        });

    })->create();
