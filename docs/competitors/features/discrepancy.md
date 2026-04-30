---
name: Source / data discrepancy
slug: discrepancy
purpose: Surfaces *why* the same metric differs across sources (e.g. Meta-reported revenue vs Shopify orders vs GA4 sessions) so merchants can act instead of guess which "truth" to believe.
nexstage_pages: dashboard, ads, performance, seo, orders (any page that surfaces a metric Nexstage resolves through `MetricSourceResolver`)
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, adbeacon, lebesgue, elevar, hyros, conjura, fospha, ga4, northbeam, wicked-reports, motion, thoughtmetric
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/adbeacon.md
  - ../competitors/lebesgue.md
  - ../competitors/elevar.md
  - ../competitors/hyros.md
  - ../competitors/conjura.md
  - ../competitors/fospha.md
  - ../competitors/ga4.md
  - ../competitors/northbeam.md
  - ../competitors/wicked-reports.md
  - ../competitors/motion.md
  - ../competitors/thoughtmetric.md
  - https://www.adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/
  - https://docs.getelevar.com/docs/elevars-channel-accuracy-report
  - https://www.fospha.com/platform/core
  - https://www.wickedreports.com/funnel-vision
  - https://docs.northbeam.io/docs/what-is-northbeam-model-comparison-tool
  - https://support.google.com/analytics/answer/10596866 (GA4 Model comparison)
---

## What is this feature

Source/data discrepancy is the surface that answers the merchant's most-asked, least-answered post-iOS14 question: "Why does Meta say I made $X but my Shopify dashboard says $Y?" Every merchant already *has* the data — Meta Ads Manager, Shopify orders, GA4 sessions, and any first-party pixel each report their own number for "revenue from Meta last month." The data exists. What does not exist by default is the *side-by-side reconciliation* that lets the merchant see (a) the size of the gap, (b) which direction it moves in, and (c) the structural reason for it (view-through window, ad-blocker loss, attribution model, refunds, currency, time zone).

For SMB Shopify/Woo owners specifically, this matters because they can't afford an analyst to triangulate four browser tabs. The discrepancy feature is what turns "having data" into "trusting a decision." Strong reviewer evidence: Morgan Decker (Andie Swim) framing the entire problem as "the discrepancy between Last Click and ad platform data was totally astronomical" ([fospha](../competitors/fospha.md)); Steve R. choosing Triple Whale because it "demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics" ([triple-whale](../competitors/triple-whale.md)); the explicit AdBeacon agency quote, "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23" ([adbeacon](../competitors/adbeacon.md)). The differentiator across competitors is whether the discrepancy is a top-level surface (Hyros's "Reporting Gap" widget, Elevar's "Channel Accuracy Report") or a side-effect of the attribution screen (Polar's 3-column attribution table, Conjura's Last-Click vs Platform-Attributed columns).

## Data inputs (what's required to compute or display)

For each input, name the source + the specific field/event:

- **Source: Shopify / WooCommerce** — `orders.id`, `orders.total_price`, `orders.created_at`, `orders.refunds`, `orders.currency`, `orders.financial_status` — the "Real" / "Store" lens; serves as truth-of-record for orders that closed.
- **Source: Meta Ads API** — `ads_insights.spend`, `ads_insights.purchase_value` (platform-reported attributed revenue), `ads_insights.purchases` (count), `account_id`, `attribution_setting` (e.g., `7d_click_1d_view`), per-level (`ad`, `adset`, `campaign`).
- **Source: Google Ads API** — `metrics.cost_micros`, `metrics.conversions`, `metrics.conversions_value`, `metrics.all_conversions`, `metrics.all_conversions_value`, `conversion_action.attribution_model_settings`.
- **Source: GA4 Reporting API** — `sessions`, `purchases`, `purchaseRevenue` per `sessionDefaultChannelGroup` / `sessionSource` / `sessionMedium`, with `attributionModel` parameter; GA4's Model Comparison report exposes side-by-side conversion totals per model.
- **Source: GSC (Search Console API)** — `clicks`, `impressions`, `ctr`, `position` per `query` / `page` / `country` / `device`. Used for organic-search lens reconciliation.
- **Source: First-party pixel (Triple Pixel / Polar Pixel / Le Pixel / Tether / Hyros / Northbeam pixel)** — server-side `purchase` events with `click_id`, `device_id`, `customer_id`, `session_id`, `attribution_window_seconds`, `model_name` ("first_click", "last_click", "linear", "shapley", "markov", etc.).
- **Source: Computed (the gap itself)** — `delta_value = source_a_value - source_b_value`; `delta_pct = delta_value / source_b_value`; `match_pct = (success + ignored) / total_real`. (Elevar exposes match_pct per destination; Hyros exposes delta as the "Reporting Gap" widget.)
- **Source: User-input** — `attribution_window_default`, `view_through_enabled`, `currency_conversion_rate`, `time_zone`, `channel_mapping_overrides` — config that drives why two sources disagree even when underlying data is identical.

## Data outputs (what's typically displayed)

For each output, name the metric, formula, units, and typical comparisons:

