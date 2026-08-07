<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ModuleProjectionCache
{
    public const VERSION_KEY = 'myapes:modules:projection-version';

    public function version(): int
    {
        Cache::add(
            self::VERSION_KEY,
            1,
            now()->addYears(10),
        );

        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function invalidate(): void
    {
        $this->version();
        Cache::increment(self::VERSION_KEY);
    }
}
