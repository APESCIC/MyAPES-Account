<?php

namespace App\Core\Extensions\Models;

use App\Core\Accounts\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-module plugin enablement row (#288).
 *
 * Stored in `module_plugins`. Legacy column names `sub_core_key` / `module_key`
 * remain; use {@see module()} / {@see plugin()} for Structure vocabulary.
 */
#[Fillable([
    'sub_core_key',
    'module_key',
    'enabled',
    'lock_version',
    'installed_at',
    'installed_by',
    'enabled_at',
    'enabled_by',
    'disabled_at',
    'disabled_by',
])]
class ModuleInstallation extends Model
{
    protected $table = 'module_plugins';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'lock_version' => 'integer',
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function module(): string
    {
        return (string) $this->sub_core_key;
    }

    public function plugin(): string
    {
        return (string) $this->module_key;
    }

    public function instanceKey(): string
    {
        return "{$this->sub_core_key}:{$this->module_key}";
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }
}
