<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @deprecated Replaced by StructureArchitectureHardFailTest (#294 Wave 8).
 * Kept as a thin alias so older docs that cite the report group still resolve.
 */
#[Group('architecture')]
class StructureLayoutBaselineTest extends TestCase
{
    public function test_defers_to_hard_fail_suite(): void
    {
        $this->assertTrue(class_exists(StructureArchitectureHardFailTest::class));
    }
}
