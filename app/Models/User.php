<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

#[Fillable([
    'oidc_sub',
    'identity_type',
    'name',
    'email',
    'password',
    'ldap_groups',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const IDENTITY_LOCAL = 'local';

    public const IDENTITY_CLOUDRON_OIDC = 'cloudron_oidc';

    public const ROLE_SERVICE_USER = 'service_user';

    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPERADMIN = 'superadmin';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Cast]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ldap_groups' => 'array',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function accessLevel(): string
    {
        return (string) $this->getAttribute('legacy_access_level');
    }

    public static function accessLevelColumn(): string
    {
        return 'legacy_access_level';
    }

    /**
     * @return array<int, string>
     */
    public static function accessLevels(): array
    {
        return [
            self::ROLE_SERVICE_USER,
            self::ROLE_STAFF,
            self::ROLE_ADMIN,
            self::ROLE_SUPERADMIN,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function staffAccessLevels(): array
    {
        return [self::ROLE_STAFF, self::ROLE_ADMIN, self::ROLE_SUPERADMIN];
    }

    /**
     * @return array<int, string>
     */
    public static function adminAccessLevels(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_SUPERADMIN];
    }

    public function setAccessLevel(string $accessLevel): static
    {
        if (! in_array($accessLevel, self::accessLevels(), true)) {
            throw new InvalidArgumentException("Unsupported access level [{$accessLevel}].");
        }

        $this->setAttribute('legacy_access_level', $accessLevel);

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'role')) {
            $this->setAttribute('role', $accessLevel);
        }

        return $this;
    }

    /**
     * @param  array<int, string>  $accessLevels
     */
    public function scopeWithAccessLevels(Builder $query, array $accessLevels): Builder
    {
        return $query->whereIn(self::accessLevelColumn(), $accessLevels);
    }

    public function isCloudronIdentity(): bool
    {
        return $this->identity_type === self::IDENTITY_CLOUDRON_OIDC
            && is_string($this->oidc_sub)
            && trim($this->oidc_sub) !== '';
    }

    public function isStaff(): bool
    {
        return in_array($this->accessLevel(), self::staffAccessLevels(), true);
    }

    public function isAdmin(): bool
    {
        return in_array($this->accessLevel(), self::adminAccessLevels(), true);
    }
}
