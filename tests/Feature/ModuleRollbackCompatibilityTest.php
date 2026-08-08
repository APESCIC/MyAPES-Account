<?php

namespace Tests\Feature;

use App\Exceptions\ModuleLifecycleException;
use App\Models\ModuleInstallation;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleRollbackCompatibilityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleRollbackCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            if (is_dir($directory)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $directory,
                        \FilesystemIterator::SKIP_DOTS,
                    ),
                    \RecursiveIteratorIterator::CHILD_FIRST,
                );

                foreach ($files as $file) {
                    $file->isDir()
                        ? rmdir($file->getPathname())
                        : unlink($file->getPathname());
                }

                rmdir($directory);
            }
        }

        parent::tearDown();
    }

    public function test_the_source_controlled_runtime_manifest_represents_the_current_release(): void
    {
        $result = app(ModuleRollbackCompatibilityChecker::class)
            ->check(base_path());

        $this->assertSame('manifest', $result['contract']);
        $this->assertSame(5, $result['installations']);
        $this->assertSame('0.11.0', $result['target_version']);
    }

    public function test_a_legacy_target_without_a_manifest_requires_exactly_five_enabled_baselines(): void
    {
        $legacy = $this->temporaryRelease();

        $this->assertSame(
            [
                'contract' => 'legacy-v0.8.3',
                'installations' => 5,
                'target_version' => '0.8.3',
            ],
            app(ModuleRollbackCompatibilityChecker::class)->check($legacy),
        );

        $installation = ModuleInstallation::query()->firstOrFail();
        $installation->forceFill([
            'enabled' => false,
            'disabled_at' => now(),
        ])->save();

        $this->expectException(ModuleLifecycleException::class);
        $this->expectExceptionMessage('Module rollback compatibility failed.');
        app(ModuleRollbackCompatibilityChecker::class)->check($legacy);
    }

    public function test_legacy_and_manifest_targets_reject_extra_installations(): void
    {
        DB::table('module_installations')->insert([
            'sub_core_key' => 'apes-cic',
            'module_key' => 'cases',
            'enabled' => false,
            'installed_at' => now(),
            'installed_by' => null,
            'enabled_at' => null,
            'enabled_by' => null,
            'disabled_at' => now(),
            'disabled_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->temporaryRelease(), base_path()] as $target) {
            try {
                app(ModuleRollbackCompatibilityChecker::class)->check($target);
                $this->fail('An unsupported installation was accepted.');
            } catch (ModuleLifecycleException $exception) {
                $this->assertContains($exception->reason, [
                    'legacy_contract_unrepresentable',
                    'target_contract_unrepresentable',
                ]);
            }
        }
    }

    public function test_malformed_or_unsafe_runtime_manifests_fail_closed(): void
    {
        $target = $this->temporaryRelease();
        file_put_contents(
            $target.'/resources/data/module-runtime-contract.json',
            json_encode([
                'schema_version' => 1,
                'application_version' => '0.11.0',
                'shipped_instances' => ['../unsafe'],
            ], JSON_THROW_ON_ERROR),
        );

        try {
            app(ModuleRollbackCompatibilityChecker::class)->check($target);
            $this->fail('An unsafe target capability manifest was accepted.');
        } catch (ModuleLifecycleException $exception) {
            $this->assertSame('target_manifest_invalid', $exception->reason);
        }
    }

    public function test_manifest_instances_must_reference_declared_contract_keys(): void
    {
        $target = $this->temporaryRelease();
        file_put_contents(
            $target.'/resources/data/module-runtime-contract.json',
            json_encode([
                'schema_version' => 1,
                'application_version' => '0.11.0',
                'sub_cores' => [
                    'apes-cic',
                    'pet-care-clinic',
                    'shelter-rescue',
                ],
                'module_types' => [
                    'cases',
                    'consultations',
                    'pet-profiles',
                    'tickets',
                ],
                'shipped_instances' => [
                    'apes-cic:tickets',
                    'pet-care-clinic:consultations',
                    'pet-care-clinic:pet-profiles',
                    'rogue-core:tickets',
                    'shelter-rescue:cases',
                    'shelter-rescue:pet-profiles',
                ],
                'legacy_visible_instances' => [
                    'apes-cic:tickets',
                    'pet-care-clinic:consultations',
                    'pet-care-clinic:pet-profiles',
                    'shelter-rescue:cases',
                    'shelter-rescue:pet-profiles',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        try {
            app(ModuleRollbackCompatibilityChecker::class)->check($target);
            $this->fail('An undeclared manifest instance was accepted.');
        } catch (ModuleLifecycleException $exception) {
            $this->assertSame('target_manifest_invalid', $exception->reason);
        }
    }

    public function test_manifest_version_must_match_the_target_release_version(): void
    {
        $target = $this->temporaryRelease();
        copy(
            resource_path('data/module-runtime-contract.json'),
            $target.'/resources/data/module-runtime-contract.json',
        );
        file_put_contents($target.'/VERSION', "0.8.9\n");

        try {
            app(ModuleRollbackCompatibilityChecker::class)->check($target);
            $this->fail('A capability manifest with a mismatched release version was accepted.');
        } catch (ModuleLifecycleException $exception) {
            $this->assertSame('target_manifest_invalid', $exception->reason);
        }
    }

    public function test_manifest_object_key_order_does_not_change_its_meaning(): void
    {
        $target = $this->temporaryRelease();
        $manifest = json_decode(
            (string) file_get_contents(
                resource_path('data/module-runtime-contract.json'),
            ),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        file_put_contents(
            $target.'/resources/data/module-runtime-contract.json',
            json_encode([
                'application_version' => $manifest['application_version'],
                'schema_version' => $manifest['schema_version'],
                'module_types' => $manifest['module_types'],
                'sub_cores' => $manifest['sub_cores'],
                'legacy_visible_instances' => $manifest['legacy_visible_instances'],
                'shipped_instances' => $manifest['shipped_instances'],
            ], JSON_THROW_ON_ERROR),
        );
        copy(base_path('VERSION'), $target.'/VERSION');

        $this->assertSame(
            'manifest',
            app(ModuleRollbackCompatibilityChecker::class)
                ->check($target)['contract'],
        );
    }

    public function test_rollback_command_is_read_only_and_reports_only_contract_counts(): void
    {
        $before = ModuleInstallation::query()->orderBy('id')->get();

        $this->artisan('myapes:modules:rollback-check', [
            '--target-release' => base_path(),
        ])
            ->expectsOutputToContain(
                'Module rollback compatibility: ok (manifest, 5 installations)',
            )
            ->assertSuccessful();

        $this->assertEquals(
            $before,
            ModuleInstallation::query()->orderBy('id')->get(),
        );
    }

    private function temporaryRelease(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'myapes-module-rollback-'.Str::uuid();
        mkdir($path.'/resources/data', 0700, true);
        $this->temporaryDirectories[] = $path;

        return $path;
    }
}
