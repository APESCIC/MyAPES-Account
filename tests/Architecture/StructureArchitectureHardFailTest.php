<?php

namespace Tests\Architecture;

use App\Support\ArchitectureDependencyScanner;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hard-fail architecture suite for #294.
 *
 * Rules (see docs/architecture.md):
 * 1. Core must not import Modules\* or Plugins\*
 * 2. Plugins must not import Modules\*
 * 3. Plugins may import other plugins only when declared in PluginDependency,
 *    and only via public API (Contracts, Models, Support)
 * 4. Modules must not import other modules
 * 5. Modules may import plugins only via Contracts\*
 * 6. Legacy feature controller / dashboard folders stay empty
 */
#[Group('architecture')]
class StructureArchitectureHardFailTest extends TestCase
{
    public function test_repository_passes_architecture_dependency_rules(): void
    {
        $scanner = new ArchitectureDependencyScanner;
        $violations = $scanner->scanRepository(dirname(__DIR__, 2));

        $this->assertSame(
            [],
            $violations,
            "Architecture violations:\n- ".implode("\n- ", $violations),
        );
    }

    public function test_layout_trees_exist(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            $root.'/app/Core',
            $root.'/modules/apes-cic',
            $root.'/modules/pet-care-clinic',
            $root.'/modules/shelter-rescue',
            $root.'/plugins/tickets',
            $root.'/plugins/cases',
            $root.'/plugins/recruitment',
            $root.'/plugins/consultations',
            $root.'/plugins/pet-profiles',
        ] as $path) {
            $this->assertDirectoryExists($path);
        }
    }
}
