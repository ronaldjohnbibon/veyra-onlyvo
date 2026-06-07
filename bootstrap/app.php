<?php

use App\Admin\Auth\Http\Middleware\EnsureAdminIpAllowed;
use App\Admin\SystemSettings\Http\Middleware\EnsureNotInMaintenance;
use App\Shared\Http\Middleware\EnsureSanctumTokenName;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.ip'   => EnsureAdminIpAllowed::class,
            'token.name' => EnsureSanctumTokenName::class,
        ]);

        $middleware->group('api.auth', [
            'auth:sanctum',
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            EnsureNotInMaintenance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage() ?: 'Validation failed.',
                'errors'  => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (DomainException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage() ?: 'Request failed.',
                'errors'  => [],
            ], 400);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'errors'  => [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage() ?: 'Unauthorized.',
                'errors'  => [],
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status'  => false,
                'message' => 'Resource not found.',
                'errors'  => [],
            ], 404);
        });
    })->create();
