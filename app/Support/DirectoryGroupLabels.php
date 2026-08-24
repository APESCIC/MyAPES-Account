<?php

namespace App\Support;

use App\Models\DirectoryGroup;

/**
 * Labels Cloudron directory catalogue entries for admin UI clarity.
 */
final class DirectoryGroupLabels
{
    public static function sourceLabel(DirectoryGroup|string $group): string
    {
        $name = $group instanceof DirectoryGroup ? $group->name : $group;

        if (self::isMyApesCloudronGroup($name)) {
            return 'Cloudron MyAPES Account';
        }

        return 'Cloudron directory';
    }

    public static function sourceHint(DirectoryGroup|string $group): string
    {
        $name = $group instanceof DirectoryGroup ? $group->name : $group;

        if (self::isMyApesCloudronGroup($name)) {
            return 'Preset MyAPES Account Cloudron group.';
        }

        return 'Other group from the Cloudron directory catalogue.';
    }

    public static function isMyApesCloudronGroup(string $name): bool
    {
        if (in_array(strtolower(trim($name)), DirectoryGroupPrefix::requiredGroups(), true)) {
            return true;
        }

        return DirectoryGroupPrefix::isManagedGroup($name);
    }

    public static function mappingLabel(bool $immutable): string
    {
        return $immutable ? 'Preset Cloudron mapping' : 'Custom mapping';
    }
}
