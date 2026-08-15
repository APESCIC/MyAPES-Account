<?php

namespace App\Policies;

use App\Models\PetProfile;
use App\Models\User;
use App\Services\ModuleState;

class PetProfilePolicy
{
    public function __construct(
        private readonly ModuleState $modules,
    ) {}

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

        if ($pet->service_domain === PetProfile::DOMAIN_PETCARE) {
            return $this->modules->enabled('pet-care-clinic', 'pet-profiles')
                && ($user->can('pet-care-clinic.pet-profiles.view-all')
                    || ($pet->user_id === $user->id
                        && $user->can('pet-care-clinic.pet-profiles.view-own')));
        }

        return false;
    }

    public function update(User $user, PetProfile $pet): bool
    {
        if ($pet->service_domain === PetProfile::DOMAIN_SHELTER) {
            return $user->can('shelter-rescue.pet-profiles.update-all')
                || ($pet->user_id === $user->id
                    && $user->can('shelter-rescue.pet-profiles.update-own'));
        }

        if ($pet->service_domain === PetProfile::DOMAIN_PETCARE) {
            return $this->modules->enabled('pet-care-clinic', 'pet-profiles')
                && (($user->can('pet-care-clinic.pet-profiles.view-all')
                        && $user->can('pet-care-clinic.pet-profiles.update-all'))
                    || ($pet->user_id === $user->id
                        && $user->can('pet-care-clinic.pet-profiles.view-own')
                        && $user->can('pet-care-clinic.pet-profiles.update-own')));
        }

        return false;
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
}
