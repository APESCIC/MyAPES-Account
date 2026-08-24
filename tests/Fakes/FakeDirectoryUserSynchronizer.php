<?php

namespace Tests\Fakes;

use App\Services\DirectoryUserSynchronizer;

final class FakeDirectoryUserSynchronizer extends DirectoryUserSynchronizer
{
    public function __construct()
    {
    }

    /**
     * @return array{seen: int, created: int, updated: int}
     */
    public function synchronize(): array
    {
        return [
            'seen' => 0,
            'created' => 0,
            'updated' => 0,
        ];
    }
}
