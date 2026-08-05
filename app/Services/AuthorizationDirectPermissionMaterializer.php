<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuthorizationDirectPermissionMaterializer
{
    public function __construct(
        private readonly AuthorizationDirectPermissionPolicy $policy,
    ) {}

    public function grant(
        User $user,
        Permission $permission,
        string $source,
        ?User $actor = null,
    ): PermissionSource {
        $this->assertSource($source, $actor);
        $this->policy->assertAssignable($permission);

        return DB::transaction(function () use (
            $user,
            $permission,
            $source,
            $actor,
        ): PermissionSource {
            $this->lockUser($user);

            $permissionSource = PermissionSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($permission)
                ->whereNull('team_id')
                ->where('source_key', $source)
                ->first() ?? new PermissionSource;
            $permissionSource->forceFill([
                'user_id' => $user->getKey(),
                'permission_id' => $permission->getKey(),
                'team_id' => null,
                'source' => $source,
                'source_key' => $source,
                'granted_by' => $actor?->getKey(),
            ])->save();

            DB::table(
                config('permission.table_names.model_has_permissions'),
            )->insertOrIgnore([
                'permission_id' => $permission->getKey(),
                'team_id' => null,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
            ]);
            $user->unsetRelation('permissions');
            $user->forgetWildcardPermissionIndex();

            return $permissionSource;
        });
    }

    public function revoke(
        User $user,
        Permission $permission,
        string $source,
    ): void {
        $this->assertSource($source, forGrant: false);
        $this->policy->assertAssignable($permission);

        DB::transaction(function () use ($user, $permission, $source): void {
            $this->lockUser($user);

            PermissionSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($permission)
                ->whereNull('team_id')
                ->where('source_key', $source)
                ->delete();

            $hasRemainingSource = PermissionSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($permission)
                ->whereNull('team_id')
                ->exists();
            $pivot = DB::table(
                config('permission.table_names.model_has_permissions'),
            )
                ->where('permission_id', $permission->getKey())
                ->whereNull('team_id')
                ->where('model_type', $user->getMorphClass())
                ->where('model_id', $user->getKey());

            if ($hasRemainingSource) {
                $pivot->insertOrIgnore([
                    'permission_id' => $permission->getKey(),
                    'team_id' => null,
                    'model_type' => $user->getMorphClass(),
                    'model_id' => $user->getKey(),
                ]);
            } else {
                $pivot->delete();
            }

            $user->unsetRelation('permissions');
            $user->forgetWildcardPermissionIndex();
        });
    }

    private function assertSource(
        string $source,
        ?User $actor = null,
        bool $forGrant = true,
    ): void {
        if (! in_array($source, PermissionSource::sources(), true)) {
            throw new InvalidArgumentException(
                "Unsupported direct-permission source [{$source}].",
            );
        }

        if ($forGrant
            && $source === PermissionSource::SOURCE_LOCAL
            && ($actor === null || ! $actor->exists)) {
            throw new InvalidArgumentException(
                'Local direct permissions require a persisted actor.',
            );
        }

        if ($forGrant
            && $source === PermissionSource::SOURCE_SYSTEM
            && $actor !== null) {
            throw new InvalidArgumentException(
                'System direct permissions cannot reference an actor.',
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
