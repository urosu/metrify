# Web Performance Analytics UI Patterns

> Live web research conducted April 2026. Relevant for Nexstage Performance page design decisions.
> Sources: Vercel, Shopify, GTmetrix, Cloudflare, New Relic, Datadog, SpeedCurve, Blue Triangle docs.

---

## Universal Principles (Across All Tools)

1. **No circular gauges for recurring monitoring.** PSI uses a gauge — but every continuous monitoring tool (Vercel, Shopify, New Relic, Datadog, SpeedCurve) uses flat metric cards + status badges. Gauges are for one-shot audits, not dashboards.
2. **P75 is the universal standard.** All tools use P75 as the primary display percentile. P50 (median) misleads for performance. P95/P99 shown for power users only.
3. **Field (RUM) data over lab data, always.** Every tool released after 2023 puts CrUX/RUM data first, Lighthouse lab scores second or collapsible. Nexstage already does this correctly.
4. **Status badges win over raw numbers.** "Good / Needs Improvement / Poor" more actionable than "2,340ms". Raw numbers should be secondary (muted text).
5. **Deploy/change annotations are essential.** Shopify, DebugBear, SpeedCurve all annotate time-series charts with deploy events. Without annotations, a time-series is "something happened around March 15th."
6. **Ecommerce-specific URL categorization is non-negotiable.** A 4.2s LCP on checkout = emergency; 4.2s on About Us = irrelevant. Home / Product / Checkout / Other segmentation.

---

## Per-Tool UI Patterns

### Vercel Speed Insights
- **Score**: "Real Experience Score" as P75 line-graph headline. NO circular gauge.
- **Chart**: time-based line graph, P75 default, P90/P95/P99 togglable
- **Secondary**: Kanban board of routes/paths color-coded Good/NI/Poor; choropleth geographic map
- **Deploy tracking**: filter by environment (production vs. preview) to compare pre/post-deploy

### Shopify Web Performance Dashboard
- **Score**: 3 metric cards — LCP / INP / CLS with P75 value + Good/Moderate/Poor badge. NO gauge.
- **Chart types**:
  - **Stacked-bar distribution**: % of visits in Good (green) / Moderate (yellow) / Poor (red)
  - **Time-series line chart**: P75 over time with visit counts colored by tier overlaid
  - **Event annotation tags**: numbered vertical lines on chart for store changes (theme updates, app installs)
- **Data**: CrUX, 28-day rolling window

### Google PageSpeed Insights
- **Score**: Large circular gauge (0–100), red/orange/green. Developer one-shot tool.
- Layout: CWV Assessment (hero) → Field data cards → Lab score gauge → Opportunities → Diagnostics → Passed audits

### GTmetrix
- **Score**: GTmetrix Grade (A–F letter badge) + Performance Score (% donut)
- **Key charts**:
  - **Speed filmstrip**: screenshots at specific ms intervals with FCP/LCP/TTI flags
  - **Waterfall chart**: per-request horizontal bars showing DNS/connect/SSL/TTFB/download phases
  - **History graphs**: line charts for Grade / Performance / individual metrics over time
  - **CrUX histograms**: 28-day histogram bars in CrUX tab

### Cloudflare Observatory
- **TTFB breakdown**: time-series with TTFB decomposed into DNS / TCP connect / TLS / request processing / response phases
- Histogram: blue bars (baseline TTFB) vs. orange bars (with CDN improvement)

### New Relic Browser Monitoring
- **Frontend vs backend split chart**: separates browser rendering time from server response time
- **Geographic heatmap**: P75 CWV by country; click country → watch real sessions from that country

### SpeedCurve (Most Ecommerce-Complete)
- **Correlation chart**: scatter/curve — Start Render time (X) vs. bounce rate or CVR (Y). Shows the cliff shape.
- **Deploy annotations**: exact inflection points on every chart
- **Shopify RUM integration**: tracks Add-to-Cart / Checkout Start / Checkout Complete. Correlation curves showing CVR drops from 5% at 0.4s to 38% at 2.5s.
- One merchant result: +10% traffic, +5% CVR, +15% revenue after improving metrics.

### Blue Triangle (Ecommerce-Specific)
- "Conversion Rate vs. Page Speed" curve: CVR on Y, load time on X. Shows "flat zone" + "cliff"
- "What-if" analysis: enter target speed improvement → get financial impact estimate in dollar terms

---

## What to Add to Nexstage Performance Page

### Implemented (as of April 2026)
- ✅ TTFB as first-class CWV card (Server Response Time)
- ✅ Distribution bars (Good/NI/Poor %) on each CWV card
- ✅ Performance vs. CVR correlation scatter chart (requires 6+ weeks data)
- ✅ Revenue at risk formula tooltip
- ✅ Uptime monitoring (30d uptime %, any-down indicator, per-URL status dot)
- ✅ Distribution columns stored in DB (not parsed from JSONB on each request)

### Next to implement (Tier 2)
- Event annotation markers on trend sparklines (wire to AnnotationService)
- Global TTFB probe map from multiple geographic locations
- CDN cache hit rate indicator (inspect X-Cache response headers)
- Before/after deploy comparison (side-by-side LCP/INP/CLS table)

### Not yet / V2
- Third-party script impact breakdown (group Lighthouse waterfall by hostname)
- INP breakdown by interaction type (from raw_response JSONB)
- Real User Monitoring (RUM) — needs JS snippet on merchant site
- TTFB probing on hourly cadence from all global locations
