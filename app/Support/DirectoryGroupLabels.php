<?php

namespace App\Support;

use App\Models\DirectoryGroup;

/**
 * Labels Cloudron directory catalogue entries for admin UI clarity.
 * Groups are always directory-sourced; “custom” applies to mappings, not group membership.
 */
final class DirectoryGroupLabels
{
    public static function sourceLabel(DirectoryGroup|string $group): string
    {
        $name = $group instanceof DirectoryGroup ? $group->name : $group;

        if (self::isMyApesCloudronGroup($name)) {
            return 'Cloudron MyAPES';
        }

        return 'Cloudron directory';
    }

    public static function sourceHint(DirectoryGroup|string $group): string
    {
        $name = $group instanceof DirectoryGroup ? $group->name : $group;

        if (self::isMyApesCloudronGroup($name)) {
            return 'Required or recognized MyAPES Cloudron group.';
        }

        return 'Other group from the Cloudron directory catalogue. Not an app-owned custom group.';
    }

    public static function isMyApesCloudronGroup(string $name): bool
    {
        $required = config('myapes.directory.required_groups', []);

        if (in_array($name, $required, true)) {
            return true;
        }

        return str_starts_with(strtolower($name), 'myapes.');
    }

    public static function mappingLabel(bool $immutable): string
    {
        return $immutable ? 'Protected Cloudron mapping' : 'Custom mapping';
    }
}
