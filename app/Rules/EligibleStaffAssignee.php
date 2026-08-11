<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleStaffAssignee implements ValidationRule
{
    public function __construct(
        private readonly ?string $requiredPermission = null,
    ) {}

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if ($value === null) {
            return;
        }

        $query = User::query()->eligibleStaff()->whereKey($value);
        if ($this->requiredPermission !== null) {
            $query->withAuthorizationPermission($this->requiredPermission);
        }

        if (! $query->exists()) {
            $fail('The selected assignee is unavailable.');
        }
    }
}
