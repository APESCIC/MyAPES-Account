<?php

namespace App\Services;

use App\Auth\DirectoryUserProfile;
use App\Models\StaffProfile;
use App\Models\User;

final class StaffProfileDirectorySynchronizer
{
    public function apply(User $user, DirectoryUserProfile $profile): StaffProfile
    {
        $staffProfile = $user->staffProfile()->firstOrCreate([]);
        $attributes = [];

        if ($profile->jobTitle !== null) {
            $attributes['job_title'] = $profile->jobTitle;
        }

        if ($profile->workPhone !== null) {
            $attributes['work_phone'] = $profile->workPhone;
        }

        if ($attributes !== []) {
            $staffProfile->forceFill($attributes)->save();
        }

        return $staffProfile->refresh();
    }
}
