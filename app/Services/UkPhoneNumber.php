<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class UkPhoneNumber
{
    public function normalize(
        ?string $value,
        string $field,
        bool $required = true,
    ): ?string {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            if ($required) {
                throw ValidationException::withMessages([$field => 'A UK-reachable telephone number is required.']);
            }

            return null;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse($value, 'GB');
        } catch (NumberParseException) {
            throw ValidationException::withMessages([$field => 'Enter a valid UK-reachable telephone number.']);
        }

        if (! $util->isValidNumber($number)
            || $util->getRegionCodeForNumber($number) !== 'GB') {
            throw ValidationException::withMessages([$field => 'Enter a valid UK-reachable telephone number.']);
        }

        return $util->format($number, PhoneNumberFormat::E164);
    }
}
