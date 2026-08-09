<?php

namespace Tests\Unit;

use App\Services\UkPhoneNumber;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UkPhoneNumberTest extends TestCase
{
    public function test_uk_reachable_numbers_are_stored_as_e164(): void
    {
        $normalizer = app(UkPhoneNumber::class);

        $this->assertSame('+447400123456', $normalizer->normalize('07400 123456', 'mobile_number'));
        $this->assertSame('+442079460018', $normalizer->normalize('020 7946 0018', 'landline_number'));
        $this->assertNull($normalizer->normalize(null, 'whatsapp_number', false));
    }

    public function test_non_uk_or_unreachable_numbers_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(UkPhoneNumber::class)->normalize('+1 202 555 0100', 'mobile_number');
    }
}
