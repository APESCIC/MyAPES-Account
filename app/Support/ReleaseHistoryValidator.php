<?php

namespace App\Support;

use DateTimeImmutable;

class ReleaseHistoryValidator
{
    /**
     * @var list<string>
     */
    private const REQUIRED_FIELDS = [
        'version',
        'date',
        'channel',
        'type',
        'title',
        'summary',
        'changes',
        'affected_areas',
        'categories',
        'audiences',
        'version_rationale',
        'validation',
        'known_limitations',
        'rollback',
        'provenance',
        'references',
    ];

    /**
     * @var list<string>
     */
    private const CHANNELS = ['stable', 'beta'];

    /**
     * @var list<string>
     */
    private const VERSION_TYPES = ['major', 'minor', 'patch'];

    /**
     * @var list<string>
     */
    private const CATEGORIES = [
        'added',
        'changed',
        'fixed',
        'removed',
        'security',
        'compliance',
        'accessibility',
    ];

    /**
     * @var list<string>
     */
    private const AUDIENCES = ['public-facing', 'internal-only'];

    /**
     * @param  list<array<string, mixed>>  $releases
     * @return list<string>
     */
    public function validate(array $releases, string $version): array
    {
        $errors = [];
        $version = trim($version);

        if ($releases === []) {
            $errors[] = 'Release history must contain at least one record.';

            return $errors;
        }

        if (! $this->isSemanticVersion($version)) {
            $errors[] = 'VERSION must contain a semantic version without a v prefix.';
        }

        $seenVersions = [];
        $previousVersion = null;
        $previousDate = null;

        foreach ($releases as $index => $release) {
            $recordNumber = $index + 1;

            foreach (array_diff(array_keys($release), self::REQUIRED_FIELDS) as $field) {
                $errors[] = "Release {$recordNumber} contains unsupported field [{$field}].";
            }

            foreach (self::REQUIRED_FIELDS as $field) {
                if (! array_key_exists($field, $release) || $this->isEmptyRequiredValue($release[$field])) {
                    $errors[] = "Release {$recordNumber} is missing required field [{$field}].";
                }
            }

            $releaseVersion = is_string($release['version'] ?? null)
                ? trim($release['version'])
                : '';
            $releaseDate = is_string($release['date'] ?? null)
                ? trim($release['date'])
                : '';

            if (! $this->isSemanticVersion($releaseVersion)) {
                $errors[] = "Release {$recordNumber} has an invalid semantic version.";
            } else {
                if (isset($seenVersions[$releaseVersion])) {
                    $errors[] = 'Release versions must be unique.';
                }

                $seenVersions[$releaseVersion] = true;

                if ($previousVersion !== null && version_compare($previousVersion, $releaseVersion, '<=')) {
                    $errors[] = 'Release versions must be ordered newest first.';
                }

                $previousVersion = $releaseVersion;
            }

            if (! $this->isIsoDate($releaseDate)) {
                $errors[] = "Release {$recordNumber} has an invalid ISO release date.";
            } else {
                if ($previousDate !== null && $releaseDate > $previousDate) {
                    $errors[] = 'Release dates must be non-increasing.';
                }

                $previousDate = $releaseDate;
            }

            if (! in_array($release['channel'] ?? null, self::CHANNELS, true)) {
                $errors[] = "Release {$recordNumber} has an unsupported channel.";
            }

            if (! in_array($release['type'] ?? null, self::VERSION_TYPES, true)) {
                $errors[] = "Release {$recordNumber} has an unsupported version type.";
            }

            if (! $this->containsOnly($release['categories'] ?? null, self::CATEGORIES)) {
                $errors[] = "Release {$recordNumber} has unsupported categories.";
            }

            if (! $this->containsOnly($release['audiences'] ?? null, self::AUDIENCES)) {
                $errors[] = "Release {$recordNumber} has unsupported audiences.";
            }

            $this->validateStringLists($release, $recordNumber, $errors);
            $this->validateReferences($release['references'] ?? null, $recordNumber, $errors);

            if ($this->containsCredentialShapedText($release)) {
                $errors[] = "Release {$recordNumber} contains text that resembles a credential.";
            }
        }

        if (($releases[0]['version'] ?? null) !== $version) {
            $errors[] = 'VERSION must match the newest release record.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  list<array<string, mixed>>  $releases
     * @param  list<array<string, mixed>>  $baseReleases
     * @return list<string>
     */
    public function validateAppendOnly(
        array $releases,
        string $version,
        array $baseReleases,
        string $baseVersion,
    ): array {
        $errors = [
            ...$this->validate($releases, $version),
            ...$this->validate($baseReleases, $baseVersion),
        ];

        if (count($releases) !== count($baseReleases) + 1) {
            $errors[] = 'Release history must prepend exactly one new record.';
        } elseif (array_slice($releases, 1) !== $baseReleases) {
            $errors[] = 'Published release records must remain unchanged and in order.';
        }

        if (! $this->isSemanticVersion($version)
            || ! $this->isSemanticVersion(trim($baseVersion))
            || version_compare($version, trim($baseVersion), '<=')) {
            $errors[] = 'The new release version must be higher than the base version.';
        }

        return array_values(array_unique($errors));
    }

    private function isSemanticVersion(string $version): bool
    {
        return preg_match('/\A(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\z/', $version) === 1;
    }

    private function isIsoDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isEmptyRequiredValue(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && $value === []);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function containsOnly(mixed $values, array $allowed): bool
    {
        if (! is_array($values) || $values === []) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                return false;
            }
        }

        return count(array_unique($values)) === count($values);
    }

    /**
     * @param  array<string, mixed>  $release
     * @param  list<string>  $errors
     */
    private function validateStringLists(array $release, int $recordNumber, array &$errors): void
    {
        foreach (['changes', 'affected_areas', 'validation', 'known_limitations'] as $field) {
            $values = $release[$field] ?? null;

            if (! is_array($values) || $values === []) {
                continue;
            }

            foreach ($values as $value) {
                if (! is_string($value) || trim($value) === '') {
                    $errors[] = "Release {$recordNumber} field [{$field}] must contain non-empty strings.";
                    break;
                }
            }
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function validateReferences(mixed $references, int $recordNumber, array &$errors): void
    {
        if (! is_array($references) || $references === []) {
            return;
        }

        foreach ($references as $reference) {
            if (! is_array($reference)
                || ! is_string($reference['label'] ?? null)
                || trim($reference['label']) === ''
                || ! is_string($reference['url'] ?? null)
                || filter_var($reference['url'], FILTER_VALIDATE_URL) === false
                || ! str_starts_with($reference['url'], 'https://github.com/APESCIC/MyAPES-Account/')) {
                $errors[] = "Release {$recordNumber} has an invalid reference.";
                break;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $release
     */
    private function containsCredentialShapedText(array $release): bool
    {
        $encoded = json_encode($release);

        if (! is_string($encoded)) {
            return true;
        }

        return preg_match(
            '/(?:gh[pousr]_[A-Za-z0-9]{20,}|sk-[A-Za-z0-9_-]{20,}|-----BEGIN [A-Z ]*PRIVATE KEY-----)/',
            $encoded,
        ) === 1;
    }
}
