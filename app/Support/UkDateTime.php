<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

class UkDateTime
{
    public const TIMEZONE = 'Europe/London';

    public const DATE_TIME_FORMAT = 'd/m/Y H:i:s';

    public const DATE_FORMAT = 'd/m/Y';

    /**
     * @var list<string>
     */
    private const UK_FORMATS = [
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y G:i:s',
        'd/m/Y G:i',
        'd/m/Y',
        'j/n/Y H:i:s',
        'j/n/Y H:i',
        'j/n/Y G:i:s',
        'j/n/Y G:i',
        'j/n/Y',
    ];

    /**
     * @var list<string>
     */
    private const UTC_FORMATS = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
    ];

    public function format(?DateTimeInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(self::TIMEZONE)
            ->format(self::DATE_TIME_FORMAT);
    }

    public function formatDate(?DateTimeInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(self::TIMEZONE)
            ->format(self::DATE_FORMAT);
    }

    public function parse(?string $value): ?Carbon
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        foreach (self::UK_FORMATS as $format) {
            $parsed = $this->parseExact($value, $format, self::TIMEZONE);
            if ($parsed !== null) {
                return $parsed->utc();
            }
        }

        foreach (self::UTC_FORMATS as $format) {
            $parsed = $this->parseExact($value, $format, 'UTC');
            if ($parsed !== null) {
                return $parsed->utc();
            }
        }

        return null;
    }

    private function parseExact(string $value, string $format, string $timezone): ?Carbon
    {
        try {
            $parsed = Carbon::createFromFormat('!'.$format, $value, $timezone);
        } catch (Throwable) {
            return null;
        }

        if ($parsed === false) {
            return null;
        }

        if ($parsed->format($format) !== $value) {
            return null;
        }

        return $parsed;
    }
}
