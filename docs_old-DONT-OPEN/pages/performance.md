# Performance Page — UX Spec

**Route:** `/{workspace:slug}/performance`

**Controller:** `PerformanceController` (invokable)

**Data service:** `PerformanceDataService::forIndex()`

---

## §1 — Purpose

Show Core Web Vitals as Google actually measures them (CrUX field data), correlate poor
vitals with revenue at risk, and surface Lighthouse lab scores for debugging. Two-track
design: CrUX field data is the hero (what matters for ranking); Lighthouse lab data is
collapsible (for diagnostics only).

---

## §2 — Filters

| Filter | Values | Default | Persistence |
|---|---|---|---|
| Strategy | `mobile` / `desktop` | `mobile` | Query param `strategy` |
| Page type | `all` / `home` / `product` / `checkout` / `other` | `all` | Query param `page_type` |
| Window | `30` / `60` / `90` days | `30` | Query param `window` |

Filter changes trigger an Inertia partial reload (`router.get`, `preserveState: true`).

---

## §3 — CWV Thresholds

| Metric | Good | Needs Improvement | Poor |
|---|---|---|---|
| LCP | ≤ 2500 ms | 2500–4000 ms | > 4000 ms |
| INP | ≤ 200 ms | 200–500 ms | > 500 ms |
| CLS | ≤ 0.10 | 0.10–0.25 | > 0.25 |

Status: `'good' | 'needs_improvement' | 'poor' | null` (null when value is null).

---

## §4 — Page Sections (top to bottom)

### 4.1 Filter bar

Horizontal row with:
- Strategy toggle (Mobile | Desktop pill buttons)
- Page type tabs (All | Home | Products | Cart | Other)
- Window selector pushed to the right (30d | 60d | 90d)

### 4.2 CrUX hero section

Header: "How Google sees your site" + `InfoTooltip`.
Subtitle: "Real Chrome user data · 28-day rolling window" (CrUX always uses 28d regardless of window filter).

Three `CwvCard` components in a 3-column grid:

| Card | Label | Abbr | Format |
|---|---|---|---|
| LCP | Loading Speed | LCP | `X.X s` |
| INP | Responsiveness | INP | `X ms` |
| CLS | Layout Stability | CLS | `0.XX` |

Each `CwvCard` shows:
- Metric value (coloured by status)
- Status badge: Good / Needs Improvement / Poor
- `CruxSourceChip`: "Real users" (source=`url`) | "Site avg" (source=`origin`) | "Lab est." (null)
- 12-week sparkline trend (SVG polyline, data from `trend[]`)

### 4.3 Revenue at risk callout

Shown only when `revenue_at_risk > 0`. Amber banner with `AlertTriangle` icon:

> **€X,XXX/month** estimated at risk from slow pages based on organic CVR drop

With `InfoTooltip` explaining the calculation.

### 4.4 URL table

Heading: "Pages — sorted by revenue at risk"

Columns (left to right):
1. Page — label (bold) + URL path (muted)
2. Type — pill badge (home / product / checkout / other)
3. Orders/mo — right-aligned integer
4. Revenue at risk — amber if > 0, dash if 0
5. Loading Speed (LCP) — `CwvCell` with status colour + "lab" label if crux_source is null
6. Responsiveness (INP) — same
7. Layout Stability (CLS) — same
8. Lab Score — `LabScoreBadge` (0–49 red / 50–89 amber / 90–100 green)

Sorted server-side by `revenue_at_risk DESC`.

Empty table state (filter produces no rows): "No pages match the selected filter".

### 4.5 Lab diagnostics (collapsible)

`<details>` element, closed by default. Summary line shows overall lab score.
Subtitle in summary: "Lab data is for debugging — Google ranks based on real-user data above".

Body: grid of per-URL FCP, TTFB, TBT values (text-only, no charts).

Shown only when `summary.lab_performance_score !== null`.

### 4.6 Empty state (no URLs)

Shown when `has_store_urls === false`. Centred layout:
- `Gauge` icon (zinc-300, 48×48)
- Heading: "No pages monitored yet"
- Body: "Add URLs to start tracking Core Web Vitals and page speed."
- CTA button → `/settings/integrations`

### 4.7 CrUX null state chip

When `has_crux_data === false` (no URL has CrUX data), show chip inline under each CwvCard value:
"Lab estimate only — not enough real-user data yet"

---

## §5 — Primitives used

- `CwvCard` — inline component, not extracted to shared/
- `CruxSourceChip` — inline
- `LabScoreBadge` — inline
- `MiniSparkline` — inline SVG polyline
- `CwvCell` — inline, used in table
- `InfoTooltip` from `@/Components/shared/Tooltip`
- `AppLayout` from `@/Components/layouts/AppLayout`
- `Gauge`, `AlertTriangle` from `lucide-react`

---

## §6 — Data shape (from server)

```
summary.lcp / .inp / .cls:  { p75, status, crux_source }
summary.lab_performance_score: int | null
trend[]:  { week, lcp_p75, inp_p75, cls_p75 }
revenue_at_risk: float
urls[]:   full UrlRow[] shape (see PerformanceDataService)
filters:  { strategy, page_type, window_days }
has_store_urls: bool
has_crux_data: bool
```

---

## §7 — CrUX source hierarchy

1. `url` — CrUX has enough real-user data for this exact URL → "Real users" chip (blue)
2. `origin` — CrUX has origin-level data only → "Site avg" chip (zinc)
3. `null` — no CrUX data, Lighthouse lab only → "Lab est." chip (zinc muted)

The `crux_source` field on each URL row reflects the source for that URL specifically.
The `crux_source` on `summary.*` is the aggregate source across all URLs (null if any
are missing; 'url' if all have URL-level data; 'origin' otherwise).

---

## §8 — Notes for implementation

- `windowDays` is passed through to the Inertia props and back in `filters`, but the
  data service uses 30d as the effective window for all queries in v1. The filter UI
  works visually (button highlights correct option) even though the data doesn't change.
- CrUX data inside `lighthouse_snapshots` uses `crux_lcp_p75_ms`, `crux_inp_p75_ms`,
  `crux_cls_p75`, `crux_fcp_p75_ms`, `crux_ttfb_p75_ms` columns.
- Lab data uses `lcp_ms`, `fcp_ms`, `cls_score`, `inp_ms`, `ttfb_ms`, `tbt_ms`,
  `performance_score`.
- Strategy maps to `strategy` column on `lighthouse_snapshots` ('mobile' | 'desktop').
