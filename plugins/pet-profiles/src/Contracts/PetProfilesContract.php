<?php

namespace Plugins\PetProfiles\Contracts;

use App\Core\Accounts\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Plugins\PetProfiles\Models\PetProfile;

/**
 * Public query API for plugins that depend on Pet Profiles (#291).
 */
interface PetProfilesContract
{
    public function domainForModule(string $moduleSlug): string;

    /**
     * @return Builder<PetProfile>
     */
    public function visibleQuery(User $user, string $serviceDomain): Builder;

    /**
     * @return Collection<int, PetProfile>
     */
    public function visibleOrdered(User $user, string $serviceDomain): Collection;

    public function findVisibleOrFail(User $user, string $serviceDomain, int $id): PetProfile;
}
