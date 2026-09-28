<?php

namespace App\Console\Commands;

use App\Support\PackageScaffold;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MakePluginCommand extends Command
{
    protected $signature = 'make:plugin
        {slug : Plugin slug (kebab-case, e.g. demo-board)}
        {--name= : Human display name}
        {--modules= : Comma-separated compatible/shipped module slugs}
        {--depends= : Comma-separated plugin dependency slugs}
        {--force : Overwrite an existing package directory}';

    protected $description = 'Scaffold a plugin package under plugins/<slug> without editing Core (#295)';

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
        $modules = $this->csvOption('modules') ?: ['apes-cic'];
        $depends = $this->csvOption('depends');

        foreach ([...$modules, ...$depends] as $ref) {
            try {
                $scaffold->assertValidSlug($ref);
            } catch (RuntimeException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $packagePath = $scaffold->root().'/plugins/'.$slug;
        if (is_dir($packagePath) && ! $this->option('force')) {
            $this->components->error("Plugin package already exists at [plugins/{$slug}]. Use --force to overwrite.");

            return self::FAILURE;
        }

        $model = $studly;
        $controller = $studly.'Controller';
        $policy = $studly.'Policy';
        $translationNamespace = str_replace('-', '_', $slug);

        $modulesExport = $this->phpStringList($modules);
        $dependsBlock = $this->dependencyBlock($depends);
        $dependencyImport = $depends === []
            ? ''
            : "use App\\Core\\Extensions\\Plugins\\PluginDependency;\n";
        $settingsBlock = $this->settingsBlock($modules);
        $navBlock = $this->navigationBlock($modules, $slug, $name);
        $routeRequires = $this->routeFileList($modules, $slug);
        $primaryModule = $modules[0];
        $primaryPrefix = $this->moduleRoutePrefix($primaryModule);
        $primaryRouteName = $this->moduleRouteName($primaryModule);

        $replacements = [
            '{{ slug }}' => $slug,
            '{{ studly }}' => $studly,
            '{{ name }}' => $name,
            '{{ model }}' => $model,
            '{{ controller }}' => $controller,
            '{{ policy }}' => $policy,
            '{{ translationNamespace }}' => $translationNamespace,
            '{{ modulesList }}' => $modulesExport,
            '{{ dependenciesBlock }}' => $dependsBlock,
            '{{ dependencyImport }}' => $dependencyImport,
            '{{ settingsBlock }}' => $settingsBlock,
            '{{ navigationBlock }}' => $navBlock,
            '{{ staffRouteFiles }}' => $routeRequires,
            '{{ primaryModule }}' => $primaryModule,
            '{{ primaryPrefix }}' => $primaryPrefix,
            '{{ primaryRouteName }}' => $primaryRouteName,
            '{{ table }}' => str_replace('-', '_', $slug),
        ];

        try {
            $scaffold->writeStub(
                'plugin/ServiceProvider.php.stub',
                $packagePath.'/src/'.$studly.'ServiceProvider.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/Controller.php.stub',
                $packagePath.'/src/Http/Controllers/'.$controller.'.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/Model.php.stub',
                $packagePath.'/src/Models/'.$model.'.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/Policy.php.stub',
                $packagePath.'/src/Policies/'.$policy.'.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/ActiveRecordDetector.php.stub',
                $packagePath.'/src/Dashboard/'.$studly.'ActiveRecordDetector.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/migration.php.stub',
                $packagePath.'/database/migrations/'.date('Y_m_d_His').'_create_'.$replacements['{{ table }}'].'_table.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/factory.php.stub',
                $packagePath.'/database/factories/'.$model.'Factory.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/index.blade.php.stub',
                $packagePath.'/resources/views/index.blade.php',
                $replacements,
            );
            $scaffold->writeStub(
                'plugin/lang.php.stub',
                $packagePath.'/lang/en_GB/plugin.php',
                $replacements,
            );
            $scaffold->writeStub('plugin/README.md.stub', $packagePath.'/README.md', $replacements);
            $scaffold->writeStub(
                'plugin/FeatureTest.php.stub',
                $packagePath.'/tests/PluginPackageTest.php',
                $replacements,
            );

            foreach ($modules as $moduleSlug) {
                $routeFile = $this->moduleRouteFile($moduleSlug);
                $scaffold->writeStub(
                    'plugin/routes.php.stub',
                    $packagePath.'/routes/'.$routeFile,
                    array_merge($replacements, [
                        '{{ moduleSlug }}' => $moduleSlug,
                        '{{ routePrefix }}' => $this->moduleRoutePrefix($moduleSlug),
                        '{{ routeName }}' => $this->moduleRouteName($moduleSlug),
                    ]),
                );
            }

            foreach (['config', 'database/migrations', 'database/factories', 'lang', 'resources/views', 'routes', 'tests', 'src/Contracts', 'src/Support'] as $dir) {
                $scaffold->ensureDirectory($packagePath.'/'.$dir);
            }
            $scaffold->touchGitkeep($packagePath.'/src/Contracts');
            $scaffold->touchGitkeep($packagePath.'/src/Support');

            $scaffold->addComposerPsr4("Plugins\\{$studly}\\", "plugins/{$slug}/src/");
            $scaffold->addProvider("Plugins\\{$studly}\\{$studly}ServiceProvider");
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Plugin [{$slug}] scaffolded at plugins/{$slug}.");
        $this->line('Next:');
        $this->line('  1. composer dump-autoload');
        $this->line('  2. Require plugin routes from routes/web.php under each module group');
        $this->line('  3. Add the plugin slug to each module.php → plugins list when composing');
        $this->line('  4. php artisan test --compact tests/Architecture');
        $this->components->warn('No files under app/Core were modified.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function csvOption(string $name): array
    {
        $raw = trim((string) $this->option($name));
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $part): string => strtolower(trim($part)),
            explode(',', $raw),
        )));
    }

    /**
     * @param  list<string>  $values
     */
    private function phpStringList(array $values): string
    {
        return '['.implode(', ', array_map(
            static fn (string $value): string => "'{$value}'",
            $values,
        )).']';
    }

    /**
     * @param  list<string>  $depends
     */
    private function dependencyBlock(array $depends): string
    {
        if ($depends === []) {
            return '';
        }

        $lines = ['            dependencies: ['];
        foreach ($depends as $dep) {
            $lines[] = "                new PluginDependency('{$dep}'),";
        }
        $lines[] = '            ],';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<string>  $modules
     */
    private function settingsBlock(array $modules): string
    {
        $lines = [];
        foreach ($modules as $module) {
            $lines[] = "                '{$module}' => new PluginSettingsSchema(supportsSettings: false),";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $modules
     */
    private function navigationBlock(array $modules, string $slug, string $name): string
    {
        $lines = [];
        foreach ($modules as $module) {
            $route = $this->moduleRouteName($module).'.'.$slug.'.index';
            $lines[] = "                new PluginNavigationItem('{$name}', '{$route}', 'puzzle', 50, moduleSlug: '{$module}'),";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $modules
     */
    private function routeFileList(array $modules, string $slug): string
    {
        $lines = [];
        foreach ($modules as $module) {
            $file = $this->moduleRouteFile($module);
            $lines[] = "                dirname(__DIR__).'/routes/{$file}',";
        }

        return implode("\n", $lines);
    }

    private function moduleRouteFile(string $moduleSlug): string
    {
        return match ($moduleSlug) {
            'shelter-rescue' => 'shelter.php',
            'pet-care-clinic' => 'petcare.php',
            default => $moduleSlug.'.php',
        };
    }

    private function moduleRoutePrefix(string $moduleSlug): string
    {
        return match ($moduleSlug) {
            'shelter-rescue' => '/shelter',
            'pet-care-clinic' => '/petcare',
            'apes-cic' => '/apes-cic',
            default => '/'.$moduleSlug,
        };
    }

    private function moduleRouteName(string $moduleSlug): string
    {
        return match ($moduleSlug) {
            'shelter-rescue' => 'shelter',
            'pet-care-clinic' => 'petcare',
            'apes-cic' => 'apes-cic',
            default => $moduleSlug,
        };
    }
}
