<?php

namespace App\Support;

final class DirectoryLegacyGroupAliases
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            'position.staff',
            'position.students',
            'position.volunteers',
            'intranet.administrator',
            'intranet.superadmin',
            'myapes.staff',
            'myapes.admin',
            'myapes.admins',
            'myapes.superadmin',
            'myapes.superadmins',
            'myapes.students',
            'myapes.volunteers',
            'myapes.vounteers',
        ];
    }

    public static function canonicalFor(string $group): ?string
    {
        $normalized = strtolower(trim($group));

        if ($normalized === '') {
            return null;
        }

        if (DirectoryGroupPrefix::isManagedGroup($normalized)) {
            return $normalized;
        }

        return match ($normalized) {
            'myapes.staff' => 'myapesaccount.staff',
            'myapes.admin', 'myapes.admins' => 'myapesaccount.admin',
            'myapes.superadmin', 'myapes.superadmins' => 'myapesaccount.superadmin',
            'myapes.volunteers', 'myapes.vounteers' => 'myapesaccount.volunteer',
            'myapes.students' => 'myapesaccount.student',
            default => null,
        };
    }
}
