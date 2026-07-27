<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class AccessCompatibilitySourceContractTest extends TestCase
{
    #[DataProvider('applicationSourceProvider')]
    public function test_application_sources_do_not_access_users_role_directly(string $path): void
    {
        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertDoesNotMatchRegularExpression(
            '/->role\b|(?:^|[^A-Za-z])where(?:In)?\(\s*[\'"]role[\'"]|[\'"]role[\'"]\s*=>\s*(?:User::ROLE_|\$role\b)/m',
            $source,
            "Direct users.role access remains in [{$this->relativePath($path)}].",
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function applicationSourceProvider(): iterable
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            ...self::phpFiles($root.DIRECTORY_SEPARATOR.'app'),
            ...self::phpFiles($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'),
            ...self::phpFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'),
        ];

        $allowed = [
            $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'User.php',
            $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Commands'
                .DIRECTORY_SEPARATOR.'SyncAccessCompatibility.php',
        ];

        foreach ($paths as $path) {
            if (! in_array($path, $allowed, true)) {
                yield self::relativePath($path) => [$path];
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private static function relativePath(string $path): string
    {
        return str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($path, strlen(dirname(__DIR__, 2)) + 1),
        );
    }
}
