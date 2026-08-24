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
}
