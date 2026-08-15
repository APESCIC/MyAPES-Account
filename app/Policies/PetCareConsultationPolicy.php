<?php

namespace App\Policies;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\User;
use App\Services\ModuleState;

class PetCareConsultationPolicy
{
    public function __construct(
        private readonly ModuleState $modules,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->enabled()
            && ($user->can(PetCareConsultation::PERMISSION_VIEW_OWN)
                || $user->can(PetCareConsultation::PERMISSION_VIEW_ALL));
    }

    public function create(User $user): bool
    {
        return $this->enabled()
            && $user->can(PetCareConsultation::PERMISSION_CREATE);
    }

    public function view(
        User $user,
        PetCareConsultation $consultation,
    ): bool {
        return $this->isPetCare($consultation)
            && $this->enabled()
            && ($user->can(PetCareConsultation::PERMISSION_VIEW_ALL)
                || ($consultation->user_id === $user->id
                    && $user->can(PetCareConsultation::PERMISSION_VIEW_OWN)));
    }

    public function update(
        User $user,
        PetCareConsultation $consultation,
    ): bool {
        return $this->view($user, $consultation)
            && ($user->can(PetCareConsultation::PERMISSION_UPDATE_ALL)
                || ($consultation->user_id === $user->id
                    && $user->can(PetCareConsultation::PERMISSION_UPDATE_OWN)));
    }

    public function assign(User $user, PetCareConsultation $consultation): bool
    {
        return $this->view($user, $consultation)
            && $user->can(PetCareConsultation::PERMISSION_ASSIGN);
    }

    public function close(User $user, PetCareConsultation $consultation): bool
    {
        return $this->view($user, $consultation)
            && $user->can(PetCareConsultation::PERMISSION_CLOSE);
    }

    private function enabled(): bool
    {
        return $this->modules->enabled('pet-care-clinic', 'consultations');
    }

    private function isPetCare(PetCareConsultation $consultation): bool
    {
        return $consultation->petProfile?->service_domain
            === PetProfile::DOMAIN_PETCARE;
    }
}
