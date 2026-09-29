<?php

namespace App\Rules;

use App\Core\Accounts\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Local public username policy: length, charset, reserved words, uniqueness (#227).
 */
class Username implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreUserId = null,
        private readonly bool $checkUnique = true,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.username.format'));

            return;
        }

        $username = strtolower(trim($value));
        $min = (int) config('username.min_length', 3);
        $max = (int) config('username.max_length', 30);
        $pattern = (string) config('username.pattern', '/^[a-z0-9](?:[a-z0-9._-]{1,28}[a-z0-9])$/');

        if (strlen($username) < $min || strlen($username) > $max) {
            $fail(__('validation.username.length', ['min' => $min, 'max' => $max]));

            return;
        }

        if (@preg_match($pattern, $username) !== 1) {
            $fail(__('validation.username.format'));

            return;
        }

        /** @var list<string> $reserved */
        $reserved = array_map(
            static fn (mixed $word): string => strtolower(trim((string) $word)),
            config('username.reserved', []),
        );

        if (in_array($username, $reserved, true)) {
            $fail(__('validation.username.reserved'));

            return;
        }

        if (! $this->checkUnique) {
            return;
        }

        $query = User::query()->where('username', $username);

        if ($this->ignoreUserId !== null) {
            $query->whereKeyNot($this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail(__('validation.username.unique'));
        }
    }
}
