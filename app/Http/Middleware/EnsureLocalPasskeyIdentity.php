<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate passkey enrolment/management to local password identities (#232).
 */
class EnsureLocalPasskeyIdentity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isLocalPasswordIdentity()) {
            abort(403, __('auth.passkeys.local_only'));
        }

        return $next($request);
    }
}
