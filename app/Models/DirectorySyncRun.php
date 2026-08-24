<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'source',
    'status',
    'queue_job_uuid',
    'queue_attempt',
    'lease_owner_token',
    'started_at',
    'finished_at',
    'groups_seen',
    'groups_missing',
    'users_seen',
    'users_created',
    'users_updated',
    'error_code',
])]
class DirectorySyncRun extends Model
{
    /** @var list<string> */
    protected $hidden = [
        'queue_job_uuid',
        'lease_owner_token',
    ];

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_SCHEDULED = 'scheduled';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    #[Cast]
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'queue_attempt' => 'integer',
            'groups_seen' => 'integer',
            'groups_missing' => 'integer',
            'users_seen' => 'integer',
            'users_created' => 'integer',
            'users_updated' => 'integer',
        ];
    }
}
