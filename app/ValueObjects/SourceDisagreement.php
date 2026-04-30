<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Output of the Source Disagreement Matrix computation.
 *
 * Represents revenue (or other metric) disagreement across 6 sources for a given dimension
 * (e.g., a campaign, date range, country). Computed by aggregating orders with
 * attribution data and comparing against platform-reported figures.
 *
 * @see PLANNING.md section 1 (thesis: source disagreement surfaced)
 * @see backend.md §5
 * @see UX.md §5 TrustBar + Source Disagreement Matrix
 *
 * Reads: Source Disagreement Matrix page, TrustBar drawer
 * Writes: AcquisitionController, DashboardController (via RevenueAttributionService)
 */
final class SourceDisagreement
{
    /**
     * @param float $store Revenue reported by the ecommerce platform (WC/Shopify)
     * @param float $facebook Revenue attributed to Facebook Ads (platform-reported)
     * @param float $google Revenue attributed to Google Ads (platform-reported)
     * @param float $gsc Revenue associated with Google Search Console keywords
     * @param float $site Revenue from site pixel / CRM integration
     * @param float $real Revenue computed via attribution model (Nexstage truth)
     * @param float $deltaPct Percentage difference between largest and smallest (winner variance)
     * @param string $winner Source name with highest revenue
     */
    public function __construct(
        public readonly float $store,
        public readonly float $facebook,
        public readonly float $google,
        public readonly float $gsc,
        public readonly float $site,
        public readonly float $real,
        public readonly float $deltaPct,
        public readonly string $winner,
    ) {}

    /** @return array<string, float|string> */
    public function toArray(): array
    {
        return [
            'store'    => $this->store,
            'facebook' => $this->facebook,
            'google'   => $this->google,
            'gsc'      => $this->gsc,
            'site'     => $this->site,
            'real'     => $this->real,
            'delta_pct' => $this->deltaPct,
            'winner'   => $this->winner,
        ];
    }

    /** @param array<string, float|string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            store: (float) ($data['store'] ?? 0),
            facebook: (float) ($data['facebook'] ?? 0),
            google: (float) ($data['google'] ?? 0),
            gsc: (float) ($data['gsc'] ?? 0),
            site: (float) ($data['site'] ?? 0),
            real: (float) ($data['real'] ?? 0),
            deltaPct: (float) ($data['delta_pct'] ?? 0),
            winner: (string) ($data['winner'] ?? 'real'),
        );
    }
}
