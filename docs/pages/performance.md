# Performance

Route: `/performance`

## Purpose

Answer "are my pages fast, and where are the CrUX losers?" — using Google PageSpeed Insights / CrUX field data and Lighthouse lab data, cross-referenced with landing pages from `/ads` and `/products` to prioritize what to fix.

## User questions this page answers

- Which landing pages have poor CrUX scores (LCP, CLS, INP) in the real-world field data?
- What does Lighthouse say about my homepage and top landing pages (lab conditions)?
- Did my recent site changes improve or hurt Core Web Vitals?
- Which slow pages are also high-spend ad destinations — where is slow speed burning ad budget?
- How does my store's Shopify Speed Score compare to my historical trend?

## Data sources

| Source | Required? | Provenance | Freshness |
|---|---|---|---|
| Google PSI / CrUX | Yes | `lighthouse_snapshots` table; synced nightly via `SyncCruxJob` using PageSpeed Insights API (Chrome UX Report + Lab data). Paired `raw_response` + `raw_response_api_version` per CLAUDE.md JSONB rules. | Nightly |
| Shopify Speed Score | Optional (Shopify only) | Shopify Admin API `OnlineStore.speedReport` — polled weekly | Weekly |
| Store landing pages | Optional | `orders.utm_*` + ad landing page joins from `campaigns.parsed_convention` | Same as orders |

## Above the fold (1440×900)

- `AlertBanner` (info) — "CrUX data reflects real users over the last 28 days — not lab conditions. Lighthouse (lab) results may differ." Dismissible per session.
- `KpiGrid` (4 cols) — workspace-aggregate CrUX field data:
  - `MetricCard` "Good LCP URLs %" — % of tracked URLs with Good LCP (≤2.5s). Delta vs prior 28d.
  - `MetricCard` "Good INP URLs %" — % with Good INP (≤200ms). Delta vs prior 28d.
  - `MetricCard` "Good CLS URLs %" — % with Good CLS (≤0.1). Delta vs prior 28d.
  - `MetricCard` "Shopify Speed Score" — numeric 0–100 (Shopify stores only). `ConfidenceChip` (§5.27) if fewer than 5 URLs tracked. Source badge: Store (slate).
- `LineChart` "Core Web Vitals trend" — one line per metric (LCP p75, INP p75, CLS p75) over the active date range. `GranularitySelector` defaults Weekly. `ChartAnnotationLayer` (§5.6.1) flags deploy dates and Google algorithm updates (same seeded list as `/seo`). Dotted incomplete-period segment on rightmost bin.
- **URL Performance Table** (`DataTable` §5.5) — one row per tracked URL. Default sort: LCP p75 DESC (worst first). Columns:
  - URL (JetBrains Mono, `MiddleTruncate` §5.18)
  - LCP p75 (tabular, colored green/amber/red per Core Web Vitals thresholds)
  - INP p75
  - CLS p75
  - Lighthouse Score (0–100; click → drawer with full Lighthouse report)
  - Ad Spend (past 28d; joined from `ad_insights` via `campaigns.parsed_convention` landing page match)
  - Orders (past 28d; joined from `orders` via `utm_content` / landing page)
  - Status chip (Good / Needs Improvement / Poor — CrUX composite)
  - Last synced (relative timestamp)

## Below the fold

- **Lighthouse Detail DrawerSidePanel** (opened by row click):
  - Lighthouse scores: Performance / Accessibility / Best Practices / SEO (four dials, 0–100).
  - Top opportunities: list of Lighthouse audit items by potential savings (ms), per PSI API response.
  - Historical trend: per-URL LCP/INP/CLS sparklines over the last 90 days.
  - "Open in PageSpeed Insights" link — deep-links to `https://pagespeed.web.dev/report?url=...` with the URL prefilled.
- **Landing page × ad spend correlation** (when `/ads` data is available): `QuadrantChart` (§5.6) X-axis = Lighthouse Performance Score, Y-axis = ROAS, bubble size = Ad Spend. Quadrant 1 (fast + high ROAS) = green zone. Quadrant 3 (slow + low ROAS) = rose zone. Hover a bubble → shows URL + metrics. Click → row filter in the table above.

## Interactions specific to this page

- **Table row click** opens Lighthouse DrawerSidePanel — does NOT navigate away from the page.
- **URL column click** opens the URL in a new tab (external link, `target="_blank"`).
- **FilterChipSentence** supports filtering by: Status (Good / Needs Improvement / Poor) · Has Ad Spend (yes/no) · URL contains (text match).
- `SavedView` (§5.19): canonical presets seeded — "Poor LCP + High Spend", "All Good", "Shopify Storefront only".
- `ExportMenu` (§5.30): CSV includes all CWV columns + Lighthouse scores + ad spend + orders.
- `ChartAnnotationLayer` right-click to annotate: useful for marking "deployed new theme", "moved to new hosting", etc.

## Competitor references

- [Shopify Speed Score](../competitors/shopify-native.md) — numeric 0–100 benchmark; we show it in context with real CrUX data rather than alone.
- [Google PSI / web.dev](../competitors/_inspiration_plausible.md) — Core Web Vitals thresholds are Google-canonical (Good / Needs Improvement / Poor). We use them verbatim, not re-labelled.
- [Plausible](../competitors/_inspiration_plausible.md) — honesty of "this is field data from real users" vs lab data; we make both explicit.

## Mobile tier

**Desktop-only** (≥1280px). The CrUX table and Lighthouse drawer do not render usefully on mobile. At `<lg`, the page shows the four KpiGrid cards + a "View full report on desktop" banner.

## Out of scope v1

- **Uptime monitoring** — v2. Schema can add a `uptime_checks` table; not wired in v1.
- **TTFB (Time to First Byte) tracking** — PSI does return TTFB lab data; deferred to v2 after CWV basics ship.
- **Real User Monitoring (RUM) via a Nexstage JS snippet** — v2.
- **Competitor page speed benchmarking** — v2 with the rest of peer data.
- **Automated "fix this" recommendations** — no AI prescriptions; the Lighthouse detail drawer links to external tooling.
- **Alerts on CWV regression** — `TriageInbox` (§5.22) will surface a "LCP regressed on /products/x" item when the nightly sync detects a cross-threshold change; the alert triggers via `AnomalyDetection` service.
