<?php

namespace App\Core\Extensions\Plugins;

use InvalidArgumentException;

/**
 * Collects and validates plugin package manifests (#287).
 */
final class PluginRegistry
{
    /** @var array<string, PluginManifest> */
    private array $manifests = [];

    private bool $validated = false;

    public function register(PluginManifest $manifest): void
    {
        if (isset($this->manifests[$manifest->slug])) {
            throw new InvalidArgumentException(
                "Duplicate plugin slug [{$manifest->slug}].",
            );
        }

        $this->manifests[$manifest->slug] = $manifest;
        ksort($this->manifests);
        $this->validated = false;
    }

    /** @return array<string, PluginManifest> */
    public function plugins(): array
    {
        $this->ensureValid();

        return $this->manifests;
    }

    public function plugin(string $slug): PluginManifest
    {
        $this->ensureValid();

        return $this->manifests[$slug]
            ?? throw new InvalidArgumentException("Unknown plugin slug [{$slug}].");
    }

    public function has(string $slug): bool
    {
        return isset($this->manifests[$slug]);
    }

    /**
     * @param  list<string>  $knownModuleSlugs
     */
    public function validate(array $knownModuleSlugs, string $applicationVersion): void
    {
        foreach ($this->manifests as $manifest) {
            foreach ($manifest->compatibleModules as $moduleSlug) {
                if ($moduleSlug === '*') {
                    continue;
                }

                if (! in_array($moduleSlug, $knownModuleSlugs, true)) {
                    throw new InvalidArgumentException(
                        "Plugin [{$manifest->slug}] references unknown module [{$moduleSlug}].",
                    );
                }
            }

            foreach ($manifest->shippedModules as $moduleSlug) {
                if (! $manifest->isCompatibleWith($moduleSlug)) {
                    throw new InvalidArgumentException(
                        "Plugin [{$manifest->slug}] ships for incompatible module [{$moduleSlug}].",
                    );
                }
            }

            if (! $this->coreConstraintSatisfied($manifest->requiresCore, $applicationVersion)) {
                throw new InvalidArgumentException(
                    "Plugin [{$manifest->slug}] requires Core {$manifest->requiresCore}; app is {$applicationVersion}.",
                );
            }

            foreach ($manifest->dependencies as $dependency) {
                if (! isset($this->manifests[$dependency->pluginSlug])) {
                    throw new InvalidArgumentException(
                        "Plugin [{$manifest->slug}] depends on missing plugin [{$dependency->pluginSlug}].",
                    );
                }
            }
        }

        $this->assertAcyclic();
        $this->validated = true;
    }

    public function ensureValid(): void
    {
        if ($this->validated || $this->manifests === []) {
            return;
        }

        $this->assertAcyclic();
        $this->validated = true;
    }

    private function coreConstraintSatisfied(string $constraint, string $version): bool
    {
        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        if (str_starts_with($constraint, '^')) {
            $min = ltrim($constraint, '^');

            return version_compare($version, $min, '>=');
        }

        if (str_starts_with($constraint, '>=')) {
            $min = trim(substr($constraint, 2));

            return version_compare($version, $min, '>=');
        }

        return $constraint === $version;
    }

    private function assertAcyclic(): void
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $slug) use (&$visit, &$visiting, &$visited): void {
            if (isset($visiting[$slug])) {
                throw new InvalidArgumentException(
                    "Plugin dependency cycle detected at [{$slug}].",
                );
            }

            if (isset($visited[$slug])) {
                return;
            }

            $visiting[$slug] = true;

            foreach ($this->manifests[$slug]->dependencies as $dependency) {
                if (! isset($this->manifests[$dependency->pluginSlug])) {
                    continue;
                }

                $visit($dependency->pluginSlug);
            }

            unset($visiting[$slug]);
            $visited[$slug] = true;
        };

        foreach (array_keys($this->manifests) as $slug) {
            $visit($slug);
        }
    }
}
