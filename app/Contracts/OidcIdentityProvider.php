<?php

namespace App\Contracts;

use App\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;

interface OidcIdentityProvider
{
    public function authorizationRedirect(bool $forceReauthentication = false): RedirectResponse;

    public function callbackIdentity(): OidcIdentity;
}
