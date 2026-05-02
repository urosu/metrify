# Feature profile template (Nexstage Batch 3)

Use the EXACT structure below. Do not deviate. If a section has no findings, keep the header and write "Not observed in public sources." rather than dropping it.

```markdown
---
name: <Feature display name>
slug: <kebab-case slug, must match filename>
purpose: <one sentence: what merchant question this answers>
nexstage_pages: <which Nexstage pages map to this feature, e.g. profit, dashboard>
researched_on: 2026-04-28
competitors_covered: <comma list of competitor slugs covered in this profile>
sources:
  - <URL/file path of every competitor profile referenced>
  - <URL of any direct web sources used>
---

## What is this feature

1-2 paragraphs. What user question merchants are answering when they look at this. Why it matters for SMB Shopify/Woo owners specifically. What the difference is between "having data" (always present in source platforms) and "having this feature" (the synthesis or visualization that makes the data useful).

## Data inputs (what's required to compute or display)

For each input, name the source + the specific field/event:

- **Source: Shopify** — `orders.line_items.cost`, `orders.financial_status`, `orders.refunds`
- **Source: Meta Ads API** — `campaigns.spend`, `adsets.conversions`, `ads.creative_id`
- **Source: Computed** — `attributed_revenue = revenue × attribution_weight` (formula)
- **Source: User-input** — `cogs_per_product` (when missing from store data)

Be exhaustive — coding agents will use this section as a backend schema requirements list.

## Data outputs (what's typically displayed)

For each output, name the metric, formula, units, and typical comparisons:

- **KPI: Total revenue** — `SUM(orders.total_price)`, USD, vs prior-period delta
- **Dimension: Channel** — string, ~10 distinct values (Direct, Email, Paid Social, etc.)
- **Breakdown: Revenue × channel × time** — table or chart
- **Slice: Per-product / per-cohort / per-period**

This becomes the column list for tables, the axes for charts, the cells for grids.

## How competitors implement this

For EVERY competitor that has this feature (read their profile in `competitors/<slug>.md`), write a sub-section. **Required:** the `**Visualization:**` line MUST name a concrete viz type (waterfall, stacked bar, sparkline grid, heatmap, table, scatter, etc.) — not "chart" or "graph". If no visual is observed, write "no visualization, prose-only".

### <Competitor name> ([profile](../competitors/<slug>.md))
- **Surface:** <where in their app — Sidebar > … > …>
- **Visualization:** <table | waterfall | stacked-bar | sparkline-grid | heatmap | scatter | letter-grade | KPI-card | progress-donut | radar | …>
- **Layout (prose):** "Top: <…>. Left rail: <…>. Main canvas: <…>. Bottom: <…>."
- **Specific UI:** "<concrete element + visual property + interaction>" — e.g. "Stoplight indicators (green/yellow/red dot, 8px), inline next to KPI label. Hover reveals dollar value + % delta vs prior period."
- **Filters:** <list — date, store, channel, segment, …>
- **Data shown:** <exact metrics + dimensions on this surface>
- **Interactions:** <click drill-down, hover, share/export, keyboard, real-time refresh>
- **Why it works (from reviews/observations):** <1-2 sentences citing review themes>
- **Source:** <profile path + any external URL where you found additional detail>

(repeat for every competitor profile that documents this feature)

## Visualization patterns observed (cross-cut)

Synthesize the per-competitor sections into a count by viz type. Examples:

- **Waterfall:** 3 competitors (Lifetimely, Triple Whale, Conjura) — associated with positive reviews ("finally I can see where money goes")
- **Table-only:** 4 competitors (BeProfit, Putler, Shopify Native, Stripe Sigma) — neutral to negative reviews when displayed without sparklines or color
- **Stacked bar:** 2 competitors (TrueProfit, Bloom Analytics) — works for cost composition
- **Tree / hierarchy view:** 1 competitor (Bloom Analytics' Profit Map) — novel, uncertain reception

Note any visual conventions that recur — color use (red for cost, green for profit), iconography (sparklines under KPIs), interaction patterns (drill-down on row click).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

Group by theme. Pull verbatim quotes from the relevant competitor profiles' "What users love" sections.

**Theme: Clarity at a glance**
- "<verbatim quote>" — competitor X profile
- "<verbatim quote>" — competitor Y profile

**Theme: Drill-down without losing context**
- ...

## What users hate about this feature

Same theme structure.

**Theme: Manual COGS entry**
- "<verbatim quote>" — competitor X profile
- "<verbatim quote>" — competitor Y profile

## Anti-patterns observed

Concrete examples of bad implementations and why they failed. Cite the competitor.

- **Hidden source disagreement:** ROAS Monster collapses platform vs store revenue into one "Real" number — hides the disagreement that IS the information. Reviews complain about "ghost ROAS".
- **Aggregating without composition:** Generic line chart of "total cost" — doesn't show which cost categories moved. Users can't act.

## Open questions / data gaps

What couldn't be observed from public sources, what would require a paid eval account, what assumption-of-template you couldn't verify (e.g., "the brief mentioned Magic Dash uses chat — actually it's newspaper-headline format per Peel docs").

## Notes for Nexstage (observations only — NOT recommendations)

Open observations downstream synthesis can use. Examples:
- "5 of 12 competitors expose attribution windows as a top-level user setting; 7 hide it. The hiders rank lower on transparency reviews."
- "Cost overview waterfall is the dominant visualization (3/8 implementations) but no one shows it broken by ad-platform — only as 'Marketing' bucket. Direct gap for source-disagreement thesis."
- "The 5×5 RFM grid is universal among customer-cohort tools (Peel, Repeat Customer Insights, Klaviyo, Putler) — table-stakes."
```

# Hard rules

- **NO INVENTION.** If a competitor's profile says "UI details not available" for this surface, repeat that here. Don't extrapolate.
- **CITE COMPETITOR PROFILES.** Every fact about a competitor MUST link back to `competitors/<slug>.md` (relative path `../competitors/<slug>.md`).
- **VERBATIM QUOTES FOR USER FEEDBACK.** Pull from the competitor profiles' verbatim quote sections. Don't paraphrase.
- **CONCRETE VIZ TYPE.** "Bar chart" is wrong. "Horizontal stacked bar with 6 segments, color-coded by source, hover reveals $ + %" is correct.
- **NO RECOMMENDATIONS.** Observations only. The "Notes for Nexstage" section is for downstream synthesis to consume; you don't decide what Nexstage should build.
- **STAY IN YOUR LANE.** Only write to your assigned `features/<slug>.md` file. Don't touch any other file.
- **TARGET 250-500 lines.** Don't pad.

# Reading workflow

1. Read this template.
2. Read `_feature_index.md` to find the row for your feature — get the canonical "Top competitors known to do this well" list.
3. Read EACH of those competitor profiles (`competitors/<slug>.md`) fully. Look for the screen sub-sections that describe this feature. Capture verbatim love/hate quotes.
4. Read the OTHER 30+ competitor profiles enough to spot any that also implement this feature but weren't on the curated list. Add them to your write-up.
5. Optionally: WebSearch + WebFetch for any specific UI detail you need that's missing from the profiles (rare — most should be in profiles).
6. Write the feature profile using the template.

# Return format

After writing the file, return a 100-word report:
- File path
- Line count
- Number of competitors covered
- Top 1-2 cross-cut findings (e.g., "Waterfall dominates cost-overview viz (4/8); 0 break by ad-platform")
- Any blockers (e.g., "feature is paywalled in every competitor; no UI details available")
