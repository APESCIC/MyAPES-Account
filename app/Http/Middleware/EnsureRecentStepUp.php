<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Step-up gate for sensitive account mutations (#233).
 *
 * Wave 1 uses Laravel password confirmation. Later waves may also accept
 * a recent passkey confirmation (#232) without changing route middleware names.
 */
class EnsureRecentStepUp
{
    public function __construct(
        private readonly RequirePassword $requirePassword,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null, ?string $passwordTimeoutSeconds = null): Response
    {
        return $this->requirePassword->handle(
            $request,
            $next,
            $redirectToRoute,
            $passwordTimeoutSeconds,
        );
    }
}
