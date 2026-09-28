<?php

namespace App\Support;

/**
 * Hand-rolled import scanner for Structure architecture rules (#294).
 *
 * Parses PHP `use` statements and FQCN references without extra Composer deps.
 */
final class ArchitectureDependencyScanner
{
    private const MODULE_SLUGS = ['apes-cic', 'pet-care-clinic', 'shelter-rescue'];

    /**
     * @return list<string> Violation messages
     */
    public function scanRepository(string $root): array
    {
        $violations = [];
        $pluginDeps = $this->declaredPluginDependencies($root);

        foreach ($this->phpFiles($root.'/app/Core') as $file) {
            foreach ($this->importedNamespaces($file) as $imported) {
                if (str_starts_with($imported, 'Modules\\') || str_starts_with($imported, 'Plugins\\')) {
                    $violations[] = $this->msg($root, $file, "Core must not import [{$imported}].");
                }
            }
        }

        foreach ($this->pluginSlugs($root) as $slug) {
            $studly = $this->studly($slug);
            $pluginRoot = $root.'/plugins/'.$slug;

            foreach ($this->phpFiles($pluginRoot) as $file) {
                foreach ($this->importedNamespaces($file) as $imported) {
                    if (str_starts_with($imported, 'Modules\\')) {
                        $violations[] = $this->msg($root, $file, "Plugin [{$slug}] must not import Modules [{$imported}].");

                        continue;
                    }

                    if (! preg_match('/^Plugins\\\\([A-Za-z0-9_]+)(?:\\\\|$)/', $imported, $matches)) {
                        continue;
                    }

                    $otherStudly = $matches[1];
                    if ($otherStudly === $studly) {
                        continue;
                    }

                    $otherSlug = $this->slugFromStudly($otherStudly, $root);
                    $allowed = $pluginDeps[$slug] ?? [];

                    if ($otherSlug === null || ! in_array($otherSlug, $allowed, true)) {
                        $violations[] = $this->msg(
                            $root,
                            $file,
                            "Plugin [{$slug}] imports [{$imported}] but did not declare dependency on [{$otherSlug}].",
                        );

                        continue;
                    }

                    if (! $this->isPublicPluginApi($imported, $otherStudly)) {
                        $violations[] = $this->msg(
                            $root,
                            $file,
                            "Plugin [{$slug}] may only use public API of [{$otherSlug}] (Contracts, Models, Support); got [{$imported}].",
                        );
                    }
                }
            }
        }

        foreach ($this->moduleSlugs($root) as $slug) {
            $studly = $this->studly($slug);
            $moduleRoot = $root.'/modules/'.$slug;

            foreach ($this->phpFiles($moduleRoot) as $file) {
                foreach ($this->importedNamespaces($file) as $imported) {
                    if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)(?:\\\\|$)/', $imported, $matches)) {
                        if ($matches[1] !== $studly) {
                            $violations[] = $this->msg(
                                $root,
                                $file,
                                "Module [{$slug}] must not import another module [{$imported}].",
                            );
                        }
                    }

                    if (preg_match('/^Plugins\\\\([A-Za-z0-9_]+)\\\\(.+)$/', $imported, $matches)) {
                        $remainder = $matches[2];
                        if (! str_starts_with($remainder, 'Contracts\\') && $remainder !== 'Contracts') {
                            $violations[] = $this->msg(
                                $root,
                                $file,
                                "Module [{$slug}] may import plugins only via Contracts; got [{$imported}].",
                            );
                        }
                    }
                }
            }
        }

        foreach ([
            'app/Http/Controllers/ApesCic',
            'app/Http/Controllers/Shelter',
            'app/Http/Controllers/PetCare',
            'app/Modules/Activity',
            'app/Modules/Analytics',
            'app/Modules/Attention',
            'app/Modules/Detectors',
            'app/Modules/Summaries',
        ] as $legacy) {
            $path = $root.'/'.$legacy;
            if (! is_dir($path)) {
                continue;
            }

            $files = $this->phpFiles($path);
            if ($files !== []) {
                $violations[] = "Legacy location [{$legacy}] still contains PHP classes.";
            }
        }

        return $violations;
    }

    /**
     * @param  list<string>  $sourceSnippets  PHP source strings to evaluate against one rule
     * @return list<string>
     */
    public function findIllegalCoreImports(array $sourceSnippets): array
    {
        $violations = [];

        foreach ($sourceSnippets as $i => $source) {
            foreach ($this->namespacesFromSource($source) as $imported) {
                if (str_starts_with($imported, 'Modules\\') || str_starts_with($imported, 'Plugins\\')) {
                    $violations[] = "snippet[{$i}] Core must not import [{$imported}].";
                }
            }
        }

        return $violations;
    }

    /**
     * @param  list<string>  $allowedDependencies
     * @return list<string>
     */
    public function findIllegalPluginImports(
        string $pluginSlug,
        string $source,
        array $allowedDependencies,
    ): array {
        $violations = [];
        $studly = $this->studly($pluginSlug);

        foreach ($this->namespacesFromSource($source) as $imported) {
            if (str_starts_with($imported, 'Modules\\')) {
                $violations[] = "Plugin [{$pluginSlug}] must not import Modules [{$imported}].";

                continue;
            }

            if (! preg_match('/^Plugins\\\\([A-Za-z0-9_]+)(?:\\\\|$)/', $imported, $matches)) {
                continue;
            }

            $otherStudly = $matches[1];
            if ($otherStudly === $studly) {
                continue;
            }

            $otherSlug = $this->kebab($otherStudly);
            if (! in_array($otherSlug, $allowedDependencies, true)) {
                $violations[] = "Plugin [{$pluginSlug}] undeclared dependency import [{$imported}].";

                continue;
            }

            if (! $this->isPublicPluginApi($imported, $otherStudly)) {
                $violations[] = "Plugin [{$pluginSlug}] non-public API import [{$imported}].";
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    public function findIllegalModuleToModuleImports(string $moduleSlug, string $source): array
    {
        $violations = [];
        $studly = $this->studly($moduleSlug);

        foreach ($this->namespacesFromSource($source) as $imported) {
            if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)(?:\\\\|$)/', $imported, $matches)
                && $matches[1] !== $studly) {
                $violations[] = "Module [{$moduleSlug}] must not import [{$imported}].";
            }
        }

        return $violations;
    }

    /**
     * @return array<string, list<string>>
     */
    public function declaredPluginDependencies(string $root): array
    {
        $map = [];

        foreach ($this->pluginSlugs($root) as $slug) {
            $map[$slug] = [];
            $provider = $this->findPluginProvider($root.'/plugins/'.$slug);
            if ($provider === null) {
                continue;
            }

            $source = (string) file_get_contents($provider);
            if (preg_match_all("/new\\s+PluginDependency\\(\\s*'([^']+)'/", $source, $matches)) {
                $map[$slug] = array_values(array_unique($matches[1]));
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    public function importedNamespaces(string $path): array
    {
        return $this->namespacesFromSource((string) file_get_contents($path));
    }

    /**
     * Collect imported namespaces from `use` statements only.
     *
     * String FQCNs (e.g. morph map aliases) are intentional Core→plugin
     * decoupling and are not imports.
     *
     * @return list<string>
     */
    public function namespacesFromSource(string $source): array
    {
        $imports = [];

        if (preg_match_all('/^use\s+([^;\s]+)\s*;/m', $source, $matches)) {
            foreach ($matches[1] as $imported) {
                // Skip use-function / use-const and aliased group imports without namespace.
                if (str_starts_with($imported, 'function ') || str_starts_with($imported, 'const ')) {
                    continue;
                }

                $imports[] = ltrim($imported, '\\');
            }
        }

        return array_values(array_unique($imports));
    }

    /**
     * @return list<string>
     */
    public function pluginSlugs(string $root): array
    {
        return $this->childDirectoryNames($root.'/plugins');
    }

    /**
     * @return list<string>
     */
    public function moduleSlugs(string $root): array
    {
        return $this->childDirectoryNames($root.'/modules');
    }

    public function studly(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
    }

    public function kebab(string $studly): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $studly));
    }

    /**
     * Known module slugs (for documentation / allowlists).
     *
     * @return list<string>
     */
    public function knownModuleSlugs(): array
    {
        return self::MODULE_SLUGS;
    }

    private function isPublicPluginApi(string $imported, string $otherStudly): bool
    {
        $prefix = 'Plugins\\'.$otherStudly.'\\';
        if (! str_starts_with($imported, $prefix)) {
            return $imported === 'Plugins\\'.$otherStudly;
        }

        $remainder = substr($imported, strlen($prefix));

        return str_starts_with($remainder, 'Contracts\\')
            || $remainder === 'Contracts'
            || str_starts_with($remainder, 'Models\\')
            || $remainder === 'Models'
            || str_starts_with($remainder, 'Support\\')
            || $remainder === 'Support';
    }

    private function slugFromStudly(string $studly, string $root): ?string
    {
        foreach ($this->pluginSlugs($root) as $slug) {
            if ($this->studly($slug) === $studly) {
                return $slug;
            }
        }

        return $this->kebab($studly);
    }

    private function findPluginProvider(string $pluginRoot): ?string
    {
        foreach ($this->phpFiles($pluginRoot.'/src') as $file) {
            if (str_ends_with($file, 'ServiceProvider.php')) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function childDirectoryNames(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $names = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($directory.'/'.$entry)) {
                $names[] = $entry;
            }
        }

        sort($names);

        return $names;
    }

    private function msg(string $root, string $file, string $message): string
    {
        $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));

        return "{$relative}: {$message}";
    }
}
