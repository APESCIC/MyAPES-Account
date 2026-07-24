<?php

namespace App\Services;

use App\Models\User;

class RoleMapper
{
    /**
     * @param  array<int, string>  $groups
     */
    public function map(array $groups): ?string
    {
        $normalized = array_map(
            static fn (string $group): string => strtolower(trim($group)),
            $groups
        );

        $superadminGroups = config('myapes.roles.superadmin_groups', []);
        $adminGroups = config('myapes.roles.admin_groups', []);
        $staffGroups = config('myapes.roles.staff_groups', []);

        if ($this->containsAny($normalized, $superadminGroups)) {
            return User::ROLE_SUPERADMIN;
        }

        if ($this->containsAny($normalized, $adminGroups)) {
            return User::ROLE_ADMIN;
        }

        if ($this->containsAny($normalized, $staffGroups)) {
            return User::ROLE_STAFF;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $needle
     * @param  array<int, string>  $haystack
     */
    private function containsAny(array $needle, array $haystack): bool
    {
        $normalizedHaystack = array_map(
            static fn (string $group): string => strtolower(trim($group)),
            $haystack
        );

        foreach ($needle as $group) {
            if (in_array($group, $normalizedHaystack, true)) {
                return true;
            }
        }

        return false;
    }
}
