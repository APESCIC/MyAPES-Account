<?php

namespace Tests\Fakes;

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

    public string $authorizationUrl = 'https://my.cloudron.apes.org.uk/openid/auth?client_id=test-client';

    public function authorizationRedirect(bool $forceReauthentication = false): RedirectResponse
    {
        $this->authorizationCalls++;
        $this->forceReauthentication = $forceReauthentication;

        if ($this->authorizationFailure !== null) {
            throw $this->authorizationFailure;
        }

        return redirect()->away($this->authorizationUrl);
    }

    public function callbackIdentity(): OidcIdentity
    {
        $this->callbackCalls++;

        if ($this->callbackFailure !== null) {
            throw $this->callbackFailure;
        }

        return $this->identity
            ?? throw new RuntimeException('A fake OIDC identity was not configured.');
    }
}
