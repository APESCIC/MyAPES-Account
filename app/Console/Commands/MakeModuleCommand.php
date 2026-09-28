<?php

namespace App\Console\Commands;

use App\Support\PackageScaffold;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {slug : Module slug (kebab-case, e.g. community-hub)}
        {--name= : Human display name}
        {--prefix= : Live URL prefix including leading slash (e.g. /community)}
        {--route-name= : Route name prefix without trailing dot (defaults from prefix)}
        {--force : Overwrite an existing package directory}';

    protected $description = 'Scaffold a module package under modules/<slug> without editing Core (#295)';

    public function handle(): int
    {
        $scaffold = new PackageScaffold;
        $slug = strtolower(trim((string) $this->argument('slug')));

        try {
            $scaffold->assertValidSlug($slug);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $studly = $scaffold->studly($slug);
        $name = trim((string) ($this->option('name') ?: Str::headline($slug)));
        $prefix = trim((string) ($this->option('prefix') ?: '/'.$slug));
        if (! str_starts_with($prefix, '/')) {
            $prefix = '/'.$prefix;
        }
        $routeName = trim((string) ($this->option('route-name') ?: ltrim(str_replace('/', '.', $prefix), '.')));
        $routeName = rtrim($routeName, '.');

        $packagePath = $scaffold->root().'/modules/'.$slug;
        if (is_dir($packagePath) && ! $this->option('force')) {
            $this->components->error("Module package already exists at [modules/{$slug}]. Use --force to overwrite.");

            return self::FAILURE;
        }

        $replacements = [
            '{{ slug }}' => $slug,
            '{{ studly }}' => $studly,
            '{{ name }}' => $name,
            '{{ prefix }}' => $prefix,
            '{{ routeName }}' => $routeName,
            '{{ hubRoute }}' => $routeName.'.index',
        ];

        try {
            $scaffold->writeStub('module/module.php.stub', $packagePath.'/module.php', $replacements);
            $scaffold->writeStub(
                'module/ServiceProvider.php.stub',
                $packagePath.'/src/'.$studly.'ServiceProvider.php',
                $replacements,
            );
            $scaffold->writeStub('module/README.md.stub', $packagePath.'/README.md', $replacements);
            $scaffold->writeStub(
                'module/routes.php.stub',
                $packagePath.'/routes/web.php',
                $replacements,
            );
            $scaffold->writeStub(
                'module/hub.blade.php.stub',
                $packagePath.'/resources/views/hub.blade.php',
                $replacements,
            );
            $scaffold->writeStub(
                'module/lang.php.stub',
                $packagePath.'/lang/en_GB/module.php',
                $replacements,
            );
            $scaffold->writeStub(
                'module/FeatureTest.php.stub',
                $packagePath.'/tests/ModulePackageTest.php',
                $replacements,
            );

            foreach (['config', 'database/migrations', 'lang', 'resources/views', 'routes', 'tests'] as $dir) {
                $scaffold->ensureDirectory($packagePath.'/'.$dir);
            }

            $scaffold->addComposerPsr4("Modules\\{$studly}\\", "modules/{$slug}/src/");
            $scaffold->addProvider("Modules\\{$studly}\\{$studly}ServiceProvider");
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Module [{$slug}] scaffolded at modules/{$slug}.");
        $this->line('Next:');
        $this->line('  1. composer dump-autoload');
        $this->line('  2. Wire the hub route from routes/web.php (live prefix stays '.$prefix.')');
        $this->line('  3. Compose plugins in module.php → plugins: []');
        $this->line('  4. php artisan test --compact tests/Architecture');
        $this->components->warn('No files under app/Core were modified.');

        return self::SUCCESS;
    }
}
