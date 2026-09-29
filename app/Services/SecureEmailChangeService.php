<?php

namespace App\Services;

use App\Core\Accounts\PendingEmailChange;
use App\Core\Accounts\User;
use App\Notifications\Auth\EmailChangeCompletedNotification;
use App\Notifications\Auth\EmailChangeConfirmNotification;
use App\Notifications\Auth\EmailChangeStartedNotification;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SecureEmailChangeService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function pendingFor(User $user): ?PendingEmailChange
    {
        $pending = $user->pendingEmailChange;

        if ($pending === null) {
            return null;
        }

        if ($pending->isExpired()) {
            $pending->delete();

            return null;
        }

        return $pending;
    }

    public function start(User $user, string $newEmail): PendingEmailChange
    {
        $this->assertLocalIdentity($user);

        $newEmail = strtolower(trim($newEmail));

        if ($newEmail === '' || $newEmail === strtolower(trim((string) $user->email))) {
            throw new DomainException(__('auth.email_change.same_as_current'));
        }

        if ($this->emailTakenByAnother($newEmail, $user->id)) {
            throw new DomainException(__('auth.register.email_unavailable'));
        }

        $expiresAt = now()->addMinutes((int) config('auth.verification.expire', 60));

        $pending = DB::transaction(function () use ($user, $newEmail, $expiresAt): PendingEmailChange {
            PendingEmailChange::query()->where('user_id', $user->id)->delete();

            return PendingEmailChange::query()->create([
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'expires_at' => $expiresAt,
            ]);
        });

        $confirmUrl = $this->confirmationUrl($user, $pending);

        $user->notify(new EmailChangeStartedNotification);

        Notification::route('mail', $newEmail)
            ->notify(new EmailChangeConfirmNotification(
                recipientName: $user->name,
                confirmUrl: $confirmUrl,
                newEmail: $newEmail,
            ));

        $this->auditLogger->record('profile.email_change_started', $user, $user, [
            'new_email' => $newEmail,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $pending;
    }

    public function confirm(User $user, string $hash): void
    {
        $this->assertLocalIdentity($user);

        $pending = PendingEmailChange::query()->where('user_id', $user->id)->first();

        if ($pending === null) {
            throw new DomainException(__('auth.email_change.invalid_or_expired'));
        }

        if ($pending->isExpired()) {
            $pending->delete();

            throw new DomainException(__('auth.email_change.invalid_or_expired'));
        }

        if (! hash_equals($pending->emailHash(), $hash)) {
            throw new DomainException(__('auth.email_change.invalid_or_expired'));
        }

        $newEmail = strtolower(trim($pending->new_email));

        if ($this->emailTakenByAnother($newEmail, $user->id)) {
            $pending->delete();

            throw new DomainException(__('auth.register.email_unavailable'));
        }

        $oldEmail = strtolower(trim((string) $user->email));

        DB::transaction(function () use ($user, $pending, $newEmail): void {
            $user->forceFill([
                'email' => $newEmail,
                'email_verified_at' => now(),
            ])->save();

            $pending->delete();
        });

        $completed = new EmailChangeCompletedNotification;

        if ($oldEmail !== '') {
            Notification::route('mail', $oldEmail)->notify($completed);
        }

        $user->refresh()->notify($completed);

        $this->auditLogger->record('profile.email_change_completed', $user, $user, [
            'previous_email' => $oldEmail,
            'email' => $newEmail,
        ]);
    }

    public function cancel(User $user): void
    {
        $this->assertLocalIdentity($user);

        $deleted = PendingEmailChange::query()->where('user_id', $user->id)->delete();

        if ($deleted === 0) {
            throw new DomainException(__('auth.email_change.nothing_pending'));
        }

        $this->auditLogger->record('profile.email_change_cancelled', $user, $user, []);
    }

    public function confirmationUrl(User $user, PendingEmailChange $pending): string
    {
        return URL::temporarySignedRoute(
            'email.change.confirm',
            $pending->expires_at,
            [
                'user' => $user->getKey(),
                'hash' => $pending->emailHash(),
            ],
        );
    }

    private function assertLocalIdentity(User $user): void
    {
        if (! $user->isLocalPasswordIdentity()) {
            throw new DomainException(__('auth.email_change.local_only'));
        }
    }

    private function emailTakenByAnother(string $email, int|string $ignoreUserId): bool
    {
        return User::query()
            ->where('email', Str::lower($email))
            ->whereKeyNot($ignoreUserId)
            ->exists();
    }
}
