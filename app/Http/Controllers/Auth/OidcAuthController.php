<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OidcIdentityProvider;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Exceptions\OidcProviderException;
use App\Http\Controllers\Controller;
use App\Http\Cookies\OidcReauthenticationCookie;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LdapGroupResolver;
use App\Services\RoleMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OidcAuthController extends Controller
{
    public function login(
        Request $request,
        OidcIdentityProvider $identityProvider,
        OidcReauthenticationCookie $reauthenticationCookie,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $forceReauthentication = $reauthenticationCookie->isRequired($request);

        if ($forceReauthentication) {
            $auditLogger->record('auth.oidc_reauthentication_required', context: [
                'reason' => 'explicit_logout',
            ]);
        }

        try {
            return $identityProvider->authorizationRedirect($forceReauthentication);
        } catch (OidcProviderException) {
            $auditLogger->record('auth.oidc_provider_unavailable', context: [
                'reason' => 'provider_unavailable',
            ]);
            abort(503, 'Cloudron sign-in is temporarily unavailable.');
        }
    }

    public function callback(
        Request $request,
        OidcIdentityProvider $identityProvider,
        LdapGroupResolver $ldapGroupResolver,
        RoleMapper $roleMapper,
        AuditLogger $auditLogger,
        OidcReauthenticationCookie $reauthenticationCookie,
    ): RedirectResponse {
        try {
            $identity = $identityProvider->callbackIdentity();
        } catch (OidcProviderException) {
            $auditLogger->record('auth.oidc_provider_unavailable', context: [
                'reason' => 'provider_unavailable',
            ]);
            abort(503, 'Cloudron sign-in is temporarily unavailable.');
        }

        if ($identity->email === null) {
            $auditLogger->record('auth.oidc_missing_email');
            abort(403, 'Authenticated identity did not include an email address.');
        }

        if ($identity->subject === null) {
            $auditLogger->record('auth.oidc_missing_subject');
            abort(403, 'Authenticated identity did not include a subject identifier.');
        }

        $email = Str::lower($identity->email);
        $sub = $identity->subject;

        try {
            $groups = $ldapGroupResolver->resolveByEmail($email);
        } catch (DirectoryIdentityNotFound) {
            $this->revokeKnownIdentity($sub, $auditLogger, 'identity_not_found');
            $auditLogger->record('auth.oidc_access_denied', context: [
                'reason' => 'identity_not_found',
            ]);
            abort(403, 'Your Cloudron account does not have MyAPES staff access.');
        } catch (DirectoryUnavailable) {
            $auditLogger->record('auth.ldap_resolution_failed', null, null, [
                'reason' => 'directory_unavailable',
            ]);
            abort(503, 'Staff access verification is temporarily unavailable.');
        }

        $role = $roleMapper->map($groups);

        if ($role === null) {
            $this->revokeKnownIdentity($sub, $auditLogger, 'no_approved_group');
            $auditLogger->record('auth.oidc_access_denied', context: [
                'reason' => 'no_approved_group',
                'group_count' => count($groups),
            ]);
            abort(403, 'Your Cloudron account does not have MyAPES staff access.');
        }

        $user = User::query()
            ->where('oidc_sub', $sub)
            ->first();

        if ($user === null) {
            $emailCollision = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists();

            if ($emailCollision) {
                $auditLogger->record('auth.oidc_email_conflict', null, null, [
                    'reason' => 'email_already_in_use',
                ]);
                abort(403, 'An account with this email already exists and cannot be auto-linked.');
            }

            $user = new User;
        } elseif (User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereKeyNot($user->getKey())
            ->exists()) {
            $auditLogger->record('auth.oidc_email_conflict', $user, $user, [
                'reason' => 'email_already_in_use',
            ]);
            abort(403, 'An account with this email already exists and cannot be linked.');
        }

        $user->oidc_sub = $sub;
        $user->name = $identity->name ?? $email;
        $user->email = $email;
        $user->role = $role;
        $user->ldap_groups = array_values(array_unique(array_map(
            static fn (string $group): string => strtolower(trim($group)),
            $groups,
        )));
        $user->email_verified_at = now();
        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put(RevalidateDirectoryAccess::SESSION_KEY, now()->timestamp);
        $auditLogger->record('auth.login_success', $user, $user, [
            'role' => $user->role,
            'group_count' => count($groups),
        ]);

        $response = redirect()->intended(route('dashboard'));

        return $reauthenticationCookie->isRequired($request)
            ? $response->withCookie($reauthenticationCookie->clear())
            : $response;
    }

    public function logout(
        Request $request,
        AuditLogger $auditLogger,
        OidcReauthenticationCookie $reauthenticationCookie,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();
        $directoryBacked = is_string($user?->oidc_sub) && trim($user->oidc_sub) !== '';

        if ($user !== null) {
            $auditLogger->record('auth.logout', $user, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()->route('home');

        return $directoryBacked
            ? $response->withCookie($reauthenticationCookie->mark())
            : $response;
    }

    private function revokeKnownIdentity(string $subject, AuditLogger $auditLogger, string $reason): void
    {
        $user = User::query()->where('oidc_sub', $subject)->first();

        if ($user === null) {
            return;
        }

        $previousRole = $user->role;
        $user->forceFill([
            'role' => User::ROLE_SERVICE_USER,
            'ldap_groups' => [],
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $auditLogger->record('auth.directory_access_revoked', $user, $user, [
            'from_role' => $previousRole,
            'reason' => $reason,
        ]);
    }
}
