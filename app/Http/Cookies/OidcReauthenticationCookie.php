<?php

namespace App\Http\Cookies;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class OidcReauthenticationCookie
{
    public const NAME = 'myapes_oidc_reauthenticate';

    private const LIFETIME_MINUTES = 365 * 24 * 60;

    private const PATH = '/staff/auth';

    public function __construct(
        private CookieFactory $cookies,
        private ConfigRepository $config,
    ) {}

    public function isRequired(Request $request): bool
    {
        return $request->cookie(self::NAME) === '1';
    }

    public function mark(): Cookie
    {
        return $this->cookies->make(
            self::NAME,
            '1',
            self::LIFETIME_MINUTES,
            self::PATH,
            $this->domain(),
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    public function clear(): Cookie
    {
        return $this->cookies->forget(self::NAME, self::PATH, $this->domain());
    }

    private function domain(): ?string
    {
        $domain = $this->config->get('session.domain');

        return is_string($domain) && trim($domain) !== '' ? $domain : null;
    }
}
