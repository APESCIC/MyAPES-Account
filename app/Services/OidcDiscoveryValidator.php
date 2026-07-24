<?php

namespace App\Services;

use App\Exceptions\AuthReadinessException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OidcDiscoveryValidator
{
    /**
     * @return array<string, mixed>
     */
    public function validate(string $issuer): array
    {
        $issuer = rtrim(trim($issuer), '/');

        if (! $this->isHttpsUrl($issuer)) {
            throw new AuthReadinessException('oidc_discovery', 'invalid_issuer_url');
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->get($this->discoveryUrl($issuer));
        } catch (Throwable) {
            throw new AuthReadinessException('oidc_discovery', 'request_failed');
        }

        if (! $response->successful()) {
            throw new AuthReadinessException('oidc_discovery', 'http_failure');
        }

        $metadata = $response->json();

        if (! is_array($metadata)) {
            throw new AuthReadinessException('oidc_discovery', 'invalid_json');
        }

        if (($metadata['issuer'] ?? null) !== $issuer) {
            throw new AuthReadinessException('oidc_discovery', 'issuer_mismatch');
        }

        $expectedEndpoints = [
            'authorization_endpoint' => $issuer.'/auth',
            'token_endpoint' => $issuer.'/token',
            'userinfo_endpoint' => $issuer.'/me',
            'jwks_uri' => $issuer.'/jwks',
        ];

        foreach ($expectedEndpoints as $key => $expected) {
            if (($metadata[$key] ?? null) !== $expected || ! $this->isHttpsUrl($expected)) {
                throw new AuthReadinessException('oidc_discovery', 'endpoint_mismatch');
            }
        }

        $this->requireMetadataValue($metadata, 'response_types_supported', 'code', 'authorization_code_missing');
        $this->requireMetadataValue($metadata, 'grant_types_supported', 'authorization_code', 'authorization_grant_missing');
        $this->requireMetadataValue($metadata, 'code_challenge_methods_supported', 'S256', 'pkce_s256_missing');
        $this->requireMetadataValue($metadata, 'token_endpoint_auth_methods_supported', 'client_secret_basic', 'client_auth_missing');
        $this->requireMetadataValue($metadata, 'id_token_signing_alg_values_supported', 'RS256', 'rs256_missing');

        foreach (['openid', 'profile', 'email'] as $scope) {
            $this->requireMetadataValue($metadata, 'scopes_supported', $scope, 'required_scope_missing');
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function requireMetadataValue(
        array $metadata,
        string $key,
        string $required,
        string $reason,
    ): void {
        $values = $metadata[$key] ?? null;

        if (! is_array($values) || ! in_array($required, $values, true)) {
            throw new AuthReadinessException('oidc_discovery', $reason);
        }
    }

    private function isHttpsUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    private function discoveryUrl(string $issuer): string
    {
        $parts = parse_url($issuer);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new AuthReadinessException('oidc_discovery', 'invalid_issuer_url');
        }

        $authority = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $authority .= ':'.$parts['port'];
        }

        // Cloudron publishes discovery at the dashboard origin even though the
        // advertised issuer and protocol endpoints live below /openid.
        return $authority.'/.well-known/openid-configuration';
    }
}
