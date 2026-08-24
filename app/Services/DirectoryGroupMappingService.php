<?php

namespace App\Services;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\User;
use App\Support\DirectoryGroupPrefix;
use App\Support\DirectoryLegacyGroupAliases;

/**
 * Preset Cloudron groups are immutable; custom mapping mutations are disabled.
 */
class DirectoryGroupMappingService
{
    public function map(
        User $actor,
        DirectoryGroup $group,
        Role $role,
    ): DirectoryGroupRoleMapping {
        $this->denyPresetGroupsOnly();

        throw new AuthorizationMutationDenied(
            'preset_groups_only',
            'Directory group mappings are preset and cannot be changed.',
        );
    }

    public function remove(
        User $actor,
        DirectoryGroupRoleMapping $mapping,
    ): void {
        $this->denyPresetGroupsOnly();
    }

    public function setAppEnabled(
        User $actor,
        DirectoryGroup $group,
        bool $enabled,
    ): DirectoryGroup {
        $this->denyPresetGroupsOnly();

        throw new AuthorizationMutationDenied(
            'preset_groups_only',
            'Preset Cloudron groups are always enabled for this app.',
        );
    }

    public function assertManagedGroup(DirectoryGroup $group): void
    {
        $name = $group->name;

        if (! $group->exists
            || ! is_string($name)
            || $name === ''
            || $name !== strtolower(trim($name))
            || preg_match('/[*?%]/', $name) === 1
            || in_array($name, DirectoryLegacyGroupAliases::all(), true)
            || ! DirectoryGroupPrefix::isManagedGroup($name)) {
            throw new AuthorizationMutationDenied(
                'invalid_directory_group',
                'Directory group is not eligible for mapping.',
            );
        }
    }

    private function denyPresetGroupsOnly(): never
    {
        throw new AuthorizationMutationDenied(
            'preset_groups_only',
            'Directory group mappings are preset and cannot be changed.',
        );
    }
}
