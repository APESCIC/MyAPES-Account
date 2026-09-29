<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Step-up gate for sensitive account mutations (#233 / #232).
 *
 * Uses Laravel password confirmation. Passkey confirmation (#232) also calls
 * session passwordConfirmed(), so a recent passkey confirm satisfies this gate.
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
