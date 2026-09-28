<?php

namespace App\Core\Providers;

use App\Contracts\OidcIdentityProvider;
use App\Services\JumbojettOidcIdentityProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Core auth bindings and public-account rate limiters (#282).
 */
class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OidcIdentityProvider::class, JumbojettOidcIdentityProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('public-login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('login'));

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('public-password-reset', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('public-password-change', function (Request $request): Limit {
            $userId = (string) ($request->user()?->getAuthIdentifier() ?? 'guest');

            return Limit::perMinute(5)->by(Str::transliterate($userId.'|'.$request->ip()));
        });
    }
}
