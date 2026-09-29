<?php

namespace App\Services\Localisation;

use Illuminate\Support\Facades\Lang;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Scans source for literal translation keys and compares them to en_GB (#276).
 * Reuses inventory roots concepts from {@see HardCodedStringScanner} (#266).
 */
class TranslationKeyChecker
{
    /**
     * @return array{
     *     used_keys: list<string>,
     *     missing_keys: list<array{key: string, file: string, line: int}>,
     *     unused_keys: list<string>,
     *     defined_keys: list<string>,
     *     hard_coded_count: int
     * }
     */
    public function check(?string $basePath = null): array
    {
        $base = $basePath ?? base_path();
        $locale = (string) config('lang_check.locale', 'en_GB');
        $roots = config('lang_check.scan_roots', [
            'app',
            'resources/views',
            'routes',
            'plugins',
            'modules',
        ]);

        $usages = [];
        foreach ($roots as $root) {
            $absolute = $base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root);
            if (! is_dir($absolute)) {
                continue;
            }
            $usages = array_merge($usages, $this->scanDirectory($absolute, $base));
        }

        $missing = [];
        $usedKeys = [];
        foreach ($usages as $usage) {
            $key = $usage['key'];
            $usedKeys[$key] = true;
            if (! Lang::has($key, $locale, false)) {
                $missing[] = $usage;
            }
        }

        $defined = $this->definedKeys($base, $locale);
        $unused = [];
        foreach ($defined as $definedKey) {
            if (! isset($usedKeys[$definedKey]) && ! $this->isAllowlistedDefinedKey($definedKey)) {
                $unused[] = $definedKey;
            }
        }
        sort($unused);

        $hardCodedCount = 0;
        if ($basePath === null) {
            try {
                $hardCodedCount = count((new HardCodedStringScanner)->inventoryFindings($base));
            } catch (\Throwable) {
                $hardCodedCount = 0;
            }
        }

        $usedList = array_keys($usedKeys);
        sort($usedList);

        return [
            'used_keys' => $usedList,
            'missing_keys' => $missing,
            'unused_keys' => $unused,
            'defined_keys' => $defined,
            'hard_coded_count' => $hardCodedCount,
        ];
    }

    /**
     * @return list<array{key: string, file: string, line: int}>
     */
    private function scanDirectory(string $absolute, string $base): array
    {
        $findings = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['php', 'blade.php'], true) && ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = ltrim(str_replace($base, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);

            if ($this->shouldIgnoreRelativePath($relative)) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            $findings = array_merge($findings, $this->extractKeysFromContents($contents, $relative));
        }

        return $findings;
    }

    /**
     * @return list<array{key: string, file: string, line: int}>
     */
    public function extractKeysFromContents(string $contents, string $relativePath): array
    {
        $patterns = [
            '/__\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/\btrans\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/\btrans_choice\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/@lang\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/Lang::(?:get|has|choice)\(\s*[\'"]([^\'"]+)[\'"]/u',
        ];

        $findings = [];
        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as $match) {
                [$key, $offset] = $match;
                if ($key === '' || str_contains($key, '$') || str_contains($key, '{')) {
                    continue;
                }

                $line = substr_count(substr($contents, 0, (int) $offset), "\n") + 1;
                $findings[] = [
                    'key' => $key,
                    'file' => $relativePath,
                    'line' => $line,
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<string>
     */
    private function definedKeys(string $base, string $locale): array
    {
        $keys = [];
        $paths = [
            $base.'/lang/'.$locale,
            $base.'/lang/vendor',
        ];

        foreach (glob($base.'/plugins/*/lang/'.$locale) ?: [] as $pluginLang) {
            $paths[] = $pluginLang;
        }
        foreach (glob($base.'/modules/*/lang/'.$locale) ?: [] as $moduleLang) {
            $paths[] = $moduleLang;
        }

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $keys = array_merge($keys, $this->keysFromLangDirectory($path, $base));
        }

        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function keysFromLangDirectory(string $directory, string $base): array
    {
        $keys = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', ltrim(str_replace($base, '', $file->getPathname()), DIRECTORY_SEPARATOR));
            $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $namespace = $this->namespaceFromRelativePath($relative);

            /** @var mixed $payload */
            $payload = require $file->getPathname();
            if (! is_array($payload)) {
                continue;
            }

            foreach ($this->flattenKeys($payload) as $dotKey) {
                $full = $namespace === null
                    ? $group.'.'.$dotKey
                    : $namespace.'::'.$group.'.'.$dotKey;
                $keys[] = $full;
            }
        }

        return $keys;
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<string>
     */
    private function flattenKeys(array $payload, string $prefix = ''): array
    {
        $keys = [];
        foreach ($payload as $key => $value) {
            $segment = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $segment));
            } else {
                $keys[] = $segment;
            }
        }

        return $keys;
    }

    private function namespaceFromRelativePath(string $relative): ?string
    {
        if (preg_match('#^plugins/([^/]+)/lang/#', $relative, $matches) === 1) {
            return str_replace('-', '_', $matches[1]);
        }

        if (preg_match('#^modules/([^/]+)/lang/#', $relative, $matches) === 1) {
            return str_replace('-', '_', $matches[1]);
        }

        if (preg_match('#^lang/vendor/([^/]+)/#', $relative, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function shouldIgnoreRelativePath(string $relative): bool
    {
        $ignores = config('lang_check.ignore_path_prefixes', [
            'vendor/',
            'node_modules/',
            'storage/',
            'bootstrap/cache/',
            'tests/',
        ]);

        foreach ($ignores as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowlistedDefinedKey(string $key): bool
    {
        $allow = config('lang_check.unused_allowlist', [
            'terms.',
            'validation.',
            'auth.',
            'passwords.',
            'pagination.',
            '::plugin.',
        ]);

        foreach ($allow as $prefix) {
            if (str_starts_with($key, $prefix) || str_contains($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
