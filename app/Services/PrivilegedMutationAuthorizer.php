<?php

namespace App\Services;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\AuthorizationState;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PrivilegedMutationAuthorizer
{
    private ?int $localQaActorId = null;

    public function __construct(
        private readonly ApplicationAuthorizationGate $gate,
        private readonly AuthorizationProfile $profile,
    ) {}

    /**
     * Establish the global authorization mutation lock before any user,
     * directory, role, mapping, provenance, or pivot locks are acquired.
     *
     * @return array{User, Collection<int, User>}
     */
    public function lock(User $actor): array
    {
        $state = AuthorizationState::query()
            ->whereKey(AuthorizationState::SINGLETON_ID)
            ->lockForUpdate()
            ->first();

        if ($state === null) {
            throw new AuthorizationMutationDenied(
                'authorization_state_unavailable',
                'Authorization state is unavailable.',
            );
        }

        $users = User::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $lockedActor = $users->first(
            static fn (User $user): bool => (int) $user->getKey()
                === (int) $actor->getKey(),
        );

        if (! $lockedActor instanceof User) {
            throw (new ModelNotFoundException)->setModel(
                User::class,
                [$actor->getKey()],
            );
        }

        return [$lockedActor, $users];
    }

    public function authorizes(User $actor, string $ability): bool
    {
        if ($this->localQaActorId === (int) $actor->getKey()) {
            if (! app()->environment(['local', 'testing'])) {
                return false;
            }

            $protectedRole = $this->profile->effectiveProtectedRole($actor);

            return is_string($protectedRole)
                && $this->isEligibleForProtectedRoles(
                    $actor,
                    [$protectedRole],
                )
                && in_array(
                    $ability,
                    $this->profile->permissionMatrix()[$protectedRole] ?? [],
                    true,
                );
        }

        return $this->gate->authorize($actor, $ability) === true;
    }

    public function runAsLocalQa(User $actor, callable $operation): mixed
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new AuthorizationMutationDenied(
                'local_qa_unavailable',
                'Local QA authorization is unavailable.',
            );
        }

        $previousActorId = $this->localQaActorId;
        $this->localQaActorId = (int) $actor->getKey();

        try {
            return $operation();
        } finally {
            $this->localQaActorId = $previousActorId;
        }
    }

    public function isEligibleSuperAdmin(User $user): bool
    {
        return $this->isEligibleForProtectedRoles($user, [
            AuthorizationProfile::ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    public function isEligibleForProtectedRoles(
        User $user,
        array $roleNames,
    ): bool {
        return User::query()
            ->eligibleForProtectedRoles($roleNames)
            ->whereKey($user->getKey())
            ->exists();
    }

    public function eligibleSuperAdminCount(): int
    {
        return User::query()->eligibleSuperAdmins()->count();
    }

    public function assertEligibleSuperAdminRemains(): void
    {
        if ($this->eligibleSuperAdminCount() < 1) {
            throw new AuthorizationMutationDenied(
                'final_active_super_admin',
                'At least one active super-admin must remain.',
            );
        }
    }
}
