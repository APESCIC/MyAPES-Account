<?php

namespace App\Services;

use App\Models\DirectoryGroup;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuthorizationRoleMaterializer
{
    public function grant(
        User $user,
        Role $role,
        string $source,
        ?DirectoryGroup $directoryGroup = null,
        ?User $actor = null,
    ): RoleSource {
        $sourceKey = $this->sourceKey($source, $directoryGroup);
        $this->assertWebRole($role);
        $this->assertAllowedProvenance($role, $source);
        $this->assertSourceActor($source, $actor);

        return DB::transaction(function () use (
            $user,
            $role,
            $source,
            $sourceKey,
            $directoryGroup,
            $actor,
        ): RoleSource {
            $this->lockUser($user);

            $roleSource = RoleSource::query()
                ->where('user_id', $user->getKey())
                ->where('role_id', $role->getKey())
                ->where('source_key', $sourceKey)
                ->first() ?? new RoleSource;
            $roleSource->forceFill([
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
                'source_key' => $sourceKey,
                'source' => $source,
                'directory_group_id' => $directoryGroup?->getKey(),
                'granted_by' => $actor?->getKey(),
            ])->save();

            DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
                'role_id' => $role->getKey(),
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
            ]);
            $user->unsetRelation('roles');

            return $roleSource;
        });
    }

    public function revoke(
        User $user,
        Role $role,
        string $source,
        ?DirectoryGroup $directoryGroup = null,
    ): void {
        $sourceKey = $this->sourceKey($source, $directoryGroup);
        $this->assertWebRole($role);
        $this->assertAllowedProvenance($role, $source);

        DB::transaction(function () use ($user, $role, $sourceKey): void {
            $this->lockUser($user);

            RoleSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($role)
                ->where('source_key', $sourceKey)
                ->delete();

            $hasRemainingSource = RoleSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($role)
                ->exists();

            if (! $hasRemainingSource) {
                DB::table(config('permission.table_names.model_has_roles'))
                    ->where('role_id', $role->getKey())
                    ->where('model_type', $user->getMorphClass())
                    ->where('model_id', $user->getKey())
                    ->delete();
            } else {
                DB::table(config('permission.table_names.model_has_roles'))
                    ->insertOrIgnore([
                        'role_id' => $role->getKey(),
                        'model_type' => $user->getMorphClass(),
                        'model_id' => $user->getKey(),
                    ]);
            }

            $user->unsetRelation('roles');
        });
    }

    private function sourceKey(
        string $source,
        ?DirectoryGroup $directoryGroup,
    ): string {
        if (! in_array($source, RoleSource::sources(), true)) {
            throw new InvalidArgumentException("Unsupported role source [{$source}].");
        }

        if ($source === RoleSource::SOURCE_DIRECTORY) {
            if ($directoryGroup === null || ! $directoryGroup->exists) {
                throw new InvalidArgumentException(
                    'Directory role sources require a persisted directory group.',
                );
            }

            return $source.':'.$directoryGroup->getKey();
        }

        if ($directoryGroup !== null) {
            throw new InvalidArgumentException(
                'Only directory role sources may reference a directory group.',
            );
        }

        return $source;
    }

    private function assertWebRole(Role $role): void
    {
        if (! $role->exists || $role->guard_name !== 'web') {
            throw new InvalidArgumentException(
                'Authorization roles must be persisted for the web guard.',
            );
        }
    }

    private function assertAllowedProvenance(Role $role, string $source): void
    {
        if ($source === RoleSource::SOURCE_LOCAL && $role->is_protected) {
            throw new InvalidArgumentException(
                'Protected roles cannot have local provenance.',
            );
        }

        if ($source === RoleSource::SOURCE_LEGACY_COMPATIBILITY
            && ! $role->is_protected) {
            throw new InvalidArgumentException(
                'Legacy compatibility provenance is restricted to protected roles.',
            );
        }
    }

    private function assertSourceActor(string $source, ?User $actor): void
    {
        if ($source === RoleSource::SOURCE_LOCAL
            && ($actor === null || ! $actor->exists)) {
            throw new InvalidArgumentException(
                'Local role sources require a persisted actor.',
            );
        }

        if ($source !== RoleSource::SOURCE_LOCAL && $actor !== null) {
            throw new InvalidArgumentException(
                'Non-local role sources cannot reference an actor.',
            );
        }
    }

    private function lockUser(User $user): void
    {
        DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->lockForUpdate()
            ->first();
    }
}
