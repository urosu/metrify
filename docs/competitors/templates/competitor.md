# Competitor profile template (Nexstage Batch 2)

Use the EXACT structure below when writing your competitor profile. Do not deviate. If a section has no findings, keep the header and write "Not observed in public sources." rather than dropping it.

```markdown
---
name: <Display name>
url: <homepage>
tier: <T1 | T2 | T3>
positioning: <1-line: who they sell to, what they replace>
target_market: <revenue band, ecosystem (Shopify/Woo/multi), geography if relevant>
pricing: <starting tier $ + scale axis (per-store, per-revenue, per-seat, etc.)>
integrations: <comma-separated list — Shopify, Woo, Meta Ads, Google Ads, GA4, Klaviyo, etc.>
data_freshness: <real-time | hourly | daily | batch | unknown>
mobile_app: <yes (iOS/Android) | no | web-responsive | unknown>
researched_on: 2026-04-28
sources:
  - <URL of homepage>
  - <URL of pricing page>
  - <URL of feature page or doc>
  - <URL of G2/Capterra/Reddit thread you quoted from>
  - <…>
---

## Positioning

2-3 sentences. Who they sell to. What product/category they replace or compete with. What positioning angle makes them different (the value prop, not the feature list).

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| <Free / Starter> | <$> | <bullets> | <"hits 1k orders/mo"> |

If pricing is opaque, say so explicitly. Capture screenshots of pricing pages to `_screens/<slug>-pricing-1.png` if useful.

## Integrations

What they pull from (sources) vs push to (destinations). Note required vs optional. Note coverage gaps (e.g., "no TikTok ads", "no GSC").

## Product surfaces (their app's information architecture)

List every distinct page/tab/screen the product has, with a 1-line description. This is the IA inventory — useful for downstream feature research. Format:

- **<Screen name>** — <1-line: what user question it answers>
- **<Screen name>** — …

Aim for completeness. T1 competitors typically have 8-20 surfaces.

## Data they expose

Break down by source. For each, list what fields they pull, what they compute on top, and what attribution windows / lookbacks they support.

### Source: Shopify (or platform equivalent)
- Pulled: <orders, line items, customers, products, refunds, inventory, etc.>
- Computed: <metrics derived: AOV, repeat-rate, cohort retention, net profit, etc.>
- Attribution windows: <if applicable>

### Source: Meta Ads
- Pulled: <campaign/adset/ad spend, impressions, clicks, conversions, ROAS>
- Computed: <pixel-attributed revenue, blended ROAS, etc.>

### Source: Google Ads
…

(repeat for every source the competitor lists; include Klaviyo, GA4, GSC, TikTok, etc. as relevant)

## Key UI patterns observed

This is the most important section. For every meaningful screen the competitor has, write a sub-section:

### <Screen Name>
- **Path/location:** Sidebar > … > … (or top nav, modal, etc.)
- **Layout (prose):** Describe the layout in concrete prose. Example: "Top: filter strip with date-range picker (presets + custom calendar) + store-switcher dropdown + period-comparison toggle. Left rail: 6-item sticky vertical sub-nav. Main canvas: 4-up KPI card row across the top (Revenue, Orders, AOV, ROAS), then a 12-month line chart with toggle for revenue/orders, then a 25-row sortable table grouped by channel."
- **UI elements (concrete):** Be specific. Examples: "Stoplight indicators (green/yellow/red dot inline next to KPI label)", "Sparkline below each KPI value (no axis, ~30px tall)", "Hover tooltip showing absolute value + percentage delta vs prior period + raw delta", "Sticky first column on horizontal scroll", "Color-coded delta cells (green for >0, red for <0, gray for 0)".
- **Interactions:** Drill-down (click a row to see breakdown by sub-dimension), filter (sidebar or top-of-table chips), hover (tooltip behavior), keyboard shortcuts, share/export options, real-time refresh, etc.
- **Metrics shown:** Exact list of metrics displayed on this screen.
- **Source/screenshot:** `docs/competitors/_screens/<slug>-<screen>-1.png` OR direct link to the source page where you observed this.

Cover every meaningful screen. T1 competitors typically yield 5-12 screen breakdowns. T3 may have only 2-3.

If you couldn't observe a screen's UI from public sources, say so explicitly: "UI details not available — only feature description seen on marketing page." DO NOT FABRICATE.

## What users love (verbatim quotes, attributed)

Pull at least 5 quotes if data exists. Format:

- "Verbatim quote here." — G2 reviewer, March 2026
- "Verbatim quote." — Reddit r/shopify, January 2026
- "Verbatim quote." — Trustpilot, February 2026
- "Verbatim quote." — Shopify App Store review, March 2026
- "Verbatim quote." — Twitter/X @<handle>, April 2026

If <5 quotes available, capture as many as possible and note "limited reviews available."

## What users hate (verbatim quotes, attributed)

Same format as above. At least 5 if data exists. If a competitor is universally loved (rare), say so but still capture any criticism.

## Unique strengths

Bullets. What they do that no one else does well, or do best in the category. Be specific (e.g., "Triple Pixel collects server-side tracking with iOS-loss recovery quoted at 12-18% reclaim — no other tool publishes this number").

## Unique weaknesses / common complaints

Bullets. Patterns of criticism that recur across multiple sources.

## Notes for Nexstage

Open observations, NOT recommendations. Things downstream feature research / synthesis should know about. Examples:

- "COGS import is per-product manual — many users complain in reviews. Worth noting in cost-config feature research."
- "They show pixel + GA4 + platform attribution side-by-side as 3 columns — direct analog to our 6-source badge thesis."
- "Their dashboard is paywalled at the second tier — couldn't get screenshots beyond the marketing page."
```

