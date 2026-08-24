<?php

namespace App\Support;

final class DirectoryGroupPrefix
{
    public static function prefix(): string
    {
        $prefix = config('myapes.directory.group_prefix', 'myapesaccount.');

        return is_string($prefix) && $prefix !== ''
            ? strtolower($prefix)
            : 'myapesaccount.';
    }

    public static function isManagedGroup(string $name): bool
    {
        $normalized = strtolower(trim($name));

        return $normalized !== ''
            && str_starts_with($normalized, self::prefix());
    }

    /**
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    public static function filterGroups(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            if (! is_string($group)) {
                continue;
            }

            $canonical = DirectoryLegacyGroupAliases::canonicalFor($group);

            if ($canonical !== null) {
                $filtered[] = $canonical;
            }
        }

        $filtered = array_values(array_unique($filtered));
        sort($filtered);

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    public static function requiredGroups(): array
    {
        $groups = config('myapes.directory.required_groups', []);

        if (! is_array($groups)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $group): string => is_string($group)
                ? strtolower(trim($group))
                : '',
            $groups,
        ))));
    }
}