- **KPI: Real revenue** — `SUM(orders.total_price - orders.refunds)`, currency, vs prior period delta. Always leftmost / authoritative in side-by-side layouts (Elevar's "Shopify" column, Conjura's leftmost rollup, Nexstage's "Real" badge).
- **KPI: Platform-reported revenue (per ad source)** — `SUM(ads_insights.purchase_value)` filtered to one platform + window. Compared 1:1 against Real.
- **KPI: First-party-pixel revenue** — `SUM(pixel.purchase_value)` per chosen attribution model. The "alternative truth" column.
- **KPI: GA4-attributed revenue** — `SUM(ga4.purchaseRevenue)` per sessionDefaultChannelGroup + selected GA4 attribution model.
- **Delta KPI: Gap value** — `platform_value - real_value` (or vs another anchor); units = currency + signed %; color-coded green/red on `% change` cell (GA4 model-comparison pattern).
- **Match rate** — `(success + ignored) / total_orders` per destination; %; threshold-banded (≥99% green, <95% red) — Elevar Channel Accuracy Report pattern.
- **Dimension: Source** — categorical, ~6 values (Real, Store, Facebook, Google, GSC, GA4) for Nexstage; competitors use 2–4 (Wicked: Wicked vs Facebook; Conjura: Last Click vs Platform; Polar: Platform / GA4 / Polar Pixel; Fospha: Last-click vs Fospha; Northbeam: 7 attribution models × Platform).
- **Dimension: Attribution model** — categorical, 1–10 values (Northbeam exposes 7; Polar 9–10; Lebesgue 5; AdBeacon 4; Conjura 2; GA4 3).
- **Breakdown: Source × Channel × Time** — table or stacked-bar grouped by channel with one column per source.
- **Slice: Per-campaign / per-ad / per-creative** — drill-down required for action (Northbeam: drill from channel → campaign → adset → ad; Hyros: row → lead journey).
- **Reason annotation** — text or icon explaining *why* a row diverges (Elevar surfaces "denied consent", "missing click ID", "sales channel filter" on the Ignored column hover).

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Attribution / Pixel Dashboard.
- **Visualization:** side-by-side multi-column table — Triple-Pixel attributed revenue vs platform-reported revenue vs first-click vs last-click vs "Total Impact"; channel breakdown rows with model selector at the top.
- **Layout (prose):** "Channel breakdown table with side-by-side columns for Triple Pixel attribution vs platform-reported metrics vs first/last-click. 'Total Impact' model is selectable as an attribution lens (Advanced+). On-demand refresh button mirrors the one on Summary (April 2026)."
- **Specific UI:** Attribution-model selector dropdown across the top; rows = channels; columns = attribution lenses. Switching the lens reflows the channel revenue numbers in place. Triple Whale's Email & SMS Attribution Dashboard (April 2026 beta) similarly shows Klaviyo / Attentive / Postscript / Omnisend revenue side-by-side.
- **Filters:** Date range, store, channel, attribution model, attribution window.
- **Data shown:** Spend, attributed revenue, ROAS, conversions per channel × per attribution lens.
- **Interactions:** Switch lens → live re-render. Drill from row to campaign / adset / ad. Moby Chat sidebar can natural-language-query the same data ("which campaign drove the most new customers last week?").
- **Why it works (from reviews):** "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra. Also: "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." (cuts both ways — users see the gap, but resolution is hard.)
- **Source:** [triple-whale](../competitors/triple-whale.md), https://triplewhale.com/blog/triple-whale-product-updates-april-2026.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Acquisition / Attribution surface.
- **Visualization:** three-column side-by-side table (Platform-reported / GA4 / Polar Pixel) per channel, with order-level drill-down view that shows the multi-touch journey for any single order.
- **Layout (prose):** "Side-by-side columnar view comparing platform-reported revenue vs GA4 vs Polar Pixel for the same window. Per swankyagency.com walkthrough: 'compare and contrast performance being reported by advertising platforms, GA4 and Polar.'"
- **Specific UI:** Attribution-model picker dropdown exposing 9–10 models (First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, Full Paid Overlap + Facebook Views, Full Impact). Click an order in the table to expand the multi-touchpoint customer journey timeline beneath the row.
- **Filters:** Date range, attribution model, channel, store, region (via "Views" saved-filter system).
- **Data shown:** Spend, attributed revenue, ROAS, CAC, conversions per (model × source) cell.
- **Interactions:** Switch model from dropdown → KPI block re-renders. Drill from channel → campaign → ad → order → customer journey.
- **Why it works:** "The ability to see (and trust!) our data at a high level gives us peace of mind." — Optimal Health Systems. Polar advertises "30–40% more accurate attribution data" than competitors' modeled pixel.
- **Source:** [polar-analytics](../competitors/polar-analytics.md), https://www.polaranalytics.com/post/attribution-models-shopify-brands.

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** **Chrome Extension overlay injected into Meta Ads Manager** (the differentiated surface) + in-app Optimization Dashboard.
- **Visualization:** in-context column injection — AdBeacon-tracked metrics rendered as additional columns alongside Meta's native Ad Set / Ad table inside facebook.com/adsmanager. In-app: side-by-side KPI tiles with attribution-model toggle.
- **Layout (prose):** "Overlays AdBeacon attribution data directly onto Meta's native Ad Set and Ad views (not at Campaign level). Displays 'platform-reported and independently-tracked data side-by-side.'"
- **Specific UI:** Browser extension panel with: side-by-side columns of Meta-reported vs AdBeacon-tracked; account-switcher dropdown for agencies; in-overlay attribution-model toggle (First Click / Last Click / Linear / Full Impact); customizable metric chooser. Distribution: Chrome Web Store. **Unique workflow primitive — no other competitor injects discrepancy data inside the platform UI being audited.**
- **Filters:** Ad set / Ad scope, attribution model, date range (inherited from Meta), account.
- **Data shown:** Tracked purchases, attribution-based revenue, orders, ROAS, custom KPIs — paired column-by-column with Meta's native numbers.
- **Interactions:** Switch attribution model in real-time inside Ads Manager; toggle between client accounts; customize visible metrics. No need to leave Meta to compare.
- **Why it works:** "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." — Agency testimonial, [adbeacon](../competitors/adbeacon.md).
- **Source:** [adbeacon](../competitors/adbeacon.md), https://www.adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/.

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Le Pixel attribution section + Advertising Audit.
- **Visualization:** per-order touchpoint timeline (drill-down from a single order to its full multi-channel journey) + an "Audit" rule-runner that flags 50+ tracking-config disagreements vs GA4/Meta/Google.
- **Layout (prose):** Le Pixel page screenshot description: "a detailed customer journey view and order information, including touchpoints like page views, add to cart, and conversion value." User picks attribution model from a 5-model selector (Shapley / Markov / First-Click / Linear / Custom), and the same order's attributed channel changes accordingly.
- **Specific UI:** Per-order touchpoint timeline rendered as vertical event log; attribution-model dropdown re-attributes inline. Color-coded performance indicators use **blue for improvements, red for declines** (unusual — most competitors use green for positive). Advertising Audit returns a list of "mistakes" with Meta/Google/TikTok/GA4 — implicit discrepancy surfacing.
- **Filters:** Date range, channel, attribution model.
- **Data shown:** Per-touchpoint channel/campaign/ad attribution, first-time vs repeat flag, subscription flag, conversion value.
- **Interactions:** Switch attribution model → same order re-attributes. Drill from order → journey → touchpoint.
- **Why it works:** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Fringe Sport, Shopify App Store, October 2025.
- **Source:** [lebesgue](../competitors/lebesgue.md), https://lebesgue.io/le-pixel.

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** Monitoring > **Channel Accuracy Report** (the most explicit discrepancy surface in the entire batch).
- **Visualization:** table — one row per configured destination (Meta CAPI, Google Ads, GA4, Klaviyo, …), with columns: **Shopify** (orders in period — the anchor), **Ignored**, **Success**, **% Match**, **Failures**.
- **Layout (prose):** "Table-style report, one row per configured destination. Columns: Shopify (total orders in period), Ignored (orders excluded), Success (orders successfully sent post-ignore-criteria), % Match (calculated as `(success + ignored) / total Shopify × 100%`), Failures (orders with API error responses)."
- **Specific UI:** Hover on "Ignored" cell drills into Server Events Log surfacing **specific reasons** ("sales channel filter, denied consent, missing click IDs/email"). Hover on "Failures" exposes "More details" link to per-error-code drill-down. Green/red logic is baked into the table — "APIs will essentially give you a 'thumbs up' or 'thumbs down' when receiving conversions."
- **Filters:** Date range, destination.
- **Data shown:** Order count by destination, ignore count, success count, % match, failure count.
- **Interactions:** Hover for reasons; drill from cell → Server Events Log → individual event payload.
- **Why it works:** "Our tracking is now much cleaner, giving us more confidence in our data and decisions." — Marie Nicole Clothing, April 2026. Elevar's positioning is fully built around this single surface — a 99% conversion-delivery guarantee with 30-day money-back is anchored to this report.
- **Source:** [elevar](../competitors/elevar.md), https://docs.getelevar.com/docs/elevars-channel-accuracy-report.

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** Dashboard / Quick Reports — **"Reporting Gap" widget** as a top-level dashboard component.
- **Visualization:** delta widget — single tile showing the difference between Hyros-attributed sales and ad-platform-reported sales (likely numeric value + signed delta + sparkline; specific UI not screenshotted in public sources).
- **Layout (prose):** "Widget grid; widget types include Live Stream (rolling list of incoming sales/leads), Hyros Insights (anomaly/opportunity callouts), Recent Reports (saved report shortcuts), and Reporting Gap (delta between Hyros-attributed sales and ad-platform-reported sales — likely the most distinctive widget). Drag-and-drop widget repositioning."
- **Specific UI:** Drag-and-drop widget grid; "Reporting Gap" sits alongside Live Stream and Insights as a peer KPI. Hyros also publishes a marketing-claim figure: **29–33% more sales captured** than native Ads Manager — that delta is the entire pitch.
- **Filters:** Date range, source / campaign.
- **Data shown:** Hyros-attributed sales, platform-reported sales, signed gap (% and absolute), trend.
- **Interactions:** Click widget → drill into per-source report; switch attribution model from row-level dropdown.
- **Why it works:** "the tracking and metrics are unbelievable. Its so awesome to have accurate data from all our paid and organic sources in one pane of glass dashboard." — Abd Ghazzawi, Trustpilot. Counter-evidence: "Hyros data sometimes does not match exactly with Facebook Ads Manager or other ad platforms, leading to confusion or distrust in the data." — Reddit r/FacebookAds.
- **Source:** [hyros](../competitors/hyros.md), https://community.hyros.com/x/change-log/yuvugsgz2fw8/.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Campaign Deepdive Dashboard.
- **Visualization:** dual-attribution side-by-side table — Last Click columns vs Platform Attributed columns, multi-platform (Google / Meta / TikTok / Bing / Pinterest).
- **Layout (prose):** "Side-by-side display of two attribution models — Last Click columns vs Platform Attributed columns (this is verbatim how the KPI definitions distinguish them). Drill-down hierarchy: campaign → ad group → ad."
- **Specific UI:** Two parallel column groups labeled "Last Click ROAS" / "Platform ROAS", "Last Click Conversions" / "Platform Attributed Conversions", "Last Click Revenue" / "Platform Attributed Revenue", "Last Click Conversion Rate" / "Platform Conversion Rate". KPI Scatter Chart sits above the table for ratio-vs-ratio outlier discovery.
- **Filters:** Date range, campaign, region, product category.
- **Data shown:** Ad spend, impressions, CPM, clicks, CTR, CPC, customers acquired, conversions / revenue / conversion rate / ROAS — each in both Last Click and Platform Attributed flavors.
- **Interactions:** Drill campaign → ad group → ad. SKU-level ad-spend attribution by parsing ad URL (Google Shopping / Performance Max / Facebook).
- **Why it works:** "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca.
- **Source:** [conjura](../competitors/conjura.md), https://help.conjura.com/en/articles/8867310-kpi-definitions-campaign-deepdive.

### Fospha ([profile](../competitors/fospha.md))
- **Surface:** Core (Daily MMM dashboard).
- **Visualization:** **horizontal grouped-bar chart — "Last-click vs. Fospha attribution"** — 4 bars per channel (paid social, Amazon, brand PPC, organic search) showing the attribution gap as the literal load-bearing visual story.
- **Layout (prose):** "Marketing illustrations on `/platform/core` show four chart types stacked. Top: horizontal bar chart of channel-attributed value changes with tooltips, with bar values ranging from -2.8k to +52.2k (positive deltas in one color, negative in another). Middle: comparative channel-performance bars across Email, Referral, PMAX, Direct. Below: side-by-side attribution comparison — 'Last-click vs. Fospha attribution' — across paid social, Amazon, brand PPC, organic search (literally a 4-bar group per channel showing the attribution gap)."
- **Specific UI:** Horizontal bars, color-coded positive/negative deltas, tooltip-on-hover with specific value, explicit "Last-click vs Fospha" framing as the comparison-chart label. Polaris Design System adopted in late-2025/early-2026 redesign.
- **Filters:** Date range, channel, KPI.
- **Data shown:** ROAS, MER, spend, attributed revenue/conversions per (channel × method) — last-click as parallel reference column.
- **Interactions:** Hover for value; drill channel → ad-level (Pro tier+).
- **Why it works:** "The discrepancy between Last Click and ad platform data was totally astronomical." — Morgan Decker, Andie Swim. Also: "Every time management challenged the numbers, I could open Fospha to prove what was really happening. Over time, Fospha became our source of truth for digital performance." — Rabee Sabha, ARNE.
- **Source:** [fospha](../competitors/fospha.md), https://www.fospha.com/platform/core.

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Advertising > Attribution > **Model comparison** report.
- **Visualization:** side-by-side table with `% change` color-coded delta cells — model A columns vs model B columns vs delta.
- **Layout (prose):** "Top: model selector A vs model selector B (defaults to Data-driven vs Cross-channel last click). Body: side-by-side table with rows = Default channel group (or Source/Medium/Campaign via dimension picker) and 3 metric columns per side: Conversions (model A), Conversions (model B), `% change` column. A 4th column 'Revenue' repeats the same trio. Color-coded delta cells (green ▲ for >0%, red ▼ for <0%) on the % change columns."
- **Specific UI:** Two sticky model-selector dropdowns at top; rows = channel group; per-row green ▲ / red ▼ on % change column. Date range applies to both models simultaneously.
- **Filters:** Date range, dimension (Default channel group / Source / Medium / Campaign), model A, model B.
- **Data shown:** Conversions count, Revenue, % change between models.
- **Interactions:** Switching either model re-renders both columns; switching dimension re-aggregates rows.
- **Why it works:** It's the canonical mental model of "compare two attribution truths" that every other competitor borrows. Counter-pressure: GA4 reduced model picker from 7 (UA) to 3 in 2023 — first-touch, linear, time-decay, position-based were removed.
- **Source:** [ga4](../competitors/ga4.md), GA4 Model Comparison help docs.

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Top-right hamburger (☰) > **Model Comparison Tool**.
- **Visualization:** side-by-side model columns, with optional **third column overlay of platform-reported numbers** for explicit reconciliation against Meta / Google self-reporting.
- **Layout (prose):** "Surface is 'purpose-built for seeing how different models interpret the same data without having to toggle back and forth manually.' Side-by-side comparison of attribution models — primary documented use case is 'Clicks Only vs Last Non-Direct Touch.' Designed to surface 'how revenue and transactions shift across models' rather than a single canonical number."
- **Specific UI:** Side-by-side model columns; export to CSV; ability to "overlay platform data (e.g., Google Ads)" as a third column. Seven Northbeam attribution models available (First Touch, Last Touch, Last Non-Direct Touch, Linear, Clicks-Only, Clicks + Modeled Views, Clicks + Deterministic Views). UI screenshot details not deeply documented in public sources.
- **Filters:** Attribution Model A vs Model B, attribution window (1d / 3d / 7d / 14d / 30d / 90d Click / LTV), accounting mode (Cash Snapshot vs Accrual), date range.
- **Data shown:** Per model: Attributed Revenue, Transactions; deltas highlighted as the primary insight.
- **Interactions:** Compare any two of the 7 models; export to CSV; overlay platform-reported numbers.
- **Why it works:** "Northbeam's data is by far the most accurate and consistent." — Victor M., Capterra. Their explicit philosophy is "don't pick one truth" — rare and pedagogically aligned with the Nexstage thesis.
- **Source:** [northbeam](../competitors/northbeam.md), https://docs.northbeam.io/docs/what-is-northbeam-model-comparison-tool.

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** **FunnelVision** report.
- **Visualization:** dual-column comparison table with TOF/MOF/BOF segmentation labels — Wicked-attributed ROAS columns next to Facebook-reported ROAS columns per campaign.
- **Layout (prose):** "Full-funnel view that 'automatically segment[s] clicks by the top, middle and bottom of your funnel' (verbatim, funnel-vision page). A core visual claim is **side-by-side comparison columns of Wicked-attributed ROAS vs. Facebook-reported ROAS**, per campaign — a direct two-source compare that's analogous in spirit to Nexstage's multi-source-badge thesis."
- **Specific UI:** TOF / MOF / BOF segmentation labels per click; "Cold Traffic" tag for conversions occurring more than 7 days before sale; toggleable lookback window; toggleable view-through impact slider ("Customized Meta View-Through Conversion Impact"); compare-mode toggle to overlay Facebook's reported numbers.
- **Filters:** Lookback window, view-through impact slider, funnel stage.
- **Data shown:** ROAS (Wicked-attributed), ROAS (Facebook-reported), spend, conversions, CAC at each funnel stage, cold-traffic ROAS.
- **Interactions:** Slider changes view-through impact in real time; toggle compare to overlay Facebook numbers.
- **Why it works:** Subscription/ReCharge re-attribution is the structural moat — "continuous update of revenue and ROI when subscriptions rebill" — past-period numbers continue to shift as cohorts mature.
- **Source:** [wicked-reports](../competitors/wicked-reports.md), https://www.wickedreports.com/funnel-vision.

### Motion ([profile](../competitors/motion.md))
- **Surface:** Inside Creative Reports — Pro+ tier.
- **Visualization:** side-by-side display of Facebook Ads Manager numbers vs GA4 numbers (Pro+); Pro+ also overlays Northbeam-attributed revenue/ROAS as an alternate lens.
- **Layout (prose):** "Side-by-side display of 'Facebook Ads Manager and Google Analytics data.'" Pro+ adds a Northbeam column for triangulation.
- **Specific UI:** Side-by-side metric columns; UI specifics not deeply documented in public sources.
- **Filters:** Date range, ad / creative scope.
- **Data shown:** Spend, conversions, ROAS per (creative × source) cell.
- **Interactions:** UI details not available beyond the cross-check description.
- **Why it works:** Motion is positioned as creative-analysis-first; the discrepancy view is a Pro+ supplement, not the main pitch.
- **Source:** [motion](../competitors/motion.md).

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Attribution comparison views — but quality contested.
- **Visualization:** Not directly observed; users explicitly request the feature in reviews.
- **Layout (prose):** Not observed; the relevant review evidence is *demand for the feature* rather than confirmation of its presence.
- **Specific UI:** No direct observation. Capterra reviewer literally asks for the surface: **"Need side-by-side platform vs. actual data comparison"** — Bill C., Founder (Sports), July 2022. Counter-evidence on quality: "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" — hugbel, Shopify App Store, March 2026 (1-star review). Anti-pattern observed — they have data discrepancies that they don't surface or explain.
- **Filters:** N/A (feature absent / unsurfaced).
- **Data shown:** N/A.
- **Interactions:** N/A.
- **Why it (doesn't) work:** Direct review demand for the feature confirms the latent merchant need. ThoughtMetric's failure to ship a credible discrepancy surface is the cautionary tale.
- **Source:** [thoughtmetric](../competitors/thoughtmetric.md).

## Visualization patterns observed (cross-cut)

Synthesis by viz type across the 13 competitors profiled above:

- **Side-by-side multi-column table (most common):** 7 competitors — Triple Whale, Polar (3-col Platform / GA4 / Polar Pixel), Conjura (Last Click / Platform Attributed), GA4 (Model A / Model B / % change), Northbeam (Model Comparison Tool, optional 3rd platform column), Wicked Reports (FunnelVision Wicked / Facebook), Motion (FB / GA4 / Northbeam). The dominant pattern. Universally treats one column as anchor and others as parallel lenses.
- **Delta KPI / "Reporting Gap" widget (rare but distinctive):** 1 competitor — Hyros. A single tile shows the signed delta between attributed and platform numbers as a top-level dashboard component peer-to-peer with revenue / ROAS tiles. This is the most opinionated, lowest-friction surface in the batch.
- **Match-rate / accuracy table (operational lens):** 1 competitor — Elevar (Channel Accuracy Report). Treats discrepancy as a *delivery-quality* problem — Shopify orders as truth, % match per destination as the KPI, hover-to-reveal reason on Ignored / Failures cells. Closer to monitoring than analytics.
- **In-platform overlay (Chrome extension):** 1 competitor — AdBeacon. Injects independent-attribution columns directly into Meta Ads Manager's native table. The only "discrepancy where the work happens" implementation; structurally different surface from in-app dashboards.
- **Horizontal grouped-bar comparison chart:** 1 competitor — Fospha. "Last-click vs Fospha attribution" rendered as a 4-bar group per channel, color-coded positive/negative deltas. The only viz where the chart *is* the comparison — others wrap the comparison in a table.
- **Per-order / per-lead drill-down timeline:** 3 competitors — Polar (order journey), Lebesgue (Le Pixel customer journey), Hyros (Lead Journey "Deep Mode"). Discrepancy at the *single-order* level rather than at aggregate; user can see exactly which touchpoints were credited per model.
- **Audit / rule-runner output:** 1 competitor — Lebesgue Advertising Audit (50+ tests across Meta / Google / TikTok / GA4 surfacing config disagreements as a list of "mistakes"). Implicit discrepancy surfacing rather than columnar.

Recurring conventions:
- **Color use:** Green ▲ / red ▼ for delta cells is the GA4-standard and copied by most competitors. Lebesgue is the outlier — **blue for improvements, red for declines**.
- **Anchor column placement:** Real / Store / Shopify column is consistently leftmost where it appears (Elevar's "Shopify" column, Fospha's MMM frame, Conjura's last-click). Nexstage's "Real" badge as leftmost matches this convention.
- **Model selector affordance:** Either at the top of the report (GA4, Polar, Northbeam, Wicked) or per-row (Hyros, Lebesgue). Page-level selector is more common.
- **Drill interaction:** Click cell → row / order / event detail. Universal across the side-by-side-table competitors.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Catching over-reporting on Meta**
- "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." — Agency testimonial, [adbeacon](../competitors/adbeacon.md).
- "The discrepancy between Last Click and ad platform data was totally astronomical." — Morgan Decker, Andie Swim, Fospha blog ([fospha](../competitors/fospha.md)).
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra, July 2024 ([triple-whale](../competitors/triple-whale.md)).

**Theme: Defendable numbers — internal trust as the value prop**
- "Every time management challenged the numbers, I could open Fospha to prove what was really happening. Over time, Fospha became our source of truth for digital performance." — Rabee Sabha, ARNE ([fospha](../competitors/fospha.md)).
- "The ability to see (and trust!) our data at a high level gives us peace of mind." — Optimal Health Systems, Polar ([polar-analytics](../competitors/polar-analytics.md)).
- "Our tracking is now much cleaner, giving us more confidence in our data and decisions." — Marie Nicole Clothing, Elevar ([elevar](../competitors/elevar.md)).

**Theme: One pane of glass across sources**
- "the tracking and metrics are unbelievable. Its so awesome to have accurate data from all our paid and organic sources in one pane of glass dashboard." — Abd Ghazzawi, Hyros Trustpilot ([hyros](../competitors/hyros.md)).
- "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Fringe Sport, Lebesgue ([lebesgue](../competitors/lebesgue.md)).
- "Brings everything from Shopify to Meta ads into one place." — Susanne Kaufmann, Polar ([polar-analytics](../competitors/polar-analytics.md)).

**Theme: Discovery of leakage**
- "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca ([conjura](../competitors/conjura.md)).
- "One campaign went from a 0.04 ROAS to 3.29, that's an 8,000%+ improvement." — Boveda, Elevar ([elevar](../competitors/elevar.md)).
- "Northbeam's C+DV showed us exactly how our Meta views were driving purchases. In the future, this will give us more confidence in allocating our spend across the funnel." — Vessi case study ([northbeam](../competitors/northbeam.md)).

## What users hate about this feature

**Theme: Discrepancy without explanation**
- "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" — hugbel, Shopify App Store, March 2026 ([thoughtmetric](../competitors/thoughtmetric.md), 1-star review).
- "Hyros data sometimes does not match exactly with Facebook Ads Manager or other ad platforms, leading to confusion or distrust in the data." — Reddit r/FacebookAds ([hyros](../competitors/hyros.md)).
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, workflowautomation.net, March 2026 ([triple-whale](../competitors/triple-whale.md)).

**Theme: Long learning period before numbers are trustworthy**
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, workflowautomation.net ([triple-whale](../competitors/triple-whale.md)).
- "It only worked for the first 6 months, after which it started over-reporting leads by 20-30%." — 2-year customer, Hyros Trustpilot ([hyros](../competitors/hyros.md)).
- Northbeam: "Profitability" right-rail panel literally stays empty until Day 90 — honest but a friction ([northbeam](../competitors/northbeam.md)).

**Theme: Independence concerns when the comparator is also a partner**
- "The tool measuring your Meta spend is also Meta's endorsed measurement partner. That creates obvious tension for brands seeking independent reads." — SegmentStream Fospha alternatives article ([fospha](../competitors/fospha.md)).
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce 2026 review ([triple-whale](../competitors/triple-whale.md)).

**Theme: User has to ask for it explicitly**
- "Need side-by-side platform vs. actual data comparison." — Bill C., Founder (Sports), Capterra, July 2022 ([thoughtmetric](../competitors/thoughtmetric.md)). Direct demand from a paying user — confirms latent need.

**Theme: Pricing creep tied to discrepancy improvement**
- Hyros tier billing is by tracked attributed revenue — the more accurate the attribution, the higher the bill. "43% post-renewal price hikes" recurring ([hyros](../competitors/hyros.md)).
- Polar: "Once your brand crosses the $5M GMV mark, costs can climb steeply, particularly if you want advanced features like pixel attribution." — Conjura comparison article ([polar-analytics](../competitors/polar-analytics.md)).

## Anti-patterns observed

Concrete examples of bad implementations and why they failed:

- **Hidden source disagreement (collapsing into a single "blended" number).** Triple Whale's default Summary tile shows blended ROAS / MER without surfacing which sources contributed and how much they disagreed. Reviewers note the resulting "ghost ROAS" feeling — "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models" ([triple-whale](../competitors/triple-whale.md)). The discrepancy is the information; collapsing hides it.
- **Discrepancy displayed without reasons.** ThoughtMetric surfaces tracking-accuracy disagreements but offers no explanation: "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" ([thoughtmetric](../competitors/thoughtmetric.md)). Elevar's Channel Accuracy Report is the counter-example — every Ignored cell hovers to a *reason* (denied consent / missing click ID / sales-channel filter).
- **Single-number "truth" framing on a multi-touch reality.** Hyros's positioning of "100% certainty" combined with reviewer reports that data "sometimes does not match exactly with Facebook Ads Manager or other ad platforms" ([hyros](../competitors/hyros.md)) creates expectation/reality mismatch. Selling certainty when the underlying methodology is probabilistic invites distrust.
- **Conflict-of-interest comparator.** Fospha is an officially endorsed measurement partner for Meta, TikTok, Reddit, Pinterest, Snapchat, and Google. Reviewers flag the structural concern: the entity measuring the gap is paid by the platform on the other side of the gap ([fospha](../competitors/fospha.md)).
- **Tier-gating the discrepancy view itself.** Motion's three-source comparison (FB / GA4 / Northbeam) is Pro+ only ([motion](../competitors/motion.md)); Northbeam's Model Comparison Tool is functional but lives behind a hamburger menu ([northbeam](../competitors/northbeam.md)). When discrepancy is the merchant's #1 question, hiding the answer behind a tier or a menu costs trust.
- **Long calibration period for a feature buyers expect to work on day 1.** Northbeam's 90-day Profitability unlock and Triple Pixel's 5–7-day stabilization mean the first weeks of "discrepancy data" are unreliable, but the UI doesn't always communicate this. Users react by distrusting the entire product.
- **Data freshness mismatch across compared sources.** Conjura refreshes nightly; Meta is real-time. Comparing the two without a "as-of" timestamp invites apparent discrepancies that are pure timing artifacts.

## Open questions / data gaps

What couldn't be observed from public sources:

- **Hyros "Reporting Gap" widget exact UI.** Public marketing dropped the iOS-reclaim percentage stat earlier this product cycle; the widget is referenced in the iOS app description and the redesign change-log but **no screenshot is publicly available**. Numeric format (absolute $ vs %), sparkline presence, and threshold coloring are inferred from the widget grid pattern. Would require a paid demo to confirm.
- **AdBeacon Chrome extension install count and UI quality.** Chrome Web Store consent gate blocked WebFetch — install count, reviews, and screenshots not retrievable. Functional description from AdBeacon's own marketing only.
- **Northbeam Model Comparison Tool UI.** Docs describe the function ("compare any two of the 7 models … overlay platform data") but **no public screenshots**; the Overview Walkthrough Video is embedded in their docs but not captured here. Cell-level interaction (sort, filter) unverified.
- **Polar 3-column attribution table screen capture.** swankyagency.com walkthrough describes the side-by-side comparison but the live UI sits behind a paid login.
- **Triple Whale Email & SMS Attribution Dashboard (April 2026 beta).** Just shipped; reviewer evidence not yet accumulated.
- **Lebesgue Advertising Audit "50 tests".** The list of which 50 tests run, and the rendering format of audit findings (chips vs cards vs prose), is not surfaced publicly.
- **Whether any competitor exposes a 6-source discrepancy view.** None observed. Maximum observed is Polar's 3 (Platform / GA4 / Polar Pixel) and Northbeam's "any 2 of 7 models + optional platform overlay." No competitor in the batch shows Real / Store / Facebook / Google / GSC / GA4 simultaneously. **GSC is the missing source** — only GA4 (and Triple Whale via passthrough) ingests it; only GA4 surfaces it inline next to attribution data, and even then it's siloed by Country/Device dimensions.
- **No competitor surfaces SEO discrepancy explicitly.** GSC clicks vs GA4 organic sessions vs Shopify orders attributed to organic — no one in the batch builds this triangulation. Confirms why the Nexstage feature index folded `seo-discrepancy` into general `discrepancy` rather than carving it out separately.

## Notes for Nexstage (observations only — NOT recommendations)

Open observations downstream synthesis can use:

- **Side-by-side multi-column table is the dominant pattern (7/13 competitors).** Real / Store / Facebook / Google / GSC / GA4 as 6 columns is a structural extension of this pattern — no competitor goes past 4 simultaneous columns. The "6 source badges" thesis pushes the column count further than market precedent.
- **Hyros's "Reporting Gap" widget is the only top-level dashboard discrepancy KPI in the batch.** Every other competitor buries discrepancy inside the attribution screen. A delta widget that lives next to revenue/ROAS on the dashboard would be precedent-light but reviewer-validated.
- **AdBeacon's Chrome extension is the only "in-platform overlay" implementation observed.** Injecting Nexstage source-comparison columns directly inside Meta Ads Manager / Google Ads UI would be precedent-light but workflow-relevant (media buyers don't want to switch tabs).
- **Elevar's Channel Accuracy Report column model — `Total / Ignored / Success / % Match / Failures` with reason-on-hover — is the cleanest *operational* discrepancy surface.** The reasons (denied consent / missing click ID / sales-channel filter) are exactly the structural reasons Real diverges from Facebook-attributed in Nexstage's model. The column structure could lift directly.
- **Color convention is green ▲ / red ▼ on delta cells (GA4 standard).** Lebesgue uses blue/red — explicitly cited as unusual. Nexstage's existing source color tokens include blues for Google/Facebook badges; a delta cell using blue would conflict.
- **Anchor column is universally Real / Store / Shopify on the left.** Matches Nexstage's existing leftmost-Real placement.
- **GSC is the source no competitor reconciles.** AdBeacon, Hyros, Wicked Reports, Northbeam, Conjura, Triple Whale, Polar, Fospha, Lebesgue — none ingest GSC. GA4 is the only one with GSC alongside attribution data, and GA4 silos GSC to Country/Device dimensions only. Direct whitespace for the GSC source-badge thesis.
- **Reason-annotation is rare but loved.** Elevar is the only competitor surfacing reasons inline (hover on Ignored). ThoughtMetric's 1-star review confirms the inverse — discrepancy without reasons destroys trust faster than no discrepancy view at all.
- **Calibration period framing matters.** Northbeam's Day 90 unlock and Triple Pixel's 5–7-day stabilization both create "discrepancy unreliable until X" windows. Nexstage's `RecomputeAttributionJob` retroactive-recalc UX (the "Recomputing…" banner) is conceptually similar — make the wait visible rather than hide it.
- **Independence is structurally a positioning lever.** Fospha-as-Meta-partner is reviewer-flagged. Nexstage isn't a paid measurement partner of Meta/Google — that independence can be made explicit copy on the discrepancy surface ("we don't get paid by the platform on the other side of this gap").
- **Subscription / cohort re-attribution shifts past-period numbers.** Wicked Reports's patents-pending re-pricing as ReCharge subscriptions rebill is the only competitor surfacing this, but it directly maps onto Nexstage's "ratios are never stored" rule and the cost-config retroactive-recalc trigger. Worth deciding whether the Recomputing banner UX should also fire on cohort maturation, not just config change.
- **GA4's Model Comparison Report is the cleanest baseline UI.** Two model dropdowns + side-by-side rows + `% change` color cells. Worth deep-screenshotting as a layout template.
- **5/13 competitors have a per-order journey drill-down for single-order discrepancy diagnosis.** Polar, Lebesgue, Hyros, Wicked, Conjura. This is a credible "discrepancy at the row level" pattern Nexstage's Orders page could borrow.
- **No competitor surfaces a full audit log of which `MetricSourceResolver` decision drove a number.** The transparency wedge — "this KPI used GA4 because Real had 0 sessions for that channel" — is unaddressed in the entire batch.
