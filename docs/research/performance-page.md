# Performance Page Research

Research conducted 2026-04-30 for `/performance` page build.

---

## 1. KPI hierarchy

**Google canonical Core Web Vitals (field data thresholds):**
- LCP: ≤2500ms Good · ≤4000ms Needs Improvement · >4000ms Poor
- INP: ≤200ms Good · ≤500ms Needs Improvement · >500ms Poor
- CLS: ≤0.1 Good · ≤0.25 Needs Improvement · >0.25 Poor

**Shopify Online Store Speed Report** (Shopify admin → Analytics → Online Store Speed):
- Composite 0–100 Speed Score (Lighthouse-based) shown as headline number with benchmark band.
- Horizontal threshold line across graph: Good / Moderate / Poor.
- Numbered vertical event annotation tags on the trend line.
- Per-URL table rows with distribution bar (green/yellow/red share of visits).
- Mobile threshold: ≥70 Good; Desktop: ≥90 Good.
- Benchmarked against "similar stores" so merchants can contextualise their score.
- **Copied pattern:** threshold annotations on the trend chart; per-URL distribution color bar.

**PageSpeed Insights / CrUX:**
- Reports field data from the 28-day rolling Chrome UX Report (CrUX) window.
- Lab data from Lighthouse (diagnostic conditions, not real users).
- PSI explicitly labels which section is "Field data" and which is "Lab data" — we do the same with source chips.
- **Copied pattern:** two-pane layout (field first, lab second); "Open in PageSpeed Insights" external link in drawer.

---

## 2. Table columns (precedents)

**Vercel Speed Insights:**
- P75 percentile time-series line chart per metric (toggle P90/P95/P99).
- Kanban board view: routes/paths/HTML elements needing improvement.
- URLs under 0.5% of visits not shown by default (minimum sample threshold).
- Geographic map with color intensity by country.
- Filter by device type, connection speed, geographic location.
- **Copied pattern:** sample size threshold ("CrUX" chip only when sufficient sample); device-split toggle above table.

**Plausible top pages:**
- Columns: Visitors · Pageviews · Bounce Rate · Time on Page · Scroll Depth.
- Sortable by any column header.
- Revenue column when revenue goals are tracked (cross-sell insight).
- **Copied pattern:** sort any column to surface worst/best performers; ad-spend column as "revenue context" analog.

**Google Search Console CWV report:**
- Groups URLs by status (Good / Needs Improvement / Poor).
- Shows CrUX sample-size qualifier.
- **Copied pattern:** CrUX sample size shown in table; status-grouping chips.

---

## 3. Drill UX

**Shopify:** event overlays on trend chart (theme deployments, app installs).
**Vercel:** click a route in Kanban → drill to per-route metric breakdown.
**PageSpeed Insights:** full audit list sorted by "potential savings (ms)" — highest impact first (DebugBear pattern too).
**Copied pattern for drawer:** four Lighthouse score dials (Performance / Accessibility / Best Practices / SEO) + opportunities sorted by score-weight impact + "Open in PageSpeed Insights" deep-link.

---

## 4. Score visualization

**Shopify:** numeric 0–100 in a card with colored band background.
**PageSpeed Insights:** circular gauge with three-color arcs (green/amber/red).
**Vercel:** bold number + trend arrow; no gauge.
**Decision:** we use a bold score number inside a colored ring for the per-URL table score column; no SVG gauge (too heavy). Color coding: ≥90 emerald, 50–89 amber, <50 rose — matching Google's canonical bands.

---

## 5. Mobile vs desktop split

**CrUX** provides separate `phone` and `desktop` strategy data.
**PageSpeed Insights** shows mobile vs desktop as two tabs (mobile default, per Google's mobile-first indexing guidance).
**Vercel** allows filtering by device type across all metrics.
**Decision:** tabbed mobile/desktop toggle above the table (not separate pages). Default = mobile (matches PSI). Each row shows the active strategy's scores.

---

## 6. CrUX vs Lighthouse fallback presentation

**PSI pattern:** "Field data" section shown first when CrUX has sufficient sample (≥75 origins). Falls back to showing only "Lab data" with a "No field data available" notice.
**CrUX API minimum:** URLs with <75 unique origins in the 28d window return insufficient data.
**Decision for Nexstage:**
- When `crux_source = 'crux'` → show "CrUX" source chip (emerald tint, field data).
- When `crux_source = 'lighthouse'` → show "Lab" source chip (sky tint, lab data).
- Both chips have a tooltip explaining the difference.
- Table default-sorts Poor LCP first so the most actionable URLs surface immediately.

---

## 7. Integration with ad-spend / SEO data

**QuadrantChart** (X = Lighthouse Performance Score, Y = ROAS, size = Ad Spend):
- Quadrant 1 (fast + high ROAS): emerald zone — keep running ads here.
- Quadrant 3 (slow + low ROAS): rose zone — fix speed first, then reassess budget.
- Hover → URL + metrics tooltip. Click → table row filter.
- Copied from: Northbeam quadrant scatter + performance.md spec §Below the fold.

**Ad Spend column in table:** joined from `ad_insights` via `campaigns.parsed_convention` landing page match. Missing → "—".

---

## Sources consulted

- [Shopify Web Performance Reports](https://help.shopify.com/en/manual/online-store/web-performance/web-performance-reports)
- [Shopify Online Store Speed Report changelog](https://shopify.dev/changelog/measure-storefront-performance-with-new-online-store-speed-report)
- [Google Core Web Vitals](https://developers.google.com/search/docs/appearance/core-web-vitals)
- [About PageSpeed Insights](https://developers.google.com/speed/docs/insights/v5/about)
- [Vercel Speed Insights Overview](https://vercel.com/docs/speed-insights)
- [Vercel Speed Insights Metrics](https://vercel.com/docs/speed-insights/metrics)
- [Plausible Top Pages docs](https://plausible.io/docs/top-pages)
- [CrUX API guide](https://developer.chrome.com/docs/crux/guides/crux-api)
- [RUMvision: Lighthouse vs CrUX vs RUM](https://www.rumvision.com/blog/understanding-the-difference-between-core-web-vitals-tools/)
- [Lumar: SEO guide to speed/CWV/Lighthouse/CrUX](https://www.lumar.io/blog/best-practice/seo-guide-to-site-speed-core-web-vitals-lighthouse-crux-data/)
