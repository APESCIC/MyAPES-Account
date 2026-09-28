<?php

namespace Tests\Architecture;

use App\Support\ArchitectureDependencyScanner;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Fixture proofs that each #294 rule catches a violation.
 */
#[Group('architecture')]
class StructureArchitectureRuleFixturesTest extends TestCase
{
    public function test_core_import_of_modules_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalCoreImports([
            "<?php\nnamespace App\\Core\\Fake;\nuse Modules\\ApesCic\\ApesCicServiceProvider;\n",
        ]);

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('Modules\\ApesCic', $violations[0]);
    }

    public function test_core_import_of_plugins_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalCoreImports([
            "<?php\nnamespace App\\Core\\Fake;\nuse Plugins\\Tickets\\TicketsServiceProvider;\n",
        ]);

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('Plugins\\Tickets', $violations[0]);
    }

    public function test_plugin_import_of_modules_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalPluginImports(
            'tickets',
            "<?php\nnamespace Plugins\\Tickets;\nuse Modules\\ApesCic\\ApesCicServiceProvider;\n",
            [],
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('Modules\\ApesCic', $violations[0]);
    }

    public function test_undeclared_plugin_dependency_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalPluginImports(
            'tickets',
            "<?php\nnamespace Plugins\\Tickets;\nuse Plugins\\PetProfiles\\Contracts\\PetProfilesContract;\n",
            [],
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('undeclared dependency', $violations[0]);
    }

    public function test_declared_dependency_non_public_api_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalPluginImports(
            'consultations',
            "<?php\nnamespace Plugins\\Consultations;\nuse Plugins\\PetProfiles\\Http\\Controllers\\PetProfileController;\n",
            ['pet-profiles'],
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('non-public API', $violations[0]);
    }

    public function test_declared_dependency_contracts_are_allowed(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalPluginImports(
            'consultations',
            "<?php\nnamespace Plugins\\Consultations;\nuse Plugins\\PetProfiles\\Contracts\\PetProfilesContract;\n",
            ['pet-profiles'],
        );

        $this->assertSame([], $violations);
    }

    public function test_module_to_module_import_is_caught(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->findIllegalModuleToModuleImports(
            'apes-cic',
            "<?php\nnamespace Modules\\ApesCic;\nuse Modules\\ShelterRescue\\ShelterRescueServiceProvider;\n",
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('Modules\\ShelterRescue', $violations[0]);
    }
}
