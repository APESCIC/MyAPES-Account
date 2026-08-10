<?php

namespace App\Http\Controllers\Auth;

use App\Auth\OidcFlow;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Exceptions\OidcProviderException;
use App\Http\Controllers\Controller;
use App\Http\Cookies\OidcReauthenticationCookie;
use App\Models\OidcLinkIntent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DirectoryRoleSynchronizer;
use App\Services\LdapGroupResolver;
use App\Services\MaintenanceResponseFactory;
use App\Services\SessionAuthorizationContext;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OidcAuthController extends Controller
{
    public function login(
        Request $request,
        OidcIdentityProvider $identityProvider,
        OidcReauthenticationCookie $reauthenticationCookie,
        AuditLogger $auditLogger,
        MaintenanceMode $maintenanceMode,
        MaintenanceResponseFactory $maintenanceResponses,
    ): RedirectResponse|Response {
        if (Auth::check()) {
            if ($maintenanceMode->active()) {
                return $request->user()?->can('admin.maintenance.manage')
                    ? redirect()->route('admin.maintenance.index')
                    : $maintenanceResponses->make($request);
            }

            return redirect()->route('dashboard');
        }

        $forceReauthentication = $reauthenticationCookie->isRequired($request);

        if ($forceReauthentication) {
            $auditLogger->record('auth.oidc_reauthentication_required', context: [
                'reason' => 'explicit_logout',
            ]);
        }

        try {
            return $identityProvider->authorizationRedirect(
                OidcFlow::StaffLogin,
                $forceReauthentication,
            );
        } catch (OidcProviderException) {
            $auditLogger->record('auth.oidc_provider_unavailable', context: [
                'reason' => 'provider_unavailable',
            ]);
            abort(503, 'Cloudron sign-in is temporarily unavailable.');
        }
    }

    public function startLink(
        Request $request,
        OidcIdentityProvider $identityProvider,
        SessionAuthorizationContext $authorizationContext,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        if (! $user->hasVerifiedEmail()
            || $user->onboarding_completed_at === null
            || ! in_array($user->identity_type, [User::IDENTITY_LOCAL, User::IDENTITY_HYBRID], true)
            || $authorizationContext->authenticationMethod($request)
                !== SessionAuthorizationContext::METHOD_PASSWORD) {
            abort(403, 'Cloudron linking requires a verified password-authenticated public account.');
        }

        $user->oidcLinkIntents()
            ->whereNull('consumed_at')
            ->delete();
        $token = Str::random(64);
        $intent = $user->oidcLinkIntents()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(10),
        ]);
        $request->session()->put('myapes.oidc_link_intent', [
            'id' => $intent->id,
            'token' => $token,
            'user_id' => $user->id,
        ]);

        try {
            return $identityProvider->authorizationRedirect(
                OidcFlow::AccountLink,
                true,
            );
        } catch (OidcProviderException) {
            $intent->delete();
            $request->session()->forget('myapes.oidc_link_intent');
            $auditLogger->record('auth.oidc_link_provider_unavailable', $user, $user);
            abort(503, 'Cloudron linking is temporarily unavailable.');
        }
    }

    public function callback(
        Request $request,
        OidcIdentityProvider $identityProvider,
        LdapGroupResolver $ldapGroupResolver,
        DirectoryRoleSynchronizer $roles,
        SessionAuthorizationContext $authorizationContext,
        AuditLogger $auditLogger,
        OidcReauthenticationCookie $reauthenticationCookie,
        MaintenanceMode $maintenanceMode,
        MaintenanceResponseFactory $maintenanceResponses,
    ): RedirectResponse|Response {
        $linking = $request->session()->has('myapes.oidc_link_intent');
        try {
            $identity = $identityProvider->callbackIdentity(
                $linking ? OidcFlow::AccountLink : OidcFlow::StaffLogin,
            );
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

        if ($linking) {
            if ($authorizationContext->authenticationMethod($request)
                !== SessionAuthorizationContext::METHOD_PASSWORD) {
                $request->session()->forget('myapes.oidc_link_intent');
                abort(403, 'Cloudron linking requires the original password-authenticated session.');
            }

            return $this->completeLink(
                $request,
                $email,
                $sub,
                $ldapGroupResolver,
                $roles,
                $authorizationContext,
                $auditLogger,
            );
        }

        $knownUser = User::query()->where('oidc_sub', $sub)->first();

        if ($knownUser?->suspended_at !== null) {
            $auditLogger->record(
                'auth.suspended_login_denied',
                $knownUser,
                $knownUser,
                ['method' => SessionAuthorizationContext::METHOD_CLOUDRON_OIDC],
            );
            abort(403, 'This account is suspended.');
        }

        try {
            $groups = $ldapGroupResolver->resolveByEmail($email);
        } catch (DirectoryIdentityNotFound) {
            $this->revokeKnownIdentity(
                $sub,
                $roles,
                $auditLogger,
                'identity_not_found',
            );
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

        $role = $roles->protectedRoleForGroups($groups);

        if ($role === null) {
            $this->revokeKnownIdentity(
                $sub,
                $roles,
                $auditLogger,
                'no_approved_group',
            );
            $auditLogger->record('auth.oidc_access_denied', context: [
                'reason' => 'no_approved_group',
                'group_count' => count($groups),
            ]);
            abort(403, 'Your Cloudron account does not have MyAPES staff access.');
        }

        $user = $knownUser;

        if ($user === null) {
            $emailCollision = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists();

            if ($emailCollision) {
                $auditLogger->record('auth.oidc_email_conflict', null, null, [
                    'reason' => 'email_already_in_use',
                ]);
                abort(403, 'An account with this email already exists. Sign in to that public account and use the explicit Cloudron linking flow.');
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
        if ($knownUser !== null
            && $user->identity_type === User::IDENTITY_LOCAL
            && is_string($user->password)
            && trim($user->password) !== '') {
            $user->identity_type = User::IDENTITY_HYBRID;
        } elseif ($user->identity_type !== User::IDENTITY_HYBRID) {
            $user->identity_type = User::IDENTITY_CLOUDRON_OIDC;
        }
        $user->name = $identity->name ?? $email;
        $user->email = $email;
        $user->email_verified_at = now();
        $user->save();
        $result = $roles->synchronize($user, $groups);

        if (! $result->eligible) {
            $auditLogger->record('auth.oidc_access_denied', $user, $user, [
                'reason' => 'eligibility_changed_before_reconciliation',
                'group_count' => count($groups),
            ]);
            abort(403, 'Your Cloudron account does not have MyAPES staff access.');
        }

        $user->refresh();

        Auth::login($user, false);
        $request->session()->regenerate();
        $authorizationContext->recordCloudronOidc($request, $user);
        $auditLogger->record('auth.login_success', $user, $user, [
            'role' => $result->protectedRole,
            'group_count' => count($groups),
        ]);

        if ($maintenanceMode->active()) {
            $response = $user->can('admin.maintenance.manage')
                ? redirect()->route('admin.maintenance.index')
                : $maintenanceResponses->make($request);
        } else {
            $response = redirect()->intended(route('dashboard'));
        }

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
        $directoryBacked = $user?->hasDirectoryIdentity() ?? false;

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

    private function revokeKnownIdentity(
        string $subject,
        DirectoryRoleSynchronizer $roles,
        AuditLogger $auditLogger,
        string $reason,
    ): void {
        $user = User::query()->where('oidc_sub', $subject)->first();

        if ($user === null) {
            return;
        }

        $result = $roles->revoke($user);

        $auditLogger->record('auth.directory_access_revoked', $user, $user, [
            'from_role' => $result->previousProtectedRole,
            'reason' => $reason,
        ]);
    }

    private function completeLink(
        Request $request,
        string $email,
        string $subject,
        LdapGroupResolver $ldapGroupResolver,
        DirectoryRoleSynchronizer $roles,
        SessionAuthorizationContext $authorizationContext,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $marker = $request->session()->pull('myapes.oidc_link_intent');
        /** @var User|null $user */
        $user = $request->user();

        if (! is_array($marker)
            || ! isset($marker['id'], $marker['token'], $marker['user_id'])
            || $user === null
            || (int) $marker['user_id'] !== (int) $user->id
            || $user->suspended_at !== null
            || ! $user->hasVerifiedEmail()
            || $user->onboarding_completed_at === null
            || ! hash_equals(Str::lower($user->email), $email)) {
            abort(403, 'The Cloudron identity could not be linked.');
        }

        $intent = OidcLinkIntent::query()->find($marker['id']);
        if ($intent === null
            || $intent->user_id !== $user->id
            || $intent->consumed_at !== null
            || $intent->expires_at->isPast()
            || ! hash_equals($intent->token_hash, hash('sha256', (string) $marker['token']))
            || User::query()->where('oidc_sub', $subject)->whereKeyNot($user->id)->exists()) {
            abort(403, 'The Cloudron identity could not be linked.');
        }

        try {
            $groups = $ldapGroupResolver->resolveByEmail($email);
        } catch (DirectoryIdentityNotFound) {
            abort(403, 'The Cloudron identity does not have MyAPES staff access.');
        } catch (DirectoryUnavailable) {
            abort(503, 'Staff access verification is temporarily unavailable.');
        }

        if ($roles->protectedRoleForGroups($groups) === null) {
            abort(403, 'The Cloudron identity does not have MyAPES staff access.');
        }

        DB::transaction(function () use ($intent, $user, $subject, $groups, $roles): void {
            $lockedIntent = OidcLinkIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedIntent->consumed_at !== null
                || $lockedIntent->expires_at->isPast()
                || User::query()->where('oidc_sub', $subject)->whereKeyNot($lockedUser->id)->exists()) {
                abort(403, 'The Cloudron identity could not be linked.');
            }

            $lockedUser->oidc_sub = $subject;
            $lockedUser->identity_type = User::IDENTITY_HYBRID;
            $lockedUser->save();
            $result = $roles->synchronize($lockedUser, $groups);

            if (! $result->eligible) {
                abort(403, 'The Cloudron identity does not have MyAPES staff access.');
            }

            $lockedIntent->update(['consumed_at' => now()]);
        });

        $user->refresh();
        Auth::login($user, false);
        $request->session()->regenerate();
        $authorizationContext->recordCloudronOidc($request, $user);
        $auditLogger->record('auth.oidc_identity_linked', $user, $user, [
            'group_count' => count($groups),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Cloudron identity linked.');
    }
}
