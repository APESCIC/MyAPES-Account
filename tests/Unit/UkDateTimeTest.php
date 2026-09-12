<?php

namespace Tests\Unit;

use App\Support\UkDateTime;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UkDateTimeTest extends TestCase
{
    private UkDateTime $ukDateTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ukDateTime = new UkDateTime;
    }

    public function test_slash_dates_use_uk_day_month_order_so_fifth_of_march_is_not_third_of_may(): void
    {
        $parsed = $this->ukDateTime->parse('05/03/2026 14:30:00');

        $this->assertNotNull($parsed);
        $this->assertSame('2026-03-05 14:30:00', $parsed->timezone('UTC')->format('Y-m-d H:i:s'));
        $this->assertSame('05/03/2026 14:30:00', $this->ukDateTime->format($parsed));
        $this->assertSame('05/03/2026', $this->ukDateTime->formatDate($parsed));
        $this->assertNull($this->ukDateTime->parse('32/01/2026 10:00:00'));
        $this->assertNull($this->ukDateTime->parse('13/13/2026'));
    }

    public function test_format_converts_stored_utc_to_europe_london_without_changing_the_instant(): void
    {
        $storedUtc = Carbon::parse('2026-08-20 12:34:56', 'UTC');

        $this->assertSame('20/08/2026 13:34:56', $this->ukDateTime->format($storedUtc));
        $this->assertTrue(
            $this->ukDateTime->parse('20/08/2026 13:34:56')?->equalTo($storedUtc) ?? false,
        );
        $this->assertSame('2026-08-20 12:34:56', $storedUtc->timezone('UTC')->format('Y-m-d H:i:s'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedInputs(): array
    {
        return [
            'uk date only is midnight london' => ['05/03/2026', '2026-03-05 00:00:00'],
            'uk without seconds' => ['5/3/2026 9:15', '2026-03-05 09:15:00'],
            'iso datetime stays utc wall clock' => ['2026-08-20 14:30:00', '2026-08-20 14:30:00'],
            'iso local form stays utc wall clock' => ['2026-08-20T12:34:56', '2026-08-20 12:34:56'],
        ];
    }

    #[DataProvider('acceptedInputs')]
    public function test_parse_accepts_uk_and_unambiguous_iso_inputs(string $input, string $utc): void
    {
        $parsed = $this->ukDateTime->parse($input);

        $this->assertNotNull($parsed);
        $this->assertSame($utc, $parsed->timezone('UTC')->format('Y-m-d H:i:s'));
    }
}
