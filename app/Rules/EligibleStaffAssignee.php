<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleStaffAssignee implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if ($value === null) {
            return;
        }

        if (! User::query()
            ->eligibleStaff()
            ->whereKey($value)
            ->exists()) {
            $fail('The selected assignee is unavailable.');
        }
    }
}
