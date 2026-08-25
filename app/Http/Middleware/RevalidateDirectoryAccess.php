<?php

namespace App\Http\Middleware;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DirectoryRoleSynchronizer;
use App\Services\DirectoryUserSynchronizer;
use App\Services\LdapGroupResolver;
use App\Services\SessionAuthorizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RevalidateDirectoryAccess
{
    public const SESSION_KEY = SessionAuthorizationContext::DIRECTORY_VALIDATED_AT_KEY;

    public function __construct(
        private readonly LdapGroupResolver $directory,
        private readonly DirectoryRoleSynchronizer $roles,
        private readonly SessionAuthorizationContext $authorizationContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->requiresRevalidation($user)) {
            return $next($request);
        }

        if ($this->authorizationContext->permitsDirectoryRestricted(
            $request,
            $user,
        )) {
            return $next($request);
        }

        try {
            $groups = $this->directory->resolveByEmail($user->email);
        } catch (DirectoryIdentityNotFound) {
            return $this->revoke($request, $user, 'identity_not_found');
        } catch (DirectoryUnavailable) {
            $this->auditLogger->record('auth.directory_unavailable', $user, $user, [
                'reason' => 'directory_unavailable',
            ]);

            abort(503, 'Staff access verification is temporarily unavailable.');
        }

        $result = $this->roles->synchronize($user, $groups);

        if (! $result->eligible) {
            if ($user->suspended_at === null) {
                $user->forceFill([
                    'suspended_at' => now(),
                    'suspended_by' => null,
                    'suspension_reason' => DirectoryUserSynchronizer::SUSPENSION_REASON_DIRECTORY_DISABLED,
                    'authorization_epoch' => (int) $user->authorization_epoch + 1,
                ]);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }

            return $this->logoutAfterRevocation(
                $request,
                $user,
                $result->previousProtectedRole,
                'no_approved_group',
            );
        }

        if ($result->authorizationChanged) {
            $this->auditLogger->record('auth.directory_role_changed', $user, $user, [
                'from_role' => $result->previousProtectedRole,
                'to_role' => $result->protectedRole,
                'group_count' => count($groups),
            ]);
        }

        $user->refresh();
        $this->authorizationContext->recordCloudronOidc($request, $user);
        $request->setUserResolver(static fn (): User => $user);
        Auth::setUser($user);

        return $next($request);
    }

    private function requiresRevalidation(User $user): bool
    {
        if (! $user->hasDirectoryIdentity()
            || $this->authorizationContext->authenticationMethod(request())
                !== SessionAuthorizationContext::METHOD_CLOUDRON_OIDC) {
            return false;
        }

        if (app()->environment(['local', 'testing'])) {
            return (bool) config('myapes.directory.revalidate_in_local', false);
        }

        return true;
    }

    private function revoke(Request $request, User $user, string $reason): Response
    {
        $result = $this->roles->revoke($user);

        if ($user->suspended_at === null) {
            $user->forceFill([
                'suspended_at' => now(),
                'suspended_by' => null,
                'suspension_reason' => DirectoryUserSynchronizer::SUSPENSION_REASON_DIRECTORY_DISABLED,
                'authorization_epoch' => (int) $user->authorization_epoch + 1,
            ]);
            $user->setRememberToken(Str::random(60));
            $user->save();
        }

        return $this->logoutAfterRevocation(
            $request,
            $user,
            $result->previousProtectedRole,
            $reason,
        );
    }

    private function logoutAfterRevocation(
        Request $request,
        User $user,
        ?string $previousRole,
        string $reason,
    ): Response {
        $this->auditLogger->record('auth.directory_access_revoked', $user, $user, [
            'from_role' => $previousRole,
            'reason' => $reason,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()
            ->route('staff.login')
            ->withErrors(['staff' => 'Your Cloudron account no longer has MyAPES staff access.']);
    }
}
