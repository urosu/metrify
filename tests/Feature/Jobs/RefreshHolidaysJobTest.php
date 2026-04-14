<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\RefreshHolidaysJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefreshHolidaysJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $countryCode, int $year = 2026): void
    {
        (new RefreshHolidaysJob($countryCode, $year))->handle();
    }

    public function test_populates_holidays_for_supported_country(): void
    {
        $this->runJob('DE', 2026);

        $count = DB::table('holidays')
            ->where('country_code', 'DE')
            ->where('year', 2026)
            ->count();

        $this->assertGreaterThan(0, $count);
    }

    public function test_unsupported_country_skips_silently(): void
    {
        $this->runJob('ZZ', 2026); // not in COUNTRY_TO_PROVIDER map

        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_idempotent_upsert(): void
    {
        $this->runJob('DE', 2026);
        $firstCount = DB::table('holidays')->where('country_code', 'DE')->count();

        $this->runJob('DE', 2026); // re-run same year
        $secondCount = DB::table('holidays')->where('country_code', 'DE')->count();

        $this->assertSame($firstCount, $secondCount);
    }

    public function test_all_rows_have_correct_country_code(): void
    {
        $this->runJob('FR', 2026);

        $mismatch = DB::table('holidays')
            ->where('country_code', '!=', 'FR')
            ->count();

        $this->assertSame(0, $mismatch);
    }

    public function test_correct_year_stored(): void
    {
        $this->runJob('DE', 2025);

        $wrongYear = DB::table('holidays')
            ->where('country_code', 'DE')
            ->where('year', '!=', 2025)
            ->count();

        $this->assertSame(0, $wrongYear);
    }

    public function test_multiple_countries_stored_independently(): void
    {
        $this->runJob('DE', 2026);
        $this->runJob('FR', 2026);

        $deCnt = DB::table('holidays')->where('country_code', 'DE')->count();
        $frCnt = DB::table('holidays')->where('country_code', 'FR')->count();

        $this->assertGreaterThan(0, $deCnt);
        $this->assertGreaterThan(0, $frCnt);
    }
}
