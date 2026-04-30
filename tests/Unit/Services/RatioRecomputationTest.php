<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the "ratios are never stored" rule documented in CLAUDE.md.
 *
 * search_console_*.ctr is a raw-API exception: we store the per-row value
 * verbatim from GSC, but when aggregating across multiple rows (e.g. a date
 * range query) callers MUST recompute CTR as SUM(clicks)/SUM(impressions).
 * Using AVG(ctr) over stored per-row values will silently produce wrong results
 * whenever row sizes differ.
 *
 * This test exists so future devs can't accidentally write AVG(ctr) aggregations.
 */
class RatioRecomputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregated_ctr_must_use_sum_clicks_over_sum_impressions_not_avg_stored_ctr(): void
    {
        $workspace = Workspace::factory()->create();

        // Create a minimal search_console_properties row so the FK is satisfied.
        $propertyId = DB::table('search_console_properties')->insertGetId([
            'workspace_id' => $workspace->id,
            'property_url' => 'https://example.com/',
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Row 1: 10 clicks, 1000 impressions → GSC-reported CTR = 0.01 (1%)
        // Row 2: 90 clicks, 100 impressions  → GSC-reported CTR = 0.90 (90%)
        // These mismatched row sizes are a realistic scenario (e.g. branded vs
        // long-tail queries). The two rows share the same query keyword but
        // differ by date to avoid the unique index.
        DB::table('search_console_queries')->insert([
            [
                'property_id' => $propertyId,
                'workspace_id' => $workspace->id,
                'date'        => '2026-04-01',
                'query'       => 'nexstage analytics',
                'device'      => 'all',
                'country'     => 'ZZ',
                'data_state'  => 'final',
                'clicks'      => 10,
                'impressions' => 1000,
                'ctr'         => 0.010000,
                'position'    => 5.0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'property_id' => $propertyId,
                'workspace_id' => $workspace->id,
                'date'        => '2026-04-02',
                'query'       => 'nexstage analytics',
                'device'      => 'all',
                'country'     => 'ZZ',
                'data_state'  => 'final',
                'clicks'      => 90,
                'impressions' => 100,
                'ctr'         => 0.900000,
                'position'    => 2.0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        $result = DB::table('search_console_queries')
            ->where('property_id', $propertyId)
            ->where('query', 'nexstage analytics')
            ->selectRaw('
                SUM(clicks)                                    AS total_clicks,
                SUM(impressions)                               AS total_impressions,
                SUM(clicks) * 1.0 / NULLIF(SUM(impressions), 0) AS correct_ctr,
                AVG(ctr)                                       AS wrong_ctr
            ')
            ->first();

        // Correct: (10 + 90) / (1000 + 100) = 100 / 1100 ≈ 0.0909
        // Wrong:   AVG(0.01, 0.90) = 0.455 — wildly inflated because it ignores
        //          that row 1 has 10× more impressions.
        $correctCtr = (float) $result->correct_ctr;
        $wrongCtr   = (float) $result->wrong_ctr;

        $this->assertEqualsWithDelta(100 / 1100, $correctCtr, 0.000001,
            'SUM(clicks)/SUM(impressions) should equal 100/1100 ≈ 0.0909');

        $this->assertNotEqualsWithDelta($correctCtr, $wrongCtr, 0.001,
            'AVG(stored_ctr) must not equal the correctly aggregated CTR — '.
            'using AVG(ctr) over rows with unequal impression counts silently produces wrong results.');
    }
}
