<?php

namespace App\Models;

use App\Support\DirectoryGroupPrefix;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'external_id',
    'member_count',
    'status',
    'app_enabled',
    'first_seen_at',
    'last_seen_at',
    'last_synced_at',
])]
class DirectoryGroup extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_MISSING = 'missing';

    #[Cast]
    protected function casts(): array
    {
        return [
            'member_count' => 'integer',
            'app_enabled' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'directory_group_role_mappings')
            ->withPivot(['id', 'is_immutable'])
            ->withTimestamps();
    }

    public function roleSources(): HasMany
    {
        return $this->hasMany(RoleSource::class);
    }

    public function isAppEnabled(): bool
    {
        return (bool) $this->app_enabled;
    }

    public function isAlwaysEnabled(): bool
    {
        return self::isAlwaysEnabledName($this->name);
    }

    public static function isAlwaysEnabledName(?string $name): bool
    {
        return is_string($name)
            && $name !== ''
            && DirectoryGroupPrefix::isManagedGroup($name);
    }

    public static function defaultAppEnabledForName(string $name): bool
    {
        return self::isAlwaysEnabledName($name);
    }
}
