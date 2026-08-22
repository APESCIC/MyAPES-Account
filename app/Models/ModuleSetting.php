<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sub_core_key',
    'module_key',
    'settings',
    'lock_version',
    'updated_by',
])]
class ModuleSetting extends Model
{
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
