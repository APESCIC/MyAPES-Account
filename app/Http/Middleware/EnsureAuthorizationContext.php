<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthorizationProfile;
use App\Services\SessionAuthorizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthorizationContext
{
    public function __construct(
        private readonly SessionAuthorizationContext $context,
        private readonly AuthorizationProfile $profile,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $this->canRestoreRememberedPassword($user)) {
            $this->context->recordPassword($request, $user);
        }

        if ($user === null || $this->context->isCurrent($request, $user)) {
            return $next($request);
        }

        $reason = $user->suspended_at === null
            ? 'missing_or_mismatched_context'
            : 'suspended';
        $this->auditLogger->record(
            'auth.authorization_context_invalidated',
            $user,
            $user,
            ['reason' => $reason],
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()
            ->route('public.login')
            ->withErrors([
                'email' => 'Please sign in again to continue.',
            ]);
    }

    private function canRestoreRememberedPassword(User $user): bool
    {
        return Auth::viaRemember()
            && $user->suspended_at === null
            && $user->identity_type === User::IDENTITY_LOCAL
            && ! $this->profile->hasDirectoryProtectedEligibility($user);
    }
}
