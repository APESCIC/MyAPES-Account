<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use App\Exceptions\ModuleLifecycleException;
use App\Models\ModuleInstallation;
use JsonException;

class ModuleRollbackCompatibilityChecker
{
    private const MANIFEST_PATH = 'resources/data/module-runtime-contract.json';

    /** @var array<int, string> */
    private const LEGACY_VISIBLE_INSTANCES = [
        'apes-cic:tickets',
        'pet-care-clinic:consultations',
        'pet-care-clinic:pet-profiles',
        'shelter-rescue:cases',
        'shelter-rescue:pet-profiles',
    ];

    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleInstanceLock $locks,
    ) {}

    /** @return array{contract: string, installations: int, target_version: string} */
    public function check(string $targetRelease): array
    {
        $target = realpath($targetRelease);

        if ($target === false || ! is_dir($target)) {
            throw new ModuleLifecycleException(
                'target_release_invalid',
                'Module rollback compatibility failed.',
            );
        }

        return $this->locks->runMany(
            array_map(
                static fn ($instance): string => $instance->key(),
                $this->registry->matrix(),
            ),
            fn (): array => $this->checkLocked($target),
        );
    }

    /** @return array{contract: string, installations: int, target_version: string} */
    private function checkLocked(string $target): array
    {
        $installations = ModuleInstallation::query()
            ->orderBy('sub_core_key')
            ->orderBy('module_key')
            ->get();
        $manifestPath = $target.DIRECTORY_SEPARATOR.str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            self::MANIFEST_PATH,
        );

        if (! is_file($manifestPath)) {
            $this->assertLegacyRepresentable($installations);

            return [
                'contract' => 'legacy-v0.8.3',
                'installations' => $installations->count(),
                'target_version' => '0.8.3',
            ];
        }

        if (is_link($manifestPath)) {
            throw new ModuleLifecycleException(
                'target_manifest_invalid',
                'Module rollback compatibility failed.',
            );
        }

        $manifest = $this->readManifest($manifestPath);
        $this->assertTargetVersion(
            $target,
            $manifest['application_version'],
        );
        $supported = $manifest['shipped_instances'];
        $actual = $installations->map->instanceKey()->all();

        if (array_diff($actual, $supported) !== []) {
            throw new ModuleLifecycleException(
                'target_contract_unrepresentable',
                'Module rollback compatibility failed.',
            );
        }

        return [
            'contract' => 'manifest',
            'installations' => $installations->count(),
            'target_version' => $manifest['application_version'],
        ];
    }

    private function assertTargetVersion(
        string $target,
        string $manifestVersion,
    ): void {
        $versionPath = $target.DIRECTORY_SEPARATOR.'VERSION';
        $resolvedVersion = realpath($versionPath);

        if ($resolvedVersion === false
            || ! str_starts_with(
                $resolvedVersion,
                $target.DIRECTORY_SEPARATOR,
            )
            || ! is_file($resolvedVersion)
            || is_link($versionPath)) {
            throw new ModuleLifecycleException(
                'target_manifest_invalid',
                'Module rollback compatibility failed.',
            );
        }

        $version = trim((string) file_get_contents($resolvedVersion));

        if (! hash_equals($manifestVersion, $version)) {
            throw new ModuleLifecycleException(
                'target_manifest_invalid',
                'Module rollback compatibility failed.',
            );
        }
    }

    private function assertLegacyRepresentable($installations): void
    {
        $actual = $installations->map->instanceKey()->all();
        sort($actual);

        if ($actual !== self::LEGACY_VISIBLE_INSTANCES
            || $installations->contains(
                static fn (ModuleInstallation $installation): bool => ! $installation->enabled,
            )) {
            throw new ModuleLifecycleException(
                'legacy_contract_unrepresentable',
                'Module rollback compatibility failed.',
            );
        }
    }

    /**
     * @return array{
     *     schema_version: int,
     *     application_version: string,
     *     sub_cores: array<int, string>,
     *     module_types: array<int, string>,
     *     shipped_instances: array<int, string>,
     *     legacy_visible_instances: array<int, string>
     * }
     */
    private function readManifest(string $path): array
    {
        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $decoded = null;
        }

        $required = [
            'schema_version',
            'application_version',
            'sub_cores',
            'module_types',
            'shipped_instances',
            'legacy_visible_instances',
        ];

        if (! is_array($decoded)
            || count($decoded) !== count($required)
            || array_diff($required, array_keys($decoded)) !== []
            || $decoded['schema_version'] !== 1
            || ! is_string($decoded['application_version'])
            || preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $decoded['application_version']) !== 1
            || ! $this->isSafeKeyList($decoded['sub_cores'])
            || ! $this->isSafeKeyList($decoded['module_types'])
            || ! $this->isSafeInstanceList($decoded['shipped_instances'])
            || ! $this->isSafeInstanceList($decoded['legacy_visible_instances'])
            || ! $this->instancesUseDeclaredKeys(
                $decoded['shipped_instances'],
                $decoded['sub_cores'],
                $decoded['module_types'],
            )
            || ! $this->instancesUseDeclaredKeys(
                $decoded['legacy_visible_instances'],
                $decoded['sub_cores'],
                $decoded['module_types'],
            )
            || array_diff($decoded['legacy_visible_instances'], $decoded['shipped_instances']) !== []) {
            throw new ModuleLifecycleException(
                'target_manifest_invalid',
                'Module rollback compatibility failed.',
            );
        }

        return $decoded;
    }

    private function isSafeKeyList(mixed $values): bool
    {
        if (! is_array($values)
            || $values === []
            || array_values($values) !== $values) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_string($value)
                || preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $value) !== 1) {
                return false;
            }
        }

        $sorted = array_values(array_unique($values));
        sort($sorted);

        return $values === $sorted;
    }

    private function isSafeInstanceList(mixed $values): bool
    {
        if (! is_array($values)
            || $values === []
            || array_values($values) !== $values) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_string($value)
                || preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*:[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $value) !== 1) {
                return false;
            }
        }

        $sorted = array_values(array_unique($values));
        sort($sorted);

        return $values === $sorted;
    }

    /**
     * @param  array<int, string>  $instances
     * @param  array<int, string>  $subCores
     * @param  array<int, string>  $moduleTypes
     */
    private function instancesUseDeclaredKeys(
        array $instances,
        array $subCores,
        array $moduleTypes,
    ): bool {
        foreach ($instances as $instance) {
            [$subCore, $module] = explode(':', $instance, 2);

            if (! in_array($subCore, $subCores, true)
                || ! in_array($module, $moduleTypes, true)) {
                return false;
            }
        }

        return true;
    }
}
