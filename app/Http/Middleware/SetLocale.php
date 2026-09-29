<?php

namespace App\Http\Middleware;

use App\Core\Accounts\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the active locale for the request (#275).
 *
 * Order: authenticated user locale → session locale (guests) → config app.locale.
 * Only values in config('app.supported_locales') are accepted.
 */
class SetLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = array_keys(config('app.supported_locales', ['en_GB' => 'English (UK)']));

        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $candidate = (string) ($user->locale ?? '');
            if ($this->isSupported($candidate, $supported)) {
                return $candidate;
            }
        }

        $sessionLocale = $request->session()->get('locale');
        if (is_string($sessionLocale) && $this->isSupported($sessionLocale, $supported)) {
            return $sessionLocale;
        }

        $fallback = (string) config('app.locale', 'en_GB');

        return $this->isSupported($fallback, $supported) ? $fallback : 'en_GB';
    }

    /**
     * @param  list<string>  $supported
     */
    private function isSupported(string $locale, array $supported): bool
    {
        return $locale !== '' && in_array($locale, $supported, true);
    }
}
