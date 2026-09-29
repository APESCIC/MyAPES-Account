<?php

namespace App\Core\Extensions\Plugins;

use Illuminate\Support\Facades\Log;

/**
 * Logs each missing plugin translation key at most once per process (#272).
 */
final class MissingPluginTranslationKeyLogger
{
    /** @var array<string, true> */
    private static array $logged = [];

    public static function once(string $pluginSlug, string $key): void
    {
        if (isset(self::$logged[$key])) {
            return;
        }

        self::$logged[$key] = true;

        Log::warning('Missing plugin translation key; falling back to English manifest text.', [
            'plugin' => $pluginSlug,
            'key' => $key,
        ]);
    }
}
