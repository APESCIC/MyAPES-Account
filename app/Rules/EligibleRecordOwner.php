<?php

namespace App\Rules;

use App\Core\Accounts\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Shared owner-id rule for tickets, cases, and other owner-scoped records.
 *
 * Lives in App\Rules so plugins do not import each other for this check (#294).
 */
class EligibleRecordOwner implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if ($value === null) {
            return;
        }

        if (! User::query()->whereKey($value)->exists()) {
            $fail('The selected owner is unavailable.');
        }
    }
}
