# Research: User Flow Funnel Analytics

Research date: 2026-04-30

## Queries run

1. "User flow funnel analytics ecommerce 2026 UX best practices"
2. "GA4 path exploration funnel visualization ecommerce drop-off"
3. "Mixpanel funnel analysis UX vertical bars steps"
4. "Plausible Analytics goal funnel step conversion rate UX design 2025"
5. "Triple Whale customer journey funnel product analytics ecommerce"
6. "Sankey diagram product flow ecommerce user path visualization"
7. "Heap funnel analysis add-to-cart drop-off product analytics UX patterns"
8. "Polar Analytics acquisition funnel ecommerce paid traffic product interest conversion"

---

## Funnel visualization style: horizontal bars vs Sankey vs path tree

### Horizontal / vertical bar funnel (recommended for Nexstage)

- **GA4 Funnel Exploration** uses vertical bars with step-by-step drop-off count and % annotated between bars. The "standard funnel" view is the industry default for a reason: scannable, no graph-literacy required, immediately shows where the biggest leak is.
- **Plausible Analytics** ships a horizontal bar funnel (each step is a proportionally-wide bar, drop-off labelled). Rated more scannable than stacked bars for ordered, linear flows. Min 2, max 8 steps; sequential or strict-order mode.
- **Mixpanel** uses vertical bar chart ("Funnel Steps" mode) as primary, with table/pie/stacked-bar as secondary toggles. Their UX shows conversion % above each bar and absolute drop-off between bars.
- **Heap** also uses a stepped bar chart with percentage labels and an optional "group by" clause for segmentation — most actionable pattern for isolating *which* channel or cohort is leaking.

### Sankey

- Best for *free-path* analysis — "which route did users actually take?" (Datadog Product Analytics, Amplitude, Heap PathFinder). Not optimal for *ordered-step* funnels. Sankey requires a clickstream pixel (not available in Nexstage v1). Implementation cost is high; graph literacy required. Northbeam's "Sankey of top customer paths" was called out as **out of scope v1** in `docs/pages/attribution.md`.
- **Decision: skip Sankey.** Nexstage has no first-party clickstream pixel. The funnel is a defined ordered sequence (not open-path), so bar funnel matches the data model. Use horizontal bars (Plausible shape) — each step is a proportionally-wide bar, drop-off annotated between steps.

### Path tree

- GA4 Path Exploration — powerful but requires arbitrary event data; overkill for an ordered 5-step funnel. Deferred.

---

## Metric hierarchy at each step

Industry consensus (GA4 / Heap / Mixpanel / Plausible):

| Metric | Level |
|---|---|
| Sessions / unique users entering that step | Primary count |
| Conversion rate from previous step (step CVR) | Primary % |
| Drop-off count from previous step | Highlighted, often in red/amber |
| Overall funnel CVR (step vs entry) | Secondary |

Nexstage v1 mock data (realistic ecom rates):
- Landing page: 100,000 sessions (baseline = 100%)
- Product page view: 60,000 (60% step rate; 40,000 drop-off)
- Add to cart: 8,400 (14% of product-page viewers; 51,600 drop-off)
- Checkout start: 4,200 (50% of add-to-cart; 4,200 drop-off)
- Purchase: 2,100 (50% checkout completion; 2,100 drop-off; overall CVR = 2.1%)

---

## Drill-down UX patterns

- **Heap "group by" segmentation** — adding a breakdown (source / device / campaign) to any step is the most actionable pattern. Heap shows this as a segmented bar chart overlay, not a separate tab. Nexstage adapts this as a per-channel filter chip.
- **GA4 "segment comparison"** — show two segments side-by-side in the same funnel bars (e.g. Facebook vs Google). Nexstage adapts this as a source toggle that recolors bars.
- **Triple Whale Funnel Analytics board** — per-product drill with journey tracking from ad → product page → conversion. Shows "product interest" as the linking layer between paid traffic and purchase. This is exactly the feature request: products that attract ad traffic but don't convert.
- **Plausible** — click any step to see the list of pages / events that constitute that step. Nexstage: clicking "Product Page" or "Add to Cart" step opens a product breakdown drawer.

