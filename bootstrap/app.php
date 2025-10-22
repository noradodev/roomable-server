<?php

use App\Http\Responses\ApiResponser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: [
            __DIR__ . '/../routes/api.php',
            __DIR__ . '/../routes/v1/v1_api.php',
        ],
        // channels: __DIR__ . '/../routes/channels.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api/v1', 'middleware' => ['auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })->withSchedule(function (Schedule $schedule) {
        // $schedule->command('rent:check')->everySecond(10);
        $schedule->command('app:create-pending-payments')->everySecond(10);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This handler will catch exceptions for API requests and return a JSON response.
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        // 401 Unauthenticated
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        });

        // 404 Not Found (Model)
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $modelName = ucfirst(class_basename($previous->getModel()));

                return ApiResponser::notFound("{$modelName} was not found!");
            }

            return ApiResponser::notFound("The request endpoint was not found!");
        });
        // 404 Not Found (Route)
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            return ApiResponser::notFound("The request endpoint was not found!");
        });

        // 403 Forbidden (Spatie Permissions)
        $exceptions->renderable(function (UnauthorizedException $e, Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have the required permissions to perform this action.'
            ], 403);
        });
    })->create();
