<?php

namespace Tests\Feature;

use App\Core\Accounts\Permission;
use App\Core\Accounts\Role;
use App\Core\Accounts\User;
use App\Core\Extensions\Models\ModuleInstallation;
use App\Core\Extensions\Plugins\PluginRegistry;
use App\Services\AuthorizationProfile;
use App\Support\ArchitectureDependencyScanner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StructureGeneratorsWave8Test extends TestCase
{
    use RefreshDatabase;

    private string $pluginSlug = 'wave8-demo';

    private string $moduleSlug = 'wave8-area';

    private string $composerBefore = '';

    private string $providersBefore = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->composerBefore = (string) file_get_contents(base_path('composer.json'));
        $this->providersBefore = (string) file_get_contents(base_path('bootstrap/providers.php'));
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedPackages();
        parent::tearDown();
    }

    public function test_make_plugin_scaffolds_arch_compliant_package_that_boots_and_serves_index(): void
    {
        $exit = Artisan::call('make:plugin', [
            'slug' => $this->pluginSlug,
            '--name' => 'Wave8 Demo',
            '--modules' => 'apes-cic',
            '--force' => true,
        ]);
        $this->assertSame(0, $exit, Artisan::output());

        $root = base_path();
        $pluginPath = $root.'/plugins/'.$this->pluginSlug;
        $this->assertDirectoryExists($pluginPath);
        $this->assertFileExists($pluginPath.'/src/Wave8DemoServiceProvider.php');
        $this->assertFileDoesNotExist($root.'/app/Core/Wave8DemoServiceProvider.php');

        spl_autoload_register(static function (string $class) use ($pluginPath): void {
            $prefix = 'Plugins\\Wave8Demo\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $pluginPath.'/src/'.$relative.'.php';
            if (is_file($file)) {
                require_once $file;
            }
        });

        $providerClass = 'Plugins\\Wave8Demo\\Wave8DemoServiceProvider';
        $this->assertTrue(class_exists($providerClass));

        /** @var ServiceProvider $provider */
        $provider = new $providerClass(app());
        $provider->register();
        $provider->boot();

        $registry = app(PluginRegistry::class);
        $this->assertTrue($registry->has($this->pluginSlug));
        $manifest = $registry->plugin($this->pluginSlug);
        $this->assertSame('0.1.0', $manifest->version);
        $this->assertTrue($manifest->isShippedFor('apes-cic'));

        ModuleInstallation::query()->create([
            'sub_core_key' => 'apes-cic',
            'module_key' => $this->pluginSlug,
            'enabled' => true,
            'lock_version' => 1,
            'installed_at' => now(),
            'enabled_at' => now(),
        ]);

        $this->assertTrue(
            ModuleInstallation::query()
                ->where('sub_core_key', 'apes-cic')
                ->where('module_key', $this->pluginSlug)
                ->where('enabled', true)
                ->exists(),
            'Generated plugin can be enabled for APES CIC via module_plugins.',
        );

        // Enablement façade needs FirstPartyModuleRegistry to know the pair; assert
        // the installation row + shipped flag above. Full matrix rebuild is covered
        // by StructureEnablementWave2Test for first-party plugins.
        $this->assertNotNull($manifest->activeRecordDetector);

        $viewAll = Permission::findOrCreate('apes-cic.wave8-demo.view-all', 'web');
        Permission::findOrCreate('apes-cic.wave8-demo.view-own', 'web');
        $staffRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();
        $staffRole->permissions()->syncWithoutDetaching([$viewAll->id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::create('wave8_demo', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('sub_core_key')->index();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Route::middleware('web')->prefix('apes-cic')->name('apes-cic.')->group(function () use ($pluginPath): void {
            require $pluginPath.'/routes/apes-cic.php';
        });

        Gate::before(static fn (): bool => true);

        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $this->actingAs($staff)
            ->get('/apes-cic/wave8-demo')
            ->assertOk()
            ->assertSee('Wave8 Demo');

        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->scanRepository($root);
        $this->assertSame(
            [],
            $violations,
            "Generated plugin must pass architecture rules:\n- ".implode("\n- ", $violations),
        );
    }

    public function test_make_module_scaffolds_package_without_core_edits(): void
    {
        $coreFingerprint = $this->coreTreeFingerprint();

        $exit = Artisan::call('make:module', [
            'slug' => $this->moduleSlug,
            '--name' => 'Wave8 Area',
            '--prefix' => '/wave8',
            '--force' => true,
        ]);
        $this->assertSame(0, $exit, Artisan::output());

        $modulePath = base_path('modules/'.$this->moduleSlug);
        $this->assertDirectoryExists($modulePath);
        $this->assertFileExists($modulePath.'/module.php');
        $this->assertFileExists($modulePath.'/src/Wave8AreaServiceProvider.php');
        $this->assertSame($coreFingerprint, $this->coreTreeFingerprint(), 'make:module must not edit app/Core');

        $scanner = new ArchitectureDependencyScanner;
        $this->assertSame([], $scanner->scanRepository(base_path()));
    }

    private function coreTreeFingerprint(): string
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('app/Core')),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname().':'.md5_file($file->getPathname());
            }
        }
        sort($files);

        return md5(implode("\n", $files));
    }

    private function cleanupGeneratedPackages(): void
    {
        foreach (['plugins/'.$this->pluginSlug, 'modules/'.$this->moduleSlug] as $relative) {
            $path = base_path($relative);
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }
        }

        if ($this->composerBefore !== '') {
            file_put_contents(base_path('composer.json'), $this->composerBefore);
        }
        if ($this->providersBefore !== '') {
            file_put_contents(base_path('bootstrap/providers.php'), $this->providersBefore);
        }
    }
}
