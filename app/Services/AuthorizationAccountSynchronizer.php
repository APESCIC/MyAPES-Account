<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuthorizationAccountSynchronizer
{
    public function __construct(
        private readonly AuthorizationProfile $profile,
        private readonly AuthorizationRoleMaterializer $materializer,
        private readonly LegacyAccessCompatibilityAdapter $legacy,
    ) {}

    public function grantPublicBaseline(User $user): void
    {
        DB::transaction(function () use ($user): void {
            DB::table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->lockForUpdate()
                ->first();

            $protectedSystemRoles = Role::query()
                ->where('guard_name', 'web')
                ->where('is_protected', true)
                ->whereIn(
                    'id',
                    RoleSource::query()
                        ->select('role_id')
                        ->where('user_id', $user->getKey())
                        ->where('source', RoleSource::SOURCE_SYSTEM),
                )
                ->orderBy('name')
                ->get();
            $roleName = $this->profile->protectedRoleForLegacy('service_user');
            $qaRoleName = $this->profile->protectedRoleForLegacy(
                $this->legacy->read($user),
            );
            $systemRoleNames = $protectedSystemRoles
                ->pluck('name')
                ->unique()
                ->values()
                ->all();

            if ($user->identity_type === User::IDENTITY_LOCAL
                && app()->environment(['local', 'testing'])
                && $qaRoleName !== null
                && $systemRoleNames === [$qaRoleName]) {
                $roleName = $qaRoleName;
            }

            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->first();

            if ($role === null) {
                throw new RuntimeException(
                    'Public authorization baseline is unavailable.',
                );
            }

            foreach ($protectedSystemRoles as $protectedSystemRole) {
                if ($protectedSystemRole->is($role)) {
                    continue;
                }

                $this->materializer->revoke(
                    $user,
                    $protectedSystemRole,
                    RoleSource::SOURCE_SYSTEM,
                );
            }

            $this->legacy->write(
                $user,
                $this->profile->legacyAccessLevelFor($roleName),
            )->save();
            $this->materializer->grant(
                $user,
                $role,
                RoleSource::SOURCE_SYSTEM,
            );
            $user->refresh();
        });
    }
}
