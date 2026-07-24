<?php

namespace Tests\Feature\Auth;

use App\Http\Cookies\OidcReauthenticationCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudronOidcRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const ISSUER = 'https://my.cloudron.apes.org.uk/openid';

    private const DISCOVERY = 'https://my.cloudron.apes.org.uk/.well-known/openid-configuration';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'myapes.oidc.issuer' => self::ISSUER,
            'myapes.oidc.client_id' => 'test-client-id',
            'myapes.oidc.client_secret' => 'test-client-secret',
            'myapes.oidc.redirect_uri' => 'https://myaccount.myapes.me.uk/staff/auth/callback',
            'myapes.oidc.scopes' => ['openid', 'profile', 'email'],
        ]);

        Http::preventStrayRequests();
        Http::fake([
            self::DISCOVERY => Http::response($this->metadata()),
        ]);
    }

    public function test_staff_login_uses_cloudron_authorization_code_flow_with_pkce_s256(): void
    {
        $response = $this->get(route('staff.auth.login'));

        $response->assertRedirect();
        $query = $this->redirectQuery($response->headers->get('Location'));

        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame(
            'https://myaccount.myapes.me.uk/staff/auth/callback',
            $query['redirect_uri'] ?? null,
        );
        $this->assertSame('test-client-id', $query['client_id'] ?? null);
        $this->assertSame('S256', $query['code_challenge_method'] ?? null);
        $this->assertMatchesRegularExpression(
            '/\A[A-Za-z0-9_-]{43}\z/',
            (string) ($query['code_challenge'] ?? ''),
        );
        $this->assertContains('openid', explode(' ', (string) ($query['scope'] ?? '')));
        $this->assertContains('profile', explode(' ', (string) ($query['scope'] ?? '')));
        $this->assertContains('email', explode(' ', (string) ($query['scope'] ?? '')));
        $this->assertNotEmpty($query['state'] ?? null);
        $this->assertNotEmpty($query['nonce'] ?? null);

        $response->assertSessionHas('myapes.oidc.openid_connect_state');
        $response->assertSessionHas('myapes.oidc.openid_connect_nonce');
        $response->assertSessionHas('myapes.oidc.openid_connect_code_verifier');
        Http::assertSentCount(1);
    }

    public function test_force_reauthentication_marker_adds_prompt_login(): void
    {
        $response = $this
            ->withCookie(OidcReauthenticationCookie::NAME, '1')
            ->get(route('staff.auth.login'));

        $response->assertRedirect();
        $query = $this->redirectQuery($response->headers->get('Location'));

        $this->assertSame('login', $query['prompt'] ?? null);
        $response->assertCookieMissing(OidcReauthenticationCookie::NAME);
    }

    /**
     * @return array<string, string>
     */
    private function redirectQuery(?string $location): array
    {
        $this->assertIsString($location);
        $this->assertStringStartsWith(self::ISSUER.'/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return array_filter(
            $query,
            static fn (mixed $value): bool => is_string($value),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(): array
    {
        return [
            'issuer' => self::ISSUER,
            'authorization_endpoint' => self::ISSUER.'/auth',
            'token_endpoint' => self::ISSUER.'/token',
            'userinfo_endpoint' => self::ISSUER.'/me',
            'jwks_uri' => self::ISSUER.'/jwks',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
        ];
    }
}
