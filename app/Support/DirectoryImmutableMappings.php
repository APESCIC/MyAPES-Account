<?php

namespace App\Support;

use App\Services\AuthorizationProfile;

final class DirectoryImmutableMappings
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'myapesaccount.staff' => AuthorizationProfile::ROLE_STAFF,
            'myapesaccount.admin' => AuthorizationProfile::ROLE_ADMINISTRATOR,
            'myapesaccount.superadmin' => AuthorizationProfile::ROLE_SUPER_ADMIN,
            'myapesaccount.volunteer' => AuthorizationProfile::ROLE_VOLUNTEER,
            'myapesaccount.student' => AuthorizationProfile::ROLE_STUDENT,
        ];
    }
}
