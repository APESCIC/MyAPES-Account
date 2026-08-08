<?php

namespace App\Services;

use Jumbojett\OpenIDConnectClient;

class LaravelOpenIdConnectClient extends OpenIDConnectClient
{
    private ?string $capturedRedirect = null;

    private string $sessionNamespace = 'staff-login';

    public function useSessionNamespace(string $namespace): void
    {
        $this->sessionNamespace = $namespace;
    }

    public function redirect(string $url): void
    {
        $this->capturedRedirect = $url;
    }

    public function capturedRedirect(): ?string
    {
        return $this->capturedRedirect;
    }

    protected function startSession(): void
    {
        // Laravel owns the encrypted application session.
    }

    protected function commitSession(): void
    {
        // Laravel persists the session after the response is produced.
    }

    protected function getSessionKey(string $key): mixed
    {
        return session()->get($this->sessionKey($key), false);
    }

    protected function setSessionKey(string $key, mixed $value): void
    {
        session()->put($this->sessionKey($key), $value);
    }

    protected function unsetSessionKey(string $key): void
    {
        session()->forget($this->sessionKey($key));
    }

    private function sessionKey(string $key): string
    {
        return $this->sessionNamespace === 'staff-login'
            ? "myapes.oidc.{$key}"
            : "myapes.oidc.{$this->sessionNamespace}.{$key}";
    }
}
