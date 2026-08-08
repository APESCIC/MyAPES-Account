<?php

namespace App\Contracts;

use App\Auth\OidcFlow;
use App\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;

interface OidcIdentityProvider
{
    public function authorizationRedirect(
        OidcFlow $flow = OidcFlow::StaffLogin,
        bool $forceReauthentication = false,
    ): RedirectResponse;

    public function callbackIdentity(OidcFlow $flow = OidcFlow::StaffLogin): OidcIdentity;
}
