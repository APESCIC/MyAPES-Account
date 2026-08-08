<?php

namespace Tests\Fakes;

use App\Auth\OidcFlow;
use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\OidcProviderException;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class FakeOidcIdentityProvider implements OidcIdentityProvider
{
    public ?OidcIdentity $identity = null;

    public ?OidcProviderException $authorizationFailure = null;

    public ?OidcProviderException $callbackFailure = null;

    public int $authorizationCalls = 0;

    public int $callbackCalls = 0;

    public ?bool $forceReauthentication = null;

    public ?OidcFlow $authorizationFlow = null;

    public ?OidcFlow $callbackFlow = null;

    public string $authorizationUrl = 'https://my.cloudron.apes.org.uk/openid/auth?client_id=test-client';

    public function authorizationRedirect(
        OidcFlow $flow = OidcFlow::StaffLogin,
        bool $forceReauthentication = false,
    ): RedirectResponse {
        $this->authorizationCalls++;
        $this->forceReauthentication = $forceReauthentication;
        $this->authorizationFlow = $flow;

        if ($this->authorizationFailure !== null) {
            throw $this->authorizationFailure;
        }

        return redirect()->away($this->authorizationUrl);
    }

    public function callbackIdentity(OidcFlow $flow = OidcFlow::StaffLogin): OidcIdentity
    {
        $this->callbackCalls++;
        $this->callbackFlow = $flow;

        if ($this->callbackFailure !== null) {
            throw $this->callbackFailure;
        }

        return $this->identity
            ?? throw new RuntimeException('A fake OIDC identity was not configured.');
    }
}
