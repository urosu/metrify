<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Metrics\MetricSourceResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for MetricSourceResolver::columnFor().
 *
 * These tests are pure in-process — no DB, no Eloquent, no HTTP.
 * The method is a deterministic lookup so no fixtures are needed.
 */
class MetricSourceResolverTest extends TestCase
{
    private MetricSourceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate via the container so constructor injection is resolved.
        $this->resolver = $this->app->make(MetricSourceResolver::class);
    }

    // ── columnFor: canonical revenue × source pairs ──────────────────────────

    #[Test]
    public function it_returns_real_attributed_column_for_real_lens(): void
    {
        $this->assertSame(
            'revenue_real_attributed',
            $this->resolver->columnFor('revenue', 'real'),
        );
    }

    #[Test]
    public function it_returns_raw_revenue_column_for_store_lens(): void
    {
        $this->assertSame(
            'revenue',
            $this->resolver->columnFor('revenue', 'store'),
        );
    }

    #[Test]
    public function it_returns_facebook_attributed_column_for_facebook_lens(): void
    {
        $this->assertSame(
            'revenue_facebook_attributed',
            $this->resolver->columnFor('revenue', 'facebook'),
        );
    }

    #[Test]
    public function it_returns_google_attributed_column_for_google_lens(): void
    {
        $this->assertSame(
            'revenue_google_attributed',
            $this->resolver->columnFor('revenue', 'google'),
        );
    }

    #[Test]
    public function it_returns_gsc_attributed_column_for_gsc_lens(): void
    {
        $this->assertSame(
            'revenue_gsc_attributed',
            $this->resolver->columnFor('revenue', 'gsc'),
        );
    }

    /**
     * GA4 currently falls back to real (WS-F ga4_order_attribution not yet landed).
     */
    #[Test]
    public function it_falls_back_to_real_attributed_for_ga4_lens(): void
    {
        $this->assertSame(
            'revenue_real_attributed',
            $this->resolver->columnFor('revenue', 'ga4'),
        );
    }

    /**
     * Unknown source slugs must not return an empty string — that would corrupt
     * a raw SQL interpolation.  They fall back to the 'real' column.
     */
    #[Test]
    public function it_falls_back_to_real_for_unknown_source(): void
    {
        $this->assertSame(
            'revenue_real_attributed',
            $this->resolver->columnFor('revenue', 'unknown_source'),
        );
    }

    /**
     * Unknown metric keys also fall back to the 'real' revenue column so SQL
     * interpolation always receives a non-empty, safe string.
     */
    #[Test]
    public function it_falls_back_to_real_revenue_for_unknown_metric(): void
    {
        $this->assertSame(
            'revenue_real_attributed',
            $this->resolver->columnFor('sessions', 'ga4'),
        );
    }

    // ── REVENUE_COLUMN constant completeness ──────────────────────────────────

    /**
     * The canonical six source slugs must all be present in REVENUE_COLUMN so
     * callers never silently hit the fallback for a real, in-use lens.
     */
    #[Test]
    public function revenue_column_map_covers_all_six_canonical_sources(): void
    {
        $canonicalSources = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];

        foreach ($canonicalSources as $source) {
            $this->assertArrayHasKey(
                $source,
                MetricSourceResolver::REVENUE_COLUMN,
                "REVENUE_COLUMN is missing the canonical source '{$source}'",
            );
        }
    }

    /**
     * Every value in REVENUE_COLUMN must be a non-empty string — empty string
     * would produce invalid SQL when interpolated.
     */
    #[Test]
    public function revenue_column_values_are_non_empty_strings(): void
    {
        foreach (MetricSourceResolver::REVENUE_COLUMN as $source => $column) {
            $this->assertNotEmpty(
                $column,
                "REVENUE_COLUMN['{$source}'] must not be empty",
            );
        }
    }
}
