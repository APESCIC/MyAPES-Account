<?php

namespace App\Policies;

use App\Models\RecruitmentRole;
use App\Models\User;
use App\Services\ModuleState;

class RecruitmentRolePolicy
{
    public function __construct(
        private readonly ModuleState $modules,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->moduleEnabled()
            && ($user->can(RecruitmentRole::PERMISSION_PREFIX.'view-own')
                || $user->can(RecruitmentRole::PERMISSION_PREFIX.'view-all'));
    }

    public function view(User $user, RecruitmentRole $recruitmentRole): bool
    {
        if (! $this->moduleEnabled()) {
            return false;
        }

        if ($user->can(RecruitmentRole::PERMISSION_PREFIX.'view-all')) {
            return true;
        }

        return $user->can(RecruitmentRole::PERMISSION_PREFIX.'view-own')
            && $recruitmentRole->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->moduleEnabled()
            && $user->can(RecruitmentRole::PERMISSION_PREFIX.'create');
    }

    public function update(User $user, RecruitmentRole $recruitmentRole): bool
    {
        return $this->moduleEnabled()
            && $user->can(RecruitmentRole::PERMISSION_PREFIX.'update');
    }

    public function delete(User $user, RecruitmentRole $recruitmentRole): bool
    {
        return $this->moduleEnabled()
            && $user->can(RecruitmentRole::PERMISSION_PREFIX.'delete');
    }

    public function publish(User $user, RecruitmentRole $recruitmentRole): bool
    {
        return $this->update($user, $recruitmentRole)
            && $recruitmentRole->status !== RecruitmentRole::STATUS_OPEN;
    }

    public function close(User $user, RecruitmentRole $recruitmentRole): bool
    {
        return $this->update($user, $recruitmentRole)
            && $recruitmentRole->isOpen();
    }

    private function moduleEnabled(): bool
    {
        return $this->modules->enabled('apes-cic', 'recruitment');
    }
}
