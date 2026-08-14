<?php

namespace App\Policies;

use App\Models\PetProfile;
use App\Models\User;
use App\Services\AuthorizationProfile;

class PetProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, PetProfile $pet): bool
    {
        if ($pet->service_domain === PetProfile::DOMAIN_SHELTER) {
            return $user->can('shelter-rescue.pet-profiles.view-all')
                || ($pet->user_id === $user->id
                    && $user->can('shelter-rescue.pet-profiles.view-own'));
        }

        return $this->hasStaffPermission($user)
            || $pet->user_id === $user->id;
    }

    public function update(User $user, PetProfile $pet): bool
    {
        if ($pet->service_domain === PetProfile::DOMAIN_SHELTER) {
            return $user->can('shelter-rescue.pet-profiles.update-all')
                || ($pet->user_id === $user->id
                    && $user->can('shelter-rescue.pet-profiles.update-own'));
        }

        return $this->view($user, $pet);
    }

    public function createShelterCase(User $user, PetProfile $pet): bool
    {
        return $pet->service_domain === PetProfile::DOMAIN_SHELTER
            && $this->view($user, $pet);
    }

    public function createConsultation(User $user, PetProfile $pet): bool
    {
        return $pet->service_domain === PetProfile::DOMAIN_PETCARE
            && $this->view($user, $pet);
    }

    private function hasStaffPermission(User $user): bool
    {
        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS);
    }
}
