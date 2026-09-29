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
 * Core auth bindings and public-account rate limiters (#282, #230).
 */
class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OidcIdentityProvider::class, JumbojettOidcIdentityProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('public-login', function (Request $request): array {
            $login = Str::lower((string) ($request->input('login') ?: $request->input('email')));

            return [
                Limit::perMinute(10)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(5)->by(Str::transliterate('account|'.$login)),
            ];
        });

        RateLimiter::for('public-password-reset-request', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(3)->by(Str::transliterate('account|'.$email)),
            ];
        });

        RateLimiter::for('public-password-reset-submit', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(5)->by(Str::transliterate('account|'.$email)),
            ];
        });

        RateLimiter::for('public-password-change', function (Request $request): array {
            $userId = (string) ($request->user()?->getAuthIdentifier() ?? 'guest');

            return [
                Limit::perMinute(5)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(5)->by(Str::transliterate('user|'.$userId)),
            ];
        });

        RateLimiter::for('public-username-change', function (Request $request): array {
            $userId = (string) ($request->user()?->getAuthIdentifier() ?? 'guest');

            return [
                Limit::perMinute(5)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(3)->by(Str::transliterate('user|'.$userId)),
            ];
        });

        RateLimiter::for('verification-resend', function (Request $request): array {
            $userId = (string) ($request->user()?->getAuthIdentifier() ?? 'guest');

            return [
                Limit::perMinute(3)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(2)->by(Str::transliterate('user|'.$userId)),
            ];
        });

        // Legacy alias kept for any remaining throttle:public-password-reset references.
        RateLimiter::for('public-password-reset', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by(Str::transliterate('ip|'.$request->ip())),
                Limit::perMinute(3)->by(Str::transliterate('account|'.$email)),
            ];
        });
    }
}
