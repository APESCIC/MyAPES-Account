<?php

namespace App\Policies;

use App\Models\PetCareConsultation;
use App\Models\User;
use App\Services\AuthorizationProfile;

class PetCareConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(
        User $user,
        PetCareConsultation $consultation,
    ): bool {
        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)
            || $consultation->user_id === $user->id;
    }

    public function update(
        User $user,
        PetCareConsultation $consultation,
    ): bool {
        return $this->view($user, $consultation);
    }
}
