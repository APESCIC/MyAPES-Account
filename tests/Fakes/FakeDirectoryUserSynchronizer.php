<?php

namespace Tests\Fakes;

use App\Services\DirectoryUserSynchronizer;

final class FakeDirectoryUserSynchronizer extends DirectoryUserSynchronizer
{
    public function __construct()
    {
    }

    /**
     * @return array{
     *     seen: int,
     *     created: int,
     *     updated: int,
     *     suspended: int,
     *     unsuspended: int
     * }
     */
    public function synchronize(): array
    {
        return [
            'seen' => 0,
            'created' => 0,
            'updated' => 0,
            'suspended' => 0,
            'unsuspended' => 0,
        ];
    }
}
