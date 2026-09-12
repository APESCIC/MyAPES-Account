<?php

namespace App\Support;

final class PrivacyNotice
{
    public static function url(): string
    {
        $configured = config('myapes.consent.privacy_notice_url');
        if (self::isValidAbsoluteUrl($configured)) {
            return $configured;
        }

        return route('privacy');
    }

    public static function opensExternally(): bool
    {
        $configured = config('myapes.consent.privacy_notice_url');
        if (! self::isValidAbsoluteUrl($configured)) {
            return false;
        }

        return rtrim($configured, '/') !== rtrim(route('privacy'), '/');
    }

    private static function isValidAbsoluteUrl(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
