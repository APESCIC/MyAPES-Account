<?php

namespace App\Rules;

use App\Core\Accounts\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Block registration when an email is already taken, without enumeration (#229).
 *
 * Verified and unverified collisions share the same generic message so callers
 * cannot distinguish account existence from the error text.
 */
class AvailablePublicEmail implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $email = strtolower(trim($value));

        if (User::query()->where('email', $email)->exists()) {
            $fail(__('auth.register.email_unavailable'));
        }
    }
}
