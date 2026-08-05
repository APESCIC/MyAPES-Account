<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class LegacyAccessCompatibilityAdapter
{
    public const COLUMN = 'legacy_access_level';

    public function read(User $user): string
    {
        return (string) $user->getAttribute(self::COLUMN);
    }

    public function write(User $user, string $accessLevel): User
    {
        if (! in_array($accessLevel, User::accessLevels(), true)) {
            throw new InvalidArgumentException(
                "Unsupported access level [{$accessLevel}].",
            );
        }

        $user->setAttribute(self::COLUMN, $accessLevel);

        if ($user->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($user->getTable(), 'role')) {
            $user->setAttribute('role', $accessLevel);
        }

        return $user;
    }

    /**
     * @param  array<int, string>  $accessLevels
     */
    public function scope(
        Builder $query,
        array $accessLevels,
    ): Builder {
        return $query->whereIn(self::COLUMN, $accessLevels);
    }

    public function staff(User $user): bool
    {
        return in_array(
            $this->read($user),
            User::staffAccessLevels(),
            true,
        );
    }

    public function admin(User $user): bool
    {
        return in_array(
            $this->read($user),
            User::adminAccessLevels(),
            true,
        );
    }
}
