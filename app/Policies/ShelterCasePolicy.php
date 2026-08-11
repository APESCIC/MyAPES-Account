<?php

namespace App\Policies;

use App\Models\ShelterCase;
use App\Models\User;
use App\Services\ModuleState;

class ShelterCasePolicy
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

    public function view(User $user, ShelterCase $case): bool
    {
        $prefix = "{$case->sub_core_key}.cases.";

        return $this->modules->enabled($case->sub_core_key, 'cases')
            && ($user->can($prefix.'view-all')
            || ($case->user_id === $user->id
                && $user->can($prefix.'view-own')));
    }

    public function update(User $user, ShelterCase $case): bool
    {
        $prefix = "{$case->sub_core_key}.cases.";

        return $this->view($user, $case)
            && ($user->can($prefix.'update-all')
                || ($case->user_id === $user->id
                    && $user->can($prefix.'update-own')));
    }

    public function delete(User $user, ShelterCase $case): bool
    {
        return $this->view($user, $case)
            && $user->can("{$case->sub_core_key}.cases.delete");
    }
}
