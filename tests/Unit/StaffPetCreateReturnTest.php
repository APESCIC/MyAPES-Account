<?php

namespace Tests\Unit;

use App\Support\StaffPetCreateReturn;
use PHPUnit\Framework\TestCase;

class StaffPetCreateReturnTest extends TestCase
{
    public function test_only_named_create_flows_are_allowed_return_keys(): void
    {
        $this->assertTrue(StaffPetCreateReturn::isAllowed('shelter.cases'));
        $this->assertTrue(StaffPetCreateReturn::isAllowed('petcare.consultations'));
        $this->assertFalse(StaffPetCreateReturn::isAllowed('https://example.invalid'));
        $this->assertFalse(StaffPetCreateReturn::isAllowed('/shelter/cases'));
        $this->assertFalse(StaffPetCreateReturn::isAllowed(null));
        $this->assertFalse(StaffPetCreateReturn::isAllowed(['shelter.cases']));
        $this->assertNull(StaffPetCreateReturn::requestedKey('https://example.invalid'));
        $this->assertSame(
            StaffPetCreateReturn::SHELTER_CASES,
            StaffPetCreateReturn::requestedKey('shelter.cases'),
        );
    }
}
