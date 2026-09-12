<?php

namespace App\Rules;

use App\Support\UkDateTime;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkDateTimeFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || app(UkDateTime::class)->parse($value) === null) {
            $fail('Enter a UK date as dd/mm/yyyy, optionally with HH:mm or HH:mm:ss.');
        }
    }
}
