<?php

namespace App\Policies;

use App\Models\ShelterCase;
use App\Models\User;
use App\Services\AuthorizationProfile;

class ShelterCasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ShelterCase $case): bool
    {
        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)
            || $case->user_id === $user->id;
    }

    public function update(User $user, ShelterCase $case): bool
    {
        return $this->view($user, $case);
    }
}
