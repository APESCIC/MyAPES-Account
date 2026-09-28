<?php

namespace App\Core\Extensions\Models;

use App\Core\Accounts\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per enablement settings row (#288). Stored in `module_plugin_settings`.
 */
#[Fillable([
    'sub_core_key',
    'module_key',
    'settings',
    'lock_version',
    'updated_by',
])]
class ModuleSetting extends Model
{
    protected $table = 'module_plugin_settings';

    public function instanceKey(): string
    {
        return "{$this->sub_core_key}:{$this->module_key}";
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'lock_version' => 'integer',
        ];
    }
}
