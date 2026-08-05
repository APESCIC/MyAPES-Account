<?php

namespace App\Services;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DirectoryGroupMappingService
{
    private const LEGACY_ALIASES = [
        'position.staff',
        'position.students',
        'position.volunteers',
        'intranet.administrator',
        'intranet.superadmin',
    ];

    public function __construct(
        private readonly PrivilegedMutationAuthorizer $authorizer,
        private readonly DirectoryRoleSynchronizer $roleSynchronizer,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function map(
        User $actor,
        DirectoryGroup $group,
        Role $role,
    ): DirectoryGroupRoleMapping {
        try {
            return DB::transaction(function () use (
                $actor,
                $group,
                $role,
            ): DirectoryGroupRoleMapping {
                [$lockedActor, $lockedUsers] = $this->authorizer->lock($actor);
                $this->authorize($lockedActor);
                $storedGroup = DirectoryGroup::query()
                    ->whereKey($group->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $storedRole = Role::query()
                    ->whereKey($role->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->assertExactGroup($storedGroup);

                if ($storedRole->guard_name !== 'web') {
                    $this->deny(
                        'invalid_mapping_role',
                        'Directory mappings require a persisted web role.',
                    );
                }

                $mapping = DirectoryGroupRoleMapping::query()
                    ->whereBelongsTo($storedGroup)
                    ->whereBelongsTo($storedRole)
                    ->lockForUpdate()
                    ->first();
                $action = 'existing';

                if ($mapping === null) {
                    $mapping = new DirectoryGroupRoleMapping;
                    $mapping->forceFill([
                        'directory_group_id' => $storedGroup->getKey(),
                        'role_id' => $storedRole->getKey(),
                        'is_immutable' => false,
                    ])->save();
                    $action = 'created';
                }

                [$matchedCount, $changedCount] = $this->resynchronizeUsers(
                    $storedGroup->name,
                    $lockedUsers,
                );
                $this->assertAnActiveSuperAdminRemains();
                $this->auditLogger->record(
                    'authorization.directory_mapping_changed',
                    $lockedActor,
                    $mapping,
                    [
                        'action' => $action,
                        'group_id' => $storedGroup->id,
                        'role_id' => $storedRole->id,
                        'mapping_id' => $mapping->id,
                        'matched_user_count' => $matchedCount,
                        'changed_user_count' => $changedCount,
                    ],
                );

                return $mapping;
            });
        } catch (AuthorizationMutationDenied $exception) {
            $this->auditDenied(
                $actor,
                'mapping_create',
                $exception->reasonCode,
                $group,
                $role,
            );

            throw $exception;
        } catch (AuthorizationException $exception) {
            $this->auditDenied(
                $actor,
                'mapping_create',
                'super_admin_required',
                $group,
                $role,
            );

            throw $exception;
        }
    }

    public function remove(
        User $actor,
        DirectoryGroupRoleMapping $mapping,
    ): void {
        try {
            DB::transaction(function () use ($actor, $mapping): void {
                [$lockedActor, $lockedUsers] = $this->authorizer->lock($actor);
                $this->authorize($lockedActor);
                $stored = DirectoryGroupRoleMapping::query()
                    ->with(['directoryGroup', 'role'])
                    ->whereKey($mapping->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($stored === null) {
                    return;
                }

                if ($stored->is_immutable) {
                    $this->deny(
                        'immutable_mapping',
                        'Immutable directory mappings cannot be removed.',
                    );
                }

                $group = $stored->directoryGroup;
                $role = $stored->getRelation('role');
                $mappingId = (int) $stored->id;
                $stored->delete();
                [$matchedCount, $changedCount] = $this->resynchronizeUsers(
                    $group->name,
                    $lockedUsers,
                );
                $this->assertAnActiveSuperAdminRemains();
                $this->auditLogger->record(
                    'authorization.directory_mapping_changed',
                    $lockedActor,
                    null,
                    [
                        'action' => 'removed',
                        'group_id' => $group->id,
                        'role_id' => $role->id,
                        'mapping_id' => $mappingId,
                        'matched_user_count' => $matchedCount,
                        'changed_user_count' => $changedCount,
                    ],
                );
            });
        } catch (AuthorizationMutationDenied $exception) {
            $this->auditDenied(
                $actor,
                'mapping_remove',
                $exception->reasonCode,
                mapping: $mapping,
            );

            throw $exception;
        } catch (AuthorizationException $exception) {
            $this->auditDenied(
                $actor,
                'mapping_remove',
                'super_admin_required',
                mapping: $mapping,
            );

            throw $exception;
        }
    }

    private function authorize(User $actor): void
    {
        if (! $this->authorizer->authorizes(
            $actor,
            'admin.group-mappings.manage',
        )) {
            throw new AuthorizationException(
                'Super-admin authorization is required.',
            );
        }
    }

    private function assertExactGroup(DirectoryGroup $group): void
    {
        $name = $group->name;

        if (! $group->exists
            || ! is_string($name)
            || $name === ''
            || $name !== strtolower(trim($name))
            || preg_match('/[*?%]/', $name) === 1
            || in_array($name, self::LEGACY_ALIASES, true)) {
            $this->deny(
                'invalid_directory_group',
                'Directory group is not eligible for exact mapping.',
            );
        }
    }

    /**
     * @return array{int, int}
     */
    private function resynchronizeUsers(
        string $groupName,
        Collection $lockedUsers,
    ): array {
        $users = $lockedUsers
            ->filter(static function (User $user) use ($groupName): bool {
                $groups = is_array($user->ldap_groups)
                    ? $user->ldap_groups
                    : [];
                $normalized = array_map(
                    static fn (mixed $group): string => is_string($group)
                        ? strtolower(trim($group))
                        : '',
                    $groups,
                );

                return in_array($groupName, $normalized, true);
            });
        $changed = 0;

        foreach ($users as $user) {
            $result = $this->roleSynchronizer->synchronize(
                $user,
                $user->ldap_groups,
            );

            if ($result->authorizationChanged) {
                $changed++;
            }
        }

        return [$users->count(), $changed];
    }

    private function assertAnActiveSuperAdminRemains(): void
    {
        $this->authorizer->assertEligibleSuperAdminRemains();
    }

    private function deny(string $reasonCode, string $message): never
    {
        throw new AuthorizationMutationDenied($reasonCode, $message);
    }

    private function auditDenied(
        User $actor,
        string $action,
        string $reasonCode,
        ?DirectoryGroup $group = null,
        ?Role $role = null,
        ?DirectoryGroupRoleMapping $mapping = null,
    ): void {
        $context = [
            'action' => $action,
            'reason_code' => $reasonCode,
        ];

        if ($mapping?->directory_group_id !== null) {
            $context['group_id'] = (int) $mapping->directory_group_id;
        }

        if ($mapping?->role_id !== null) {
            $context['role_id'] = (int) $mapping->role_id;
        }

        if ($group?->getKey() !== null) {
            $context['group_id'] = (int) $group->getKey();
        }

        if ($role?->getKey() !== null) {
            $context['role_id'] = (int) $role->getKey();
        }

        if ($mapping?->getKey() !== null) {
            $context['mapping_id'] = (int) $mapping->getKey();
        }

        $this->auditLogger->record(
            'authorization.privileged_mutation_denied',
            $actor,
            null,
            $context,
        );
    }
}
