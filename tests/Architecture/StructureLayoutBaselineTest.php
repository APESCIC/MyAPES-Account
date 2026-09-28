<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Report-only architecture baseline for #294 (folded lightly into #281).
 *
 * These checks document the intended layout. They do not fail CI yet — hard-fail
 * lands when Structure moves are complete (Wave 8).
 */
#[Group('architecture-report')]
class StructureLayoutBaselineTest extends TestCase
{
    public function test_reports_core_modules_and_plugins_trees_exist(): void
    {
        $root = dirname(__DIR__, 2);
        $missing = [];

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
            if (! is_dir($path)) {
                $missing[] = $path;
            }
        }

        // Soft assertion: print gaps without failing the suite when empty.
        if ($missing !== []) {
            fwrite(STDERR, "[architecture-report] Missing layout paths:\n- ".implode("\n- ", $missing)."\n");
        }

        $this->addToAssertionCount(1);
    }
}
