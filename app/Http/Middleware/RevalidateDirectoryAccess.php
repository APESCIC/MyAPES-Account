<?php

namespace App\Http\Middleware;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LdapGroupResolver;
use App\Services\RoleMapper;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RevalidateDirectoryAccess
{
    public const SESSION_KEY = 'myapes.directory_validated_at';

    public function __construct(
        private readonly LdapGroupResolver $directory,
        private readonly RoleMapper $roles,
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

        $interval = max(1, (int) config('myapes.directory.revalidate_seconds', 300));
        $validatedAt = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($validatedAt > 0 && now()->timestamp - $validatedAt < $interval) {
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

        $role = $this->roles->map($groups);

        if ($role === null) {
            return $this->revoke($request, $user, 'no_approved_group');
        }

        $normalizedGroups = $this->normalizedGroups($groups);
        $storedGroups = $this->normalizedGroups($user->ldap_groups ?? []);
        $previousRole = $user->role;

        if ($role !== $previousRole || $normalizedGroups !== $storedGroups) {
            $user->forceFill([
                'role' => $role,
                'ldap_groups' => $normalizedGroups,
            ])->save();

            $this->auditLogger->record('auth.directory_role_changed', $user, $user, [
                'from_role' => $previousRole,
                'to_role' => $role,
                'group_count' => count($normalizedGroups),
            ]);
        }

        $request->session()->put(self::SESSION_KEY, now()->timestamp);
        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }

    private function requiresRevalidation(User $user): bool
    {
        if (! is_string($user->oidc_sub) || trim($user->oidc_sub) === '') {
            return false;
        }

        if (app()->environment(['local', 'testing'])) {
            return (bool) config('myapes.directory.revalidate_in_local', false);
        }

        return true;
    }

    private function revoke(Request $request, User $user, string $reason): RedirectResponse
    {
        $previousRole = $user->role;
        $user->forceFill([
            'role' => User::ROLE_SERVICE_USER,
            'ldap_groups' => [],
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->auditLogger->record('auth.directory_access_revoked', $user, $user, [
            'from_role' => $previousRole,
            'reason' => $reason,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('staff.login')
            ->withErrors(['staff' => 'Your Cloudron account no longer has MyAPES staff access.']);
    }

    /**
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    private function normalizedGroups(array $groups): array
    {
        $normalized = array_values(array_unique(array_map(
            static fn (string $group): string => strtolower(trim($group)),
            $groups,
        )));
        sort($normalized);

        return $normalized;
    }
}
