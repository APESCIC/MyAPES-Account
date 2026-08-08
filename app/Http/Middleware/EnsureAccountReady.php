<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\SessionAuthorizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountReady
{
    public function __construct(
        private readonly SessionAuthorizationContext $context,
        private readonly AuthorizationProfile $profile,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();
        $method = $this->context->authenticationMethod($request);
        $staffSession = ($method === SessionAuthorizationContext::METHOD_CLOUDRON_OIDC
                && $user->hasDirectoryIdentity())
            || ($method === SessionAuthorizationContext::METHOD_QA
                && $this->profile->hasDirectoryProtectedEligibility($user));

        if ($staffSession) {
            return $next($request);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->onboarding_completed_at === null) {
            return redirect()->route('onboarding.edit');
        }

        return $next($request);
    }
}
