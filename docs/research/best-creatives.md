# Best Creatives Gallery — Research Synthesis

Compiled 2026-04-30. Informs `resources/js/Pages/Ads/Creatives.tsx` upgrade.

## Sources consulted

- `_teardown_northbeam.md#screen-creative-analytics` — Creative grid, compare drawer, red/green metric cells
- `_teardown_triple-whale.md#screen-creative-cockpit` — Trophy strip, Thumb-Stop Ratio, hover-to-play, group-by convention
- `motion.md` — thumbnail-first grid, StatStripe, leaderboards with momentum arrows
- `atria.md` — three-column triage, A/B/C/D letter grades, prescriptive "here's what to fix" card
- `klaviyo.md` — top performing flows / campaigns table (email+SMS attribution)
- `_patterns_catalog.md` — §"Creative thumbnail performance overlay", §"Leaderboard with momentum arrows", §"Three-column triage layout"

## Gallery layout decision

**Grid card (default) with list/table toggle** — all four tools converge on this. Northbeam and Triple Whale default to grid; table is a power-user escape hatch. Motion uses a 4-column grid at 1440 (collapses to 2 at mobile). Cards are ~280×360px with thumbnail filling the top 55–60% of the card.

**Thumbnail size:** aspect-video (16:9) for static images and video posters. Motion uses square (1:1) for video-first; Triple Whale uses ~320×220 landscape. We use aspect-video (matches the existing Ads/Creatives.tsx convention) and add a format chip overlay.

## Triple Whale Creative Cockpit patterns adopted

1. **Trophy strip** ("Creative Highlights") — 6 trophy cards above the main gallery: Top Spend, Top ROAS, Top CTR, Top Thumb-Stop, Top Hold Rate, Fastest Riser. Each card: thumbnail + metric label + metric value. Scrollable horizontal strip. Northbeam has a similar "Featured creatives" row in v3.
2. **Platform filter pill row** — All / Meta / Google / TikTok (greyed). No sidebar; filter lives in the toolbar row, contextual to the section.
3. **Group-by dropdown** — Individual Ad / Naming Convention / Creative Type. We use Creative Type (format) as the primary grouping axis.
4. **"Compare up to 6"** checkbox on each card — opens side-by-side compare modal. (Stub for now; we show a button that alerts.)

## Northbeam Creative Analytics patterns adopted

1. **Red/green metric cells** on the list table — same ROAS gradient from Sales page.
2. **6-ad compare drawer** — Northbeam supports side-by-side overlaid LineCharts. We stub the button.
3. **Confidence chip** on low-sample creatives (< threshold impressions).
4. **Naming-convention pivot strip** — collapsed by default; expands to show parsed `[angle][hook][offer]` tokens as filter chips.

## Motion patterns adopted

1. **Momentum arrows** on thumbnail overlay — rank #1→#3 with green/red arrows. Already in existing Creatives.tsx.
2. **StatStripe** — 5-metric strip under thumbnail: Spend · ROAS · CTR · CPA · Impressions. Already in existing Creatives.tsx.
3. **Leaderboard** sort: composite score (ROAS 50% + CTR 25% + CPA 25%), then by spend.
4. **"No preview" placeholder** with camera icon when thumbnail unavailable.

## Atria patterns adopted

1. **Three-column triage** — Winners / Iteration / Candidates. Already in existing Creatives.tsx.
2. **Prescriptive one-liner** per Candidates card: "Spent $X · 0 purchases — consider pausing".
3. **A/B/C/D letter grade** overlay top-right of thumbnail.

## Klaviyo top performers

Klaviyo surfaces "top performing flows" and "top performing campaigns" in its Email Performance Report. Metrics: Recipients, Open Rate, Click Rate, Revenue Attributed, Revenue per Recipient. We surface this as a **separate collapsible section** below the main gallery (not mixed into the ad gallery). Light treatment: two 5-row compact tables (Flows | Campaigns), each showing Name · Revenue · Orders · Revenue/Email. Platform chip: "Klaviyo".

## Filter taxonomy

Based on Motion and Atria filter panels:
- **Platform**: All / Facebook / Google / (TikTok greyed)
- **Format**: All / Image / Video / Carousel / Email / SMS
- **Status**: All / Active / Paused / Archived
- **Performance grade**: All / Top (score ≥ 60) / Middling (35–59) / Bottom (< 35)
- **Sort by**: ROAS / Spend / CTR / Recency

Tags (theme/hook/offer) are parsed from `parsed_convention` server-side. For mock data, we hardcode tags per creative row.

## Performance grade dot

- `var(--color-success)` = green dot = score ≥ 60 (Winner tier)
- `var(--color-warning)` = amber dot = score 35–59 (Iteration tier)
- `var(--color-danger)` = red dot = score < 35 (Candidate tier)
- Muted zinc = no score data

## Cross-platform comparison

Northbeam Creative Analytics shows Facebook and Google creatives in one grid with a platform column. Triple Whale shows the same but groups by platform in sections. We use a single flat grid with a platform chip on each card; the platform filter lets users scope to one.

## Decisions not adopted

- Frame-by-frame video retention overlay (Motion v2 feature, deferred per ads.md out-of-scope list)
- AI auto-tagging (deferred)
- Budget editing via the gallery (Nexstage is read-only)