---

## Product-level vs aggregate

- Polar Analytics provides per-product LTV filtering and "first product purchased" cohort split.
- Triple Whale Product Journeys tracks which products drive acquisition → repeat.
- **Nexstage pattern**: show aggregate funnel as primary view, add per-product drill as second-level. Each product shows: views / add-to-cart rate / purchase rate / cart-but-no-purchase rate. This "cart abandonment per product" metric is the core insight of the feature request.

---

## Channel split pattern

- GA4 segments funnels by "segment" (e.g. device / source / campaign).
- Heap groups by UTM source/medium as first-class dimensions.
- **Nexstage**: source filter chips above the funnel — Real / Store / Facebook / Google / Organic / Direct / Email — matching the six canonical sources. Selecting a source re-renders funnel bars for that channel only. A stacked "all channels" view shows relative channel contribution per step.

---

## Winner / Overpriced flagging

- No direct competitor does this (the exact "high cart rate but low purchase rate = price resistance" signal).
- Closest: Heap anomaly detection, Polar product performance table with CVR column.
- **Nexstage pattern**: compute `cart_rate = add_to_cart / views`, `purchase_rate = purchases / add_to_cart`. Flag:
  - "Winner": purchase_rate ≥ 40% and cart_rate ≥ 20%
  - "Possible price-resistance": cart_rate ≥ 20% but purchase_rate < 15%
  - These thresholds are heuristic and configurable per workspace settings in v2.
- Competitor reference: closest is Varos' "North Star funnel decomposition" pattern (`_patterns_catalog.md` line 695) which decomposes CVR into sub-drivers to isolate the specific friction layer.

---

## Placement decision: `/flow` (new top-level route)

**Considered options:**

1. Sub-tab on `/attribution` (`/attribution?tab=flow`): Rejected. Attribution is about source disagreement / platform reconciliation. Flow is about user behavior sequence — different question, different data shape. Mixing them conflates attribution (which source gets credit?) with conversion behavior (where do users drop?). Also, attribution.md is explicitly desktop-only with a complex matrix layout; adding a funnel sub-tab risks visual congestion.

2. Sub-route on `/customers` (`/customers/flow`): Partially applicable — the funnel informs customer acquisition. But `/customers` already has 4 tabs (Segments / Retention / LTV / Audiences) and is customer-lifecycle focused, not session-funnel focused. The target user story is "where do ad-driven sessions drop before buying?" — that's acquisition/funnel territory, not CRM.

3. **New top-level `/flow` route**: Chosen. The feature is distinct enough to warrant a dedicated nav item. It combines acquisition traffic (from ads), session behavior (from GA4/store), and product performance — a synthesis that doesn't fit neatly under any existing page. Sidebar placement: between "Customers" and "Inventory" (or between "Attribution" and "SEO" given the ad-traffic angle). Uses the `Filter` or `GitMerge` icon from lucide-react.

**Competitor precedent:** Triple Whale ships "Funnel Analytics" as a top-level board, not a sub-tab. Heap has a dedicated "Funnels" section in its nav. GA4 puts Funnel Exploration under "Explore" (a separate top-level section). All three treat funnel as a first-class destination.

---

## Sources

- https://uxcam.com/blog/conversion-funnel-analysis/
- https://plausible.io/docs/funnel-analysis
- https://docs.mixpanel.com/docs/reports/funnels
- https://www.triplewhale.com/templates/web-analysis-funnel-analytics
- https://help.heap.io/hc/en-us/articles/18980595344028-Funnel-analysis-overview
- https://docs.datadoghq.com/product_analytics/journeys/sankey/
- https://www.polaranalytics.com/solutions/optimize-customer-acquisition-2
- https://www.analyticsmania.com/post/funnel-analysis-report-in-google-analytics-4/
- https://slickplan.com/blog/ecommerce-user-flow