# Hard rules

- **NO INVENTION.** If a fact isn't found in primary sources, write "unknown" or "not observed". NEVER extrapolate UI from positioning.
- **QUOTE verbatim** with platform + month/year attribution. Don't paraphrase user feedback.
- **BE CONCRETE on UI.** "Bar chart" is wrong. Specify orientation, segments, color, interactions, hover behavior.
- **SCREENSHOTS:** When public-facing images exist, WebFetch them and save to `/home/uros/projects/nexstage/docs/competitors/_screens/<slug>-<screen>-<n>.png`. Reference in body. Skip if you can't get a clean image.
- **NO RECOMMENDATIONS.** Observations only. Synthesis is a downstream task.
- **STAY IN YOUR LANE.** Only write to your assigned `competitors/<slug>.md` file and `_screens/<slug>-*.png`. Don't touch any other file.
- **TARGET 250-500 lines.** Don't pad. Quality over quantity.

# Sources to consult (use WebSearch + WebFetch aggressively)

1. Their homepage, pricing page, features/product page, integrations page
2. Their blog — search for product walkthroughs, "introducing X", "how to use" posts (often have screenshots)
3. Their docs / help center
4. **G2.com** — their listing + review pages
5. **Capterra.com** — their listing + reviews
6. **TrustRadius.com** — their listing + reviews
7. **Trustpilot** — their listing + reviews
8. **Reddit** — `site:reddit.com "<name>"` review and "vs" threads. Target r/shopify, r/ecommerce, r/dtc, r/dropship, r/woocommerce, r/marketing.
9. **X/Twitter** — search the competitor name; DTC Twitter is loud
10. **YouTube** — "<name> tutorial" / "walkthrough" / "review" — WebFetch the video URL or transcript service (tactiq.io, youtube-transcript) where available
11. **Shopify App Store** — if Shopify-native, the listing has installation count + recent reviews
12. Comparison articles — competitor's own "vs" pages, plus 3rd-party "alternatives" articles

# Return format

After writing the file, return a 100-word report:
- File path
- Line count
- Number of user-feedback quotes captured (love + hate counts separately)
- Top 2 surprising findings about how they present data
- Any blockers (e.g., dashboard behind paywall, no public screenshots)
