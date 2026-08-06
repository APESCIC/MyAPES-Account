<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleRuntimeSourceContractTest extends TestCase
{
    public function test_runtime_module_installation_writes_stay_inside_the_lifecycle_boundary(): void
    {
        $allowed = [
            realpath(app_path('Services/DatabaseModuleLifecycleManager.php')),
            realpath(app_path('Services/ModuleInstallationSynchronizer.php')),
        ];
        $violations = [];

        foreach (File::allFiles(app_path()) as $file) {
            $path = $file->getRealPath();
            $contents = File::get($path);
            $hasRawMutation = str_contains(
                $contents,
                "DB::table('module_installations')",
            );
            $hasModelMutation = str_contains(
                $contents,
                'ModuleInstallation',
            ) && preg_match(
                '/->(?:save|update|delete|forceDelete|restore)\s*\(|ModuleInstallation::(?:create|destroy|insert|upsert|updateOrCreate)\s*\(/',
                $contents,
            ) === 1;

            if (($hasRawMutation || $hasModelMutation)
                && ! in_array($path, $allowed, true)) {
                $violations[] = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    $file->getRelativePathname(),
                );
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_no_runtime_route_or_controller_exposes_module_removal(): void
    {
        $sources = File::get(base_path('routes/web.php'));
        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $sources .= "\n".File::get($file->getRealPath());
        }

        $this->assertDoesNotMatchRegularExpression(
            '/modules[^\r\n]*(?:uninstall|destroy|delete)|(?:uninstall|destroy|delete)[^\r\n]*module/i',
            $sources,
        );
        $this->assertStringNotContainsString(
            "Rule::in(['install', 'enable', 'disable',",
            $sources,
        );
    }

    public function test_database_state_cannot_select_executable_module_code(): void
    {
        $migration = File::get(base_path(
            'database/migrations/2026_08_06_000000_create_module_installations_table.php',
        ));

        foreach ([
            'provider_class',
            'detector_class',
            'controller_class',
            'executable_path',
        ] as $unsafeColumn) {
            $this->assertStringNotContainsString($unsafeColumn, $migration);
        }
    }

    public function test_registry_navigation_icons_are_registered_with_the_frontend_bundle(): void
    {
        $frontend = File::get(resource_path('js/app.js'));

        foreach (['Building2', 'HeartPulse'] as $icon) {
            $this->assertGreaterThanOrEqual(
                2,
                substr_count($frontend, $icon),
                "{$icon} must be imported and passed to createIcons.",
            );
        }
    }
}
