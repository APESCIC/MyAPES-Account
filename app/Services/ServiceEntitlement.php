<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class ServiceEntitlement
{
    public function __construct(
        private readonly SessionAuthorizationContext $context,
        private readonly AuthorizationProfile $profile,
    ) {}

    public function allows(User $user, string $subCoreKey, Request $request): bool
    {
        $method = $this->context->authenticationMethod($request);

        if ($method === SessionAuthorizationContext::METHOD_CLOUDRON_OIDC
            && $user->hasDirectoryIdentity()
            && $this->profile->hasDirectoryProtectedEligibility($user)) {
            return true;
        }

        if ($method === SessionAuthorizationContext::METHOD_QA
            && $this->profile->hasDirectoryProtectedEligibility($user)) {
            return true;
        }

        return $user->serviceSelections()
            ->where('sub_core_key', $subCoreKey)
            ->exists();
    }
}
