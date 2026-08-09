<?php

namespace App\Services;

use App\Auth\OidcFlow;
use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\OidcProviderException;
use Illuminate\Http\RedirectResponse;
use Throwable;

class JumbojettOidcIdentityProvider implements OidcIdentityProvider
{
    public function __construct(
        private readonly OidcDiscoveryValidator $discovery,
    ) {}

    public function authorizationRedirect(
        OidcFlow $flow = OidcFlow::StaffLogin,
        bool $forceReauthentication = false,
    ): RedirectResponse {
        try {
            $client = $this->client($flow, $forceReauthentication);
            $authenticated = $client->authenticate();
            $redirect = $client->capturedRedirect();

            if ($authenticated || ! is_string($redirect) || trim($redirect) === '') {
                throw new OidcProviderException('OIDC authorization did not produce a redirect.');
            }

            return redirect()->away($redirect);
        } catch (OidcProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->providerFailure($exception);
        }
    }

    public function callbackIdentity(OidcFlow $flow = OidcFlow::StaffLogin): OidcIdentity
    {
        try {
            $client = $this->client($flow);

            if (! $client->authenticate()) {
                throw new OidcProviderException('OIDC callback did not authenticate an identity.');
            }

            $subject = $this->stringClaim($client->getVerifiedClaims('sub'));
            $userInfo = $client->requestUserInfo();

            if (! is_object($userInfo)) {
                throw new OidcProviderException('OIDC UserInfo response is invalid.');
            }

            $userInfoSubject = $this->stringClaim($userInfo->sub ?? null);

            if ($subject !== null && $userInfoSubject !== null && ! hash_equals($subject, $userInfoSubject)) {
                throw new OidcProviderException('OIDC subject claims do not match.');
            }

            return new OidcIdentity(
                subject: $subject,
                email: $this->stringClaim($userInfo->email ?? null),
                name: $this->stringClaim($userInfo->name ?? null),
            );
        } catch (OidcProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->providerFailure($exception);
        }
    }

    private function client(
        OidcFlow $flow,
        bool $forceReauthentication = false,
    ): LaravelOpenIdConnectClient {
        $issuer = $this->requiredString('issuer');
        $clientId = $this->requiredString('client_id');
        $clientSecret = $this->requiredString('client_secret');
        $redirectUri = $this->requiredString('redirect_uri');
        $scopes = config('myapes.oidc.scopes', []);

        if (! is_array($scopes) || $scopes === []) {
            throw new OidcProviderException('OIDC scopes are not configured.');
        }

        $client = new LaravelOpenIdConnectClient($issuer, $clientId, $clientSecret);
        $client->useSessionNamespace($flow->value);
        $client->providerConfigParam($this->discovery->validate($issuer));
        $client->setRedirectURL($redirectUri);
        $client->setCodeChallengeMethod('S256');
        $client->addScope(array_values(array_diff($scopes, ['openid'])));

        if ($forceReauthentication) {
            $client->addAuthParam(['prompt' => 'login']);
        }

        return $client;
    }

    private function requiredString(string $key): string
    {
        $value = config("myapes.oidc.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new OidcProviderException("OIDC {$key} is not configured.");
        }

        return trim($value);
    }

    private function stringClaim(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function providerFailure(Throwable $exception): OidcProviderException
    {
        return new OidcProviderException(
            'The Cloudron identity provider is temporarily unavailable.',
            previous: $exception,
        );
    }
}
