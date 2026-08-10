<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\AuditAdminAuthorizationDenial;
use App\Http\Middleware\EnsureAccountReady;
use App\Http\Middleware\EnsureAuthorizationContext;
use App\Http\Middleware\EnsureMaintenanceRecoveryAccess;
use App\Http\Middleware\EnsureModuleAvailable;
use App\Http\Middleware\EnsureServiceSelected;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Services\MaintenanceResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::get('/healthz', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance([
            'healthz',
            'staff/login',
            'staff/auth/login',
            'staff/auth/callback',
            'admin/maintenance',
            'admin/maintenance/activate',
            'admin/maintenance/deactivate',
        ]);

        $middleware->redirectGuestsTo(
            fn (): string => route('public.login'),
        );

        $middleware->alias([
            'admin.denial-audit' => AuditAdminAuthorizationDenial::class,
            'authorization.context' => EnsureAuthorizationContext::class,
            'directory.current' => RevalidateDirectoryAccess::class,
            'module.available' => EnsureModuleAvailable::class,
            'maintenance.recovery' => EnsureMaintenanceRecoveryAccess::class,
            'account.ready' => EnsureAccountReady::class,
            'service.selected' => EnsureServiceSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() === 503
                && app()->maintenanceMode()->active()
                && $request->expectsJson()) {
                return app(MaintenanceResponseFactory::class)->make($request);
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
