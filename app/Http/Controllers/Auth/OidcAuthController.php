<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LdapGroupResolver;
use App\Services\RoleMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jumbojett\OpenIDConnectClient;
use RuntimeException;

class OidcAuthController extends Controller
{
    public function login(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $oidc = $this->buildClient();
        $oidc->authenticate();

        return redirect()->route('dashboard');
    }

    public function callback(LdapGroupResolver $ldapGroupResolver, RoleMapper $roleMapper): RedirectResponse
    {
        $oidc = $this->buildClient();
        $oidc->authenticate();

        $email = $oidc->requestUserInfo('email');
        $name = $oidc->requestUserInfo('name');
        $sub = $oidc->requestUserInfo('sub');

        if (! is_string($email) || trim($email) === '') {
            abort(403, 'Authenticated identity did not include an email address.');
        }

        if (! is_string($sub) || trim($sub) === '') {
            abort(403, 'Authenticated identity did not include a subject identifier.');
        }

        try {
            $groups = $ldapGroupResolver->resolveByEmail($email);
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        $role = $roleMapper->map($groups);

        $user = User::query()
            ->where('oidc_sub', $sub)
            ->orWhere('email', $email)
            ->first();

        if ($user === null) {
            $user = new User;
        }

        $user->oidc_sub = $sub;
        $user->name = is_string($name) && trim($name) !== '' ? $name : $email;
        $user->email = $email;
        $user->role = $role;
        $user->ldap_groups = $groups;
        $user->email_verified_at = now();
        $user->save();

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function buildClient(): OpenIDConnectClient
    {
        $issuer = config('myapes.oidc.issuer');
        $clientId = config('myapes.oidc.client_id');
        $clientSecret = config('myapes.oidc.client_secret');
        $redirectUri = config('myapes.oidc.redirect_uri');
        $scopes = config('myapes.oidc.scopes', ['openid', 'profile', 'email']);

        if (! is_string($issuer) || ! is_string($clientId) || ! is_string($clientSecret) || ! is_string($redirectUri)) {
            throw new RuntimeException('OIDC configuration is incomplete.');
        }

        $client = new OpenIDConnectClient($issuer, $clientId, $clientSecret);
        $client->setRedirectURL($redirectUri);
        $client->addScope($scopes);

        return $client;
    }
}
