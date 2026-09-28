<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Shared scaffolding for make:module / make:plugin (#295).
 *
 * Edits package trees, composer.json PSR-4, and bootstrap/providers.php.
 * Never writes under app/Core.
 */
final class PackageScaffold
{
    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? base_path();
    }

    public function studly(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    public function assertValidSlug(string $slug): void
    {
        if (! preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', $slug)) {
            throw new RuntimeException(
                "Slug [{$slug}] must be lowercase kebab-case (e.g. pet-care-clinic).",
            );
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function writeStub(string $stubRelative, string $destination, array $replacements): void
    {
        $stubPath = $this->root.'/stubs/'.$stubRelative;
        if (! is_file($stubPath)) {
            throw new RuntimeException("Missing stub [{$stubRelative}].");
        }

        $contents = strtr((string) file_get_contents($stubPath), $replacements);
        $this->ensureDirectory(dirname($destination));
        if (file_put_contents($destination, $contents) === false) {
            throw new RuntimeException("Unable to write [{$destination}].");
        }
    }

    public function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException("Unable to create directory [{$path}].");
        }
    }

    public function touchGitkeep(string $directory): void
    {
        $this->ensureDirectory($directory);
        $path = $directory.'/.gitkeep';
        if (! is_file($path)) {
            file_put_contents($path, '');
        }
    }

    public function addComposerPsr4(string $namespace, string $path): void
    {
        $composerPath = $this->root.'/composer.json';
        /** @var array<string, mixed>|null $json */
        $json = json_decode((string) file_get_contents($composerPath), true);
        if (! is_array($json)) {
            throw new RuntimeException('Unable to parse composer.json.');
        }

        /** @var array<string, string> $psr4 */
        $psr4 = is_array($json['autoload']['psr-4'] ?? null) ? $json['autoload']['psr-4'] : [];

        if (isset($psr4[$namespace])) {
            return;
        }

        $psr4[$namespace] = $path;
        ksort($psr4);
        $json['autoload']['psr-4'] = $psr4;

        $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        file_put_contents($composerPath, $encoded);
    }

    public function addProvider(string $fqcn): void
    {
        $providersPath = $this->root.'/bootstrap/providers.php';
        $source = (string) file_get_contents($providersPath);
        $short = class_basename($fqcn);

        if (str_contains($source, "{$short}::class")) {
            return;
        }

        if (! str_contains($source, "use {$fqcn};")) {
            $inserted = preg_replace(
                '/(<\?php\n\n)/',
                "$1use {$fqcn};\n",
                $source,
                1,
            );
            $source = is_string($inserted) ? $inserted : $source;
        }

        $inserted = preg_replace(
            '/(\n\];\s*)$/',
            "\n    {$short}::class,\n];\n",
            $source,
            1,
        );

        if (! is_string($inserted) || ! str_contains($inserted, "{$short}::class")) {
            throw new RuntimeException('Unable to update bootstrap/providers.php.');
        }

        file_put_contents($providersPath, $inserted);
    }

    public function root(): string
    {
        return $this->root;
    }
}
