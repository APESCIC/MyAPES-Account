<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\AuditAdminAuthorizationDenial;
use App\Http\Middleware\EnsureAuthorizationContext;
use App\Http\Middleware\EnsureModuleAvailable;
use App\Http\Middleware\RevalidateDirectoryAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        $middleware->redirectGuestsTo(
            fn (): string => route('public.login'),
        );

        $middleware->alias([
            'admin.denial-audit' => AuditAdminAuthorizationDenial::class,
            'authorization.context' => EnsureAuthorizationContext::class,
            'directory.current' => RevalidateDirectoryAccess::class,
            'module.available' => EnsureModuleAvailable::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
