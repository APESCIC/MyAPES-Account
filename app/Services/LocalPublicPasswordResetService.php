<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalPublicPasswordResetService
{
    public function __construct(
        private readonly AuthorizationProfile $profile,
        private readonly AuthorizationMutationService $mutations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function canReset(User $actor, User $target): bool
    {
        return $this->refusalMessage($actor, $target) === null;
    }

    public function isEligibleForGuestReset(?User $user): bool
    {
        if ($user === null || $user->suspended_at !== null) {
            return false;
        }

        if ($user->isPendingFirstLogin()) {
            return false;
        }

        return $user->isLocalPasswordIdentity()
            && ! $this->profile->hasDirectoryProtectedEligibility($user);
    }

    public function completeGuestReset(User $user, string $password): void
    {
        if (! $this->isEligibleForGuestReset($user)) {
            throw new DomainException(
                'Only local public accounts can reset a password here. Directory and Cloudron accounts stay on Cloudron.',
            );
        }

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => $password,
                'authorization_epoch' => (int) $user->authorization_epoch + 1,
            ]);
            $user->setRememberToken(Str::random(60));
            $user->save();

            $this->auditLogger->record(
                'auth.public_local_password_reset',
                $user,
                $user,
                [
                    'target_user_id' => $user->id,
                    'action' => 'guest_reset_local_public_password',
                ],
            );
        });
    }

    public function reset(User $actor, User $target): string
    {
        $refusal = $this->refusalMessage($actor, $target);

        if ($refusal !== null) {
            throw new DomainException($refusal);
        }

        $temporaryPassword = Str::password(20);

        DB::transaction(function () use ($actor, $target, $temporaryPassword): void {
            $target->forceFill([
                'password' => $temporaryPassword,
                'authorization_epoch' => (int) $target->authorization_epoch + 1,
            ]);
            $target->setRememberToken(Str::random(60));
            $target->save();

            $this->auditLogger->record(
                'auth.local_public_password_reset',
                $actor,
                $target,
                [
                    'target_user_id' => $target->id,
                    'action' => 'reset_local_public_password',
                ],
            );
        });

        return $temporaryPassword;
    }

    public function refusalMessage(User $actor, User $target): ?string
    {
        if (! $this->mutations->canManageTarget($actor, $target)) {
            return 'You cannot reset the password for this account.';
        }

        if ($target->isPendingFirstLogin()) {
            return 'Pending first-login directory accounts stay on Cloudron.';
        }

        if (
            ! $target->isLocalPasswordIdentity()
            || $this->profile->hasDirectoryProtectedEligibility($target)
        ) {
            return 'Only local public accounts can have their password reset here. Directory and Cloudron accounts stay on Cloudron.';
        }

        return null;
    }
}
