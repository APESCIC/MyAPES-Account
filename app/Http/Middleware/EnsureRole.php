<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            app(AuditLogger::class)->record('auth.unauthenticated_access_blocked', null, null, [
                'path' => $request->path(),
                'required_roles' => $roles,
            ]);
            abort(401);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            app(AuditLogger::class)->record('auth.role_access_denied', $user, null, [
                'path' => $request->path(),
                'required_roles' => $roles,
                'actual_role' => $user->role,
            ]);
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
