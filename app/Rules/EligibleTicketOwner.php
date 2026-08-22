<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleTicketOwner implements ValidationRule
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
