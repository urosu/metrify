---
name: Channel mapping
slug: channel-mapping
purpose: Group raw UTM source/medium/campaign and referrer signals into a stable, trusted channel taxonomy merchants can use to compare apples-to-apples across paid, organic, email, referral and direct.
nexstage_pages: dashboard, performance, ads, seo, integrations (channel-mapping settings)
researched_on: 2026-04-28
competitors_covered: ga4, daasity, northbeam, polar-analytics, thoughtmetric, lifetimely, conjura, triple-whale, lebesgue, cometly, elevar, peel-insights, looker-studio
sources:
  - ../competitors/ga4.md
  - ../competitors/daasity.md
  - ../competitors/northbeam.md
  - ../competitors/polar-analytics.md
  - ../competitors/thoughtmetric.md
  - ../competitors/lifetimely.md
  - ../competitors/conjura.md
  - ../competitors/triple-whale.md
  - ../competitors/lebesgue.md
  - ../competitors/cometly.md
  - ../competitors/elevar.md
  - ../competitors/peel-insights.md
  - ../competitors/looker-studio.md
  - https://docs.northbeam.io/docs/manage-breakdowns
  - https://support.google.com/analytics/answer/9756891 (GA4 Default Channel Group)
  - https://intercom.help/polar-app/en/articles/5563128-understanding-views
  - https://docs.getelevar.com/docs/how-does-attribution-feed-work
---

## What is this feature

Channel mapping is the rule layer that converts raw acquisition signals — UTM parameters, ad-platform click IDs (`gclid`, `fbclid`, `ttclid`), HTTP `Referer` headers, post-purchase survey answers, and discount codes — into a stable, named taxonomy of "channels" (Paid Social, Paid Search, Email, Organic Search, Direct, Affiliate, Influencer, Referral, etc.). It is the difference between a Shopify orders table that says `utm_source=fb_ig | utm_medium=cpc-pmax-test_03 | utm_campaign=BFCM-2024-prospecting-DPA` and a dashboard row that says "Paid Social — Meta — Prospecting." Without a mapping layer, every chart is at the mercy of whatever string the media buyer happened to type into the campaign builder six months ago; with one, every cohort, LTV, ROAS and SEO surface uses the same dictionary.

For SMB Shopify/Woo merchants the feature is acutely load-bearing because their UTM hygiene is, in practice, awful. Two media buyers, one agency, three platforms, four years of campaigns and a half-dozen hand-typed utm_sources (`facebook`, `Facebook`, `fb`, `meta`, `IG`, `ig-stories`) collapse into the same row only when a mapping engine normalises them. Every competitor in this category has built one — but they differ wildly in (a) whether the rules are user-editable, (b) whether mapping is centralised or implicit per-report, (c) whether the channel hierarchy is flat or grouped, and (d) whether merchants get a UTM tag generator that keeps source/medium values disciplined at the input side.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.referring_site`, `orders.landing_site_ref`, `orders.source_name`, `orders.note_attributes` (UTM payload), `orders.discount_codes[].code`, `customers.first_order.referring_site`, `orders.client_details.user_agent` (for Direct vs unknown disambiguation).
- **Source: First-party pixel (Triple Pixel / Polar Pixel / Le Pixel / Cometly Pixel / Elevar)** — session-level UTM/click-id capture, server-set 1-year cookie for returning-user identity, multi-touch touchpoint sequence per `customer_id`.
- **Source: Meta / Google / TikTok / Pinterest / Snapchat / Bing / LinkedIn / Reddit Ads** — campaign / ad-set / ad name strings, campaign objective, channel/sub-channel platform metadata, click IDs (`gclid`, `wbraid`, `gbraid`, `fbclid`, `ttclid`, `epik`, `sccid`, `msclkid`, `li_fat_id`).
- **Source: GA4** — `Default Channel Group` (24-channel taxonomy, rule-based on source/medium/campaign), `Source / Medium`, `Session source`, `First-user source`, `Manual campaign name`, plus the linked Search Console organic-query feed.
- **Source: GSC (Google Search Console)** — query, landing page, country, device — feed Organic Search sub-attribution and brand vs non-brand splits.
- **Source: Klaviyo / Postscript / Attentive / Omnisend / Yotpo SMS** — campaign + flow `utm_campaign` / `utm_medium=email|sms`, message ID per send, attributed `value`.
- **Source: Survey vendors (Fairing, KnoCommerce, ThoughtMetric, Polar, Triple Whale)** — verbatim "How did you hear about us?" response, custom answer-choice → channel mapping table, optional "other" free-text classifier.
- **Source: Discount codes** — promo-code → channel/vendor mapping for podcast / influencer / affiliate channels (Daasity, Conjura).
- **Source: User-input rules** — pattern conditions (`utm_source IS facebook`, `utm_medium IN ['cpc','ppc']`, `referrer CONTAINS google.com`, `utm_campaign STARTS_WITH 'PMax'`), priority/rank, fallback channel, "Other" bucket label.
- **Source: User-input taxonomy seed** — default channel list (Direct, Organic Search, Paid Search, Paid Social, Email, SMS, Affiliate, Influencer, Referral, Display, Video, Audio/Podcast, Other), optionally renamed per workspace.

## Data outputs (what's typically displayed)

- **Dimension: Channel** — string, ~10–25 distinct values; primary group-by on every revenue / spend / cohort table.
- **Dimension: Sub-channel / Platform** — Paid Social → Meta / TikTok / Pinterest / Snapchat; Paid Search → Google / Bing.
- **Dimension: Campaign type / Targeting** — Prospecting / Retargeting / Branded Search / Performance Max / Display / Retargeting (Northbeam Breakdowns).
- **Dimension: Revenue Source** — Online Store vs Amazon vs Walmart vs eBay (omnichannel rollup).
- **Breakdown: Revenue × channel × time** — stacked bar / line / table.
- **Breakdown: Spend × channel × ROAS / CAC** — channel rollup of platform spend keyed to mapped channel.
- **Breakdown: Channel × attribution-model** — same channel rendered under First-Click / Last-Click / Linear / U-shaped / Survey / Vendor-Reported.
- **Slice: Per-cohort × first-touch channel** — LTV by acquisition channel.
- **Slice: Brand vs non-brand organic** — sub-split of Organic Search.
- **KPI: Channel mix %** — share of revenue/orders by channel, top 5 + "All others" rollup.
- **Admin output: Mapping audit / "unmapped" bucket** — count of orders whose UTM/referrer didn't match any rule.
- **Admin output: UTM tag generator** — UI that builds a campaign URL from a fixed source/medium dropdown, ensuring values stay in sync with the mapping rules downstream.

## How competitors implement this

### Google Analytics 4 ([profile](../competitors/ga4.md))
- **Surface:** Admin > Property Settings > Data display > Channel groups; the resulting `Default channel group` and `Session default channel group` dimensions then appear as the default group-by on every Acquisition / Traffic Acquisition / Reports snapshot card.
- **Visualization:** rule-list editor + read-only sortable table (24 default channels, e.g. `Direct`, `Organic Search`, `Paid Search`, `Paid Social`, `Organic Social`, `Email`, `Affiliates`, `Referral`, `Audio`, `SMS`, `Mobile Push Notifications`, `Display`, `Cross-network`, `Unassigned`).
- **Layout (prose):** Top: page header with date range and a "Manage custom groups" button. Left: a vertical rule list, each row a channel name + a stack of OR'd condition pills (e.g. `Source matches regex ^(facebook|fb|instagram|ig)$ AND Medium matches regex ^(cpc|ppc|paid)$`). Right: live preview pane showing how many sessions in the selected window fall into each bucket. Default rules are read-only and shown as locked grey rows; user-defined Custom channel groups appear above the defaults and are dragged to set priority.
- **Specific UI:** The rule editor uses a stacked condition-builder (per-row dropdown of dimension → operator → value, with "OR" between rows in the same channel and an implicit cascade between channels — first match wins). The "Cross-network" channel is a special bucket for Google Ads Performance Max + Demand Gen that span paid search + display + YouTube. "Unassigned" is the catch-all the help docs warn users to drive to zero.
- **Filters:** Date range, comparison.
- **Data shown:** Channel name, Sessions, Users, Engaged sessions, Conversions, Total revenue, plus the underlying source/medium/campaign for any drilled row.
- **Interactions:** Drag-to-reorder priority; clone default channel as starting point; regex / exact-match / contains / begins-with operators; "Hide channel" toggle. Custom channel groups apply only forward unless a property-wide reprocess is initiated.
- **Why it works (from reviews/observations):** "GA4's data model is excellent" — Dana DiTomaso (MeasureSchool survey, 2023). The 24-channel taxonomy is also the implicit shared dictionary every other tool's rules try to mirror.
- **Source:** [ga4.md](../competitors/ga4.md); https://support.google.com/analytics/answer/9756891

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Top-right ☰ "next to the maintenance alerts icon" → **Manage Breakdowns** (a tabbed admin page).
- **Visualization:** table of breakdowns + per-row rule editor; output renders as additional row groupings everywhere else in the app.
- **Layout (prose):** Top: page header titled "Manage Breakdowns" with an "Add Breakdown" button. Body: list of all breakdowns in the account, four pre-configured types — **Platform** (Facebook Ads, Google Ads, TikTok, etc.), **Category** (Paid Prospecting, Performance Max, etc.), **Targeting** (Branded Search, Display, Retargeting, etc.), **Revenue Source** (Online Store vs Amazon). Editing a default is done by creating a new breakdown with the same name as the default — explicit override-by-shadow pattern.
- **Specific UI:** Four orthogonal dimensions ship out-of-the-box as separate breakdown axes — not one channel column but four (Platform × Category × Targeting × Revenue Source). Users can create a new breakdown of any of those four types, then write rules that match against campaign-name patterns and assign each campaign to a bucket. Rule overrides default; default is shown as locked grey row.
- **Filters:** None on the admin page; downstream filters use the breakdowns as group-by axes on Attribution Home, Creative Analytics, Product Analytics.
- **Data shown:** Breakdown name, type, rule count, status. The breakdowns become row-groupings on every attribution table.
- **Interactions:** Add Breakdown; clone-then-shadow a default; rule reorder. Affects every dashboard simultaneously.
- **Why it works (from reviews/observations):** Validated taxonomy defaults — multiple Capterra/Trustpilot reviewers reference the campaign-grouping clarity as Northbeam's strength: "Northbeam's depth of attribution modeling is genuinely best-in-class" (Head West Guide review, 2026, [northbeam.md](../competitors/northbeam.md)).
- **Source:** [northbeam.md](../competitors/northbeam.md) > Breakdowns Manager; https://docs.northbeam.io/docs/manage-breakdowns

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Top of any dashboard / data-source switcher > **Views** dropdown. Polar treats channel mapping as a special case of saved-filter-set ("Views").
- **Visualization:** dropdown of named Views grouped into Collections; selecting a View re-filters every block on the dashboard.
- **Layout (prose):** Top of dashboard: View dropdown + comparison toggle. Inside the View editor: two filter scopes — **Global Filters** (apply uniformly across all sources) and **Individual Filters** (per-source rules). Operators: `is`, `is not`, `is in list`, `is not in list`. Common Collections seeded as: by store, by country/region, by product, by sales channel.
- **Specific UI:** Per-source rule rows (one for Shopify, one for Meta, one for Google, etc.) with `is in list` operator letting users paste a list of UTM source values that should all roll up to the same View. The semantic layer joins them under one channel string.
- **Filters:** Per-source dimensions (Shopify exposes 40+ dimensions alone), currency adjustment is part of a View.
- **Data shown:** No mapping table per se — channel grouping is implicit in which Views you save. Views appear as chips at top of dashboard.
- **Interactions:** Save View, share View, currency-toggle inside View, set as default. **Important quirk explicitly documented in their help center:** "Views combine with 'OR' logic, not 'AND.'" Multiple active Views union rather than intersect — docs warn users to put all filters into a single View if they need AND semantics.
- **Why it works (from reviews/observations):** "Best analytics tool I've ever used" (anonymous US reviewer, [polar-analytics.md](../competitors/polar-analytics.md)). The flexibility of saved Views replaces what other tools call "channel mapping rules."
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md); https://intercom.help/polar-app/en/articles/5563128-understanding-views

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Marketing Attribution dashboard channel-list (channel rows are the primary axis).
- **Visualization:** flat channel list with per-channel metric tiles (Spend / ROAS / MER / Sales) and a bar chart for comparative analysis.
- **Layout (prose):** Channels enumerated on the marketing page: "Meta Ads, TikTok, Pinterest, Google Ads, Bing, organic social, email/SMS, podcasts, influencers, affiliates, and UTM-based custom channels." Hard-to-track channels (podcasts, billboards) feed the channel taxonomy through post-purchase survey answers, which become rows on the same page as paid channels.
- **Specific UI:** "UTM-based custom channels" — listed verbatim alongside the platform-derived channels, implying user-defined UTM rules can spawn additional channel rows in the same view. UI of the rule editor itself is **not directly observable from public sources** (live app paywalled).
- **Filters:** Date range, attribution model selector (Multi-Touch / First Touch / Last Touch / Position Based / Linear Paid), attribution window (7 / 14 / 30 / 60 / 90 days).
- **Data shown:** Per-channel Spend, ROAS, MER, attributed Sales, attributed Orders.
- **Interactions:** Switch attribution model, change window, drill from channel into campaign.
- **Why it works (from reviews/observations):** "trusting attribution from ad platforms will lead you to make budgeting mistakes" — WIDI CARE (Shopify App Store, December 2024, [thoughtmetric.md](../competitors/thoughtmetric.md)). Survey-fed channels (podcasts, influencers) sit on the same table as paid channels, eliminating the "where do I find offline?" gap.
- **Source:** [thoughtmetric.md](../competitors/thoughtmetric.md)

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Attribution Report (Sidebar > Attribution).
- **Visualization:** tabular layout — channel rows with side-by-side platform-reported vs. Lifetimely Pixel-attributed columns.
- **Layout (prose):** Channel rows enumerated: Facebook, Instagram, Google, TikTok, Snapchat, Pinterest, Microsoft. Columns: reported revenue, spend, CPC, CAC, ROAS. Lifetimely's own pixel data shown alongside platform-reported numbers — explicit side-by-side comparison.
- **Specific UI:** No public-facing user-editable channel-mapping rule editor observed. Channel rows are derived from connected ad-platform integrations (one row per platform) plus implicit Shopify referrer/UTM mapping for non-paid traffic. Cohort filtering uses `source/medium` directly as a dimension rather than a remapped channel string.
- **Filters:** Date range, attribution model toggle (first-click vs. last-click), anomaly alert layer.
- **Data shown:** Per-channel reported revenue, spend, CPC, CAC, ROAS, plus pixel-attributed revenue.
- **Interactions:** Date range, model toggle, anomaly drill.
- **Why it works (from reviews/observations):** "best-in-class for a Shopify app, allowing you to segment customers by first purchase date, first product purchased, acquisition channel, geography, and more" ([lifetimely.md](../competitors/lifetimely.md)). The cohort report exposes channel directly as one of the cohort filter dimensions — no separate mapping admin needed because the channels are platform-derived.
- **Source:** [lifetimely.md](../competitors/lifetimely.md)

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Settings (implied — Conjura's marketing copy says "Channel definitions are user-customizable: mark a channel as ad vs. marketing"); channels surface across Performance Overview, Campaign Deepdive, New vs Existing Customers, LTV Analysis.
- **Visualization:** dual-attribution column pair on Campaign Deepdive (Last Click vs Platform Attributed) is the practical "channel mapping" output — every channel renders twice, once per source-of-truth.
- **Layout (prose):** Campaign Deepdive shows hierarchical drill: Campaign → Ad Group → Ad. Side-by-side column pairs for Last Click (Conjura's own session-based attribution, derived from UTMs landing on the Shopify storefront) and Platform Attributed (passed through from Meta/Google/TikTok). KPI Scatter Chart plots two ratio metrics with channel/campaign as bubbles.
- **Specific UI:** "Discount Code Attribution" is treated as a parallel channel dimension — users register a discount-code-to-channel mapping (e.g. `PODCAST20` → Podcast → "Joe Rogan Show") and Conjura assigns credit to that channel/vendor for any order using the code. Full rule editor UI **not directly observable from public sources** — described in help docs but not screenshotted.
- **Filters:** Store, channel, territory, region, product category.
- **Data shown:** Per channel: Spend, Impressions, CPM, Clicks, CTR, CPC, Customers Acquired, Last Click Conversions/Revenue/CR/ROAS, Platform Attributed Conversions/Revenue/CR/ROAS, plus Contribution Profit per channel.
- **Interactions:** Toggle attribution mode (Last Click vs Platform), filter by region/category, drill from chart to specific campaign.
- **Why it works (from reviews/observations):** "I can see my contribution margin down to an SKU level, so I know where I should be paying attention" — Bell Hutley (Shopify App Store, March 2024, [conjura.md](../competitors/conjura.md)). The dual-column pattern lets merchants see the disagreement at the channel level without leaving the page.
- **Source:** [conjura.md](../competitors/conjura.md)

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Templates Library > Acquisition Marketing > **Attribution Deep Dive** (built on the "Marketing Attribution explore"). Mapping logic lives behind a **"Dynamic Attribution Method" filter-only field** at the top of the dashboard plus a "Customizing Attribution Logic" ranking control.
- **Visualization:** dimensional pivot inside one explore — channel × vendor × eight attribution models, plus a discount-code performance table and an "assisted lift" visualization.
- **Layout (prose):** Single attribution explore with **eight selectable attribution models** as filterable values: First-Click, Last-Click, Assisted, Last-Click + Assisted, Last Ad Click, Last Marketing Click, Survey-Based (Fairing-driven), Vendor-Reported. Plus **Custom Attribution** — "uses a waterfall approach to sift through multiple attribution data sources" with user-defined priority ranking (e.g. survey → discount-code → GA last-click). Plus **Discount Code Attribution** as a parallel dimension.
- **Specific UI:** Survey-Based Attribution exposes three explicit dimensions in the Order Attribution view: **Survey Response** (verbatim text), **Survey-Based Channel**, **Survey-Based Vendor** — i.e. the raw answer plus its mapped channel and mapped vendor live as separate queryable dimensions. Channel definitions are user-customizable and "mark a channel as ad vs. marketing" is a first-class attribute.
- **Filters:** Channel, vendor, attribution method (filter-only field — switches without rebuilding the report), date range, store-type filter, metric filter (CPA / CPO / gross margin / net sales / ROAS / orders / new-customer orders).
- **Data shown:** CPA, CPO, gross margin, net sales, gross sales, ROAS, orders, new-customer orders — by channel × vendor × attribution model.
- **Interactions:** Toggle between models via the Dynamic Attribution Method filter without rebuilding; rank models for the Custom Attribution waterfall; UTM dimension drill-down for GA-based models.
- **Why it works (from reviews/observations):** "the best tool I've used. The customer support is unparalleled" — Béis (Shopify App Store, March 2022, [daasity.md](../competitors/daasity.md)). Departmentally-organised IA and the eight-model toggle let analysts answer multiple stakeholders from one explore.
- **Source:** [daasity.md](../competitors/daasity.md)

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Summary Dashboard (collapsible sections by data integration — Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics) + Pixel/Attribution dashboard with attribution-model selector + Custom Metric Builder.
- **Visualization:** dual-column attribution table (Triple Pixel-attributed vs platform-reported per channel) on the Attribution dashboard; collapsible source-grouped tile sections on Summary.
- **Layout (prose):** Summary's body is organized as **collapsible sections by data integration** — by default sections include Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics (Triple Pixel), Custom Expenses. Each section is a grid of draggable metric tiles (channel-keyed). The Attribution dashboard renders side-by-side Triple Pixel vs Meta-reported vs first/last-click columns per channel; "Total Impact" is selectable as a separate model.
- **Specific UI:** Channel grouping in Triple Whale is platform-derived (one section per integration) rather than user-rule-driven. The Custom Metric Builder lets users compose channel-aware composite metrics (e.g. blended ROAS across mapped channels). UI of any explicit rule-editor for UTM → channel **not directly observable from public sources** — KB pages 403'd to WebFetch.
- **Filters:** Store switcher, date range, attribution model, channel/section visibility toggle.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend, Sessions, Conversion Rate, Refund Rate, plus per-platform spend/ROAS sub-tiles.
- **Interactions:** Drag-and-drop tile reorder; pin to "Pinned" section; collapse/expand source groups; pivot to table view; on-demand refresh button (April 2026).
- **Why it works (from reviews/observations):** "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics" — Steve R. (Capterra, July 2024, [triple-whale.md](../competitors/triple-whale.md)). Pixel-vs-platform side-by-side is precisely the channel-mapping disagreement merchants want to see.
- **Source:** [triple-whale.md](../competitors/triple-whale.md)

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Business Report channel taxonomy — channels enumerated as Google / Meta / TikTok / Microsoft / Pinterest / Klaviyo / Amazon / email / organic / **ChatGPT** (notable inclusion of LLM-referrer as a first-class channel).
- **Visualization:** line + bar charts in Business Report, Compare Metrics tool plots a single metric trend per channel.
- **Layout (prose):** User picks metrics + period; output renders with channels as series. "Color-coded performance indicators (blue for improvements, red for declines)" — they use **blue, not green**, for positive deltas, which is unusual.
- **Specific UI:** Channel taxonomy is broader than peers — **ChatGPT is exposed as a channel**, suggesting Lebesgue is mapping LLM-referrer hostnames (`chat.openai.com`, `chatgpt.com`, etc.) into a dedicated bucket. UI for editing channel rules **not directly observable** from public marketing.
- **Filters:** Marketing channel, product, geography (limited — country-level breakdown is a documented gap per Tomás Manuel J., Capterra Feb 2026).
- **Data shown:** Revenue, First-time Revenue, Ad Spend (per channel), COGS, Profit, ROAS.
- **Interactions:** Pick metric → pick range → auto-generate; downloadable Business Overview Table.
- **Why it works (from reviews/observations):** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible" — Fringe Sport (Shopify App Store, October 2025, [lebesgue.md](../competitors/lebesgue.md)).
- **Source:** [lebesgue.md](../competitors/lebesgue.md)

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** AI Ads Manager (unified table across Meta + Google + TikTok + LinkedIn) + per-lead Conversion Profile / Customer Journey view.
- **Visualization:** unified ad-platform rows in the Ads Manager; per-lead chronological touchpoint timeline in Conversion Profiles.
- **Layout (prose):** Tabular ad-account view with rows for campaigns / ad sets / ads from multiple platforms — channel mapping is implicit in the platform integration. An attribution-model switcher (First Touch / Last Touch / Linear / U-Shaped / Time Decay) sits inline. Per-lead Conversion Profile shows "complete customer journeys mapping interactions across paid ads, organic search, referrals, and direct traffic before and after conversion."
- **Specific UI:** Channel taxonomy is implicit per-platform; no dedicated rule editor surfaced. Custom-event configuration ("Create custom events in just a few clicks") is the closest analog — users add lead/conversion events and Cometly attributes them to whichever upstream channel touched the journey.
- **Filters:** Customizable column picker, attribution-model dropdown, conversion-window selector (30/60/90 days), source / campaign / touchpoint filters.
- **Data shown:** Spend, impressions, clicks, conversions, revenue (Cometly-attributed), ROAS, CPA, custom metrics.
- **Interactions:** Drill campaign → ad set → individual ad; switch attribution model and watch credit redistribute; AI Chat panel embedded for write-back budget changes.
- **Why it works (from reviews/observations):** "I am very impressed with the tracking accuracy. I would say it tracks about 90% of my orders" — Leo Roux, Petsmont ([cometly.md](../competitors/cometly.md)).
- **Source:** [cometly.md](../competitors/cometly.md)

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** **Attribution Feed** (beta) — sidebar > Attribution Feed.
- **Visualization:** sortable table; one row per customer pathway (combination of First Touch + Last Touch UTMs).
- **Layout (prose):** Each row represents a unique First-Touch/Last-Touch UTM tuple with revenue and order count. Sortable by revenue. Excel/CSV exportable. Beta badge — docs explicitly note "in beta as we add more filtering and channel translation."
- **Specific UI:** Columns: First Touch UTMs (source/medium/campaign), Last Touch UTMs, Last Touch Organic Referrer (when no UTMs), revenue, order count. Notable design choice: **"Last Touch Organic Referrer (when no UTMs)"** — when UTMs are missing, Elevar falls back to the HTTP referrer hostname as the channel signal, surfacing it as a separate column rather than collapsing it into "Direct."
- **Filters:** Date range; filtering capability flagged as in-progress in beta.
- **Data shown:** First Touch UTMs, Last Touch UTMs, Last Touch Organic Referrer, revenue, order count.
- **Interactions:** Sort by revenue, export.
- **Disclaimer captured verbatim from docs:** "not meant to be a replacement for Google Analytics" and "excludes first-touch organic referrals and alternative attribution models like Data-Driven attribution."
- **Why it works (from reviews/observations):** "Our tracking is now much cleaner, giving us more confidence in our data and decisions" — Marie Nicole Clothing (Shopify App Store, April 2026, [elevar.md](../competitors/elevar.md)). Elevar's pitch is plumbing, not analytics — the Attribution Feed is intentionally primitive.
- **Source:** [elevar.md](../competitors/elevar.md)

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Sidebar > Attribution sub-pages (Orders by Channel, Revenue by Channel, New Revenue by Channel, Returning Revenue by Channel, Payback Period, Multi-Touch Attribution Segments).
- **Visualization:** channel rows in a flat table with per-channel KPI tiles for LTV / AOV / ROAS / Payback Period.
- **Layout (prose):** Channel breakdown across "Facebook, Instagram, Paid Search, Organic Search, TikTok, Twitter, Pinterest, etc. (13 in total)." Rolling 7-day or 30-day windows for revenue and orders. Channel mapping fed from "Google Analytics definition and ads social media sources" plus Shopify first/last visits.
- **Specific UI:** Hybrid mapping — Peel layers GA's channel-group definitions with ad-platform-derived channels and Shopify referrer/first-visit data. No public rule editor for end-users surfaced; mapping is opinionated and ships out-of-the-box.
- **Filters:** Date range, window (7d/30d), channel.
- **Data shown:** Orders by Channel, Revenue by Channel, ROAS, ROI, CAC, LTV-to-CAC, Payback Period.
- **Interactions:** "With two clicks you can determine the LTV/AOV (& a suite of other metrics) by channels"; drill into channel for cohort/LTV view.
- **Source:** [peel-insights.md](../competitors/peel-insights.md)

### Looker Studio ([profile](../competitors/looker-studio.md))
- **Surface:** Per-template channel grouping pie / channel-mix table; templates inherit GA4's `Default channel group` dimension directly.
- **Visualization:** channel-grouping pie chart + landing-page table in the standard ecommerce templates.
- **Layout (prose):** Looker Studio has no native channel-mapping admin; it consumes GA4's pre-computed `Default channel group` dimension as-is. Authors can create calculated fields with regex/case statements over `source/medium` to override, but each report has its own copy of the rule — no shared mapping layer.
- **Specific UI:** Calculated field editor uses CASE / REGEXP_MATCH formulas. Rule lives at report-level, not workspace-level.
- **Filters:** Standard Looker Studio filter chips per page.
- **Data shown:** Channel-grouping pie of sessions/revenue, top sources, brand vs non-brand split (via regex calc field).
- **Source:** [looker-studio.md](../competitors/looker-studio.md)

## Visualization patterns observed (cross-cut)

- **Rule-list editor with regex/exact-match operators + locked default rules:** 2 competitors — GA4 (24-channel default + custom groups), Northbeam (Breakdowns Manager with override-by-shadow). The most explicit / transparent pattern; matches users' mental model of "if/then channel."
- **Saved-filter / View pattern (channel = a saved filter set, not a row):** 1 competitor — Polar Analytics. Flexible but has a documented OR-vs-AND gotcha that drives miscounted dashboards.
- **Source-grouped collapsible tile sections (channel = data integration):** 1 competitor — Triple Whale Summary. Implicit mapping; users can't author their own taxonomy.
- **Dual-column dual-attribution table (channel rendered twice — pixel/store vs platform):** 3 competitors — Conjura (Last Click vs Platform Attributed), Lifetimely (pixel vs reported), Triple Whale (Triple Pixel vs platform). Validates "show the disagreement" UX.
- **Filter-only-field model picker over a single explore (multiple attribution lenses, one report):** 1 competitor — Daasity (Dynamic Attribution Method, eight models). Most powerful pattern for analysts; least obvious for SMB operators.
- **First-Touch + Last-Touch + Organic-Referrer triplet table:** 1 competitor — Elevar Attribution Feed (beta). Acknowledged primitive; explicitly disclaimed as "not GA4 replacement."
- **Survey-fed channels co-listed with paid channels in same table:** 3 competitors — ThoughtMetric, Daasity (Survey-Based Channel dimension), Triple Whale (post-purchase survey). Treats "How did you hear about us?" as first-class channel input.
- **Discount-code-as-channel parallel dimension:** 2 competitors — Daasity (Discount Code Attribution), Conjura (URL-derived SKU + promo-code mapping). Aimed at podcast / influencer / affiliate channels.
- **LLM-referrer as named channel:** 1 competitor — Lebesgue (`ChatGPT` exposed as channel in Business Report taxonomy). New 2025-2026 pattern.
- **Channel hierarchy = 4 orthogonal dimensions (Platform × Category × Targeting × Revenue Source):** 1 competitor — Northbeam Breakdowns. Decouples "what platform" from "what funnel role" — uncommon and powerful.

Color/iconography conventions: stacked-bar revenue-by-channel charts default to a categorical palette (one hue per channel); brand-color tinting per platform (Meta blue, Google multicolor, TikTok black) is common but not universal. Lebesgue inverts the dominant green-positive convention by using **blue for positive, red for negative** (potentially deliberate for R/G colorblindness; clashes with brand-color channel hues). No competitor surfaces an "unmapped" / "Other" bucket on the main dashboard — it's hidden in the admin (GA4 surfaces `Unassigned` in the channel list itself, which the help docs say users should drive to zero).

**No competitor in the researched set ships a UTM tag generator inside the channel-mapping admin** — i.e. a UI that builds `?utm_source=…&utm_medium=…&utm_campaign=…` URLs from a fixed dropdown that mirrors the mapping rules. Tag generation is treated as a separate concern (third-party tools like ga-dev-tools / utmbuilder.dev fill this gap), creating a documented sync risk between input UTMs and mapping rules.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Trust replaces platform-self-report**
- "trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time. That's why ThoughtMetric is a must!" — WIDI CARE, Shopify App Store, December 2024 ([thoughtmetric.md](../competitors/thoughtmetric.md))
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra, July 12, 2024 ([triple-whale.md](../competitors/triple-whale.md))
- "great to have a few different sources of truth in this world and ThoughtMetric is becoming a trusted tool to sift through the chaos" — Woolly Clothing Co, Shopify App Store, August 2025 ([thoughtmetric.md](../competitors/thoughtmetric.md))

**Theme: Channel as the primary cohort lens**
- "best-in-class for a Shopify app, allowing you to segment customers by first purchase date, first product purchased, acquisition channel, geography, and more" ([lifetimely.md](../competitors/lifetimely.md), Notes for Nexstage section)
- "With two clicks you can determine the LTV/AOV (& a suite of other metrics) by channels" — Peel Insights marketing copy, cited in [peel-insights.md](../competitors/peel-insights.md)
- "Conjura provides us with a joined-up view of all customer and marketing data. This removes the data silos." — Andy B., Capterra, January 2019 ([conjura.md](../competitors/conjura.md))

**Theme: Side-by-side disagreement is the value**
- "Our team relies on Cometly to track and attribute various KPIs, including revenue, to the correct marketing sources... view our paid media spend in a single, comprehensive view" — Rexell Espinosa, Design Pickle ([cometly.md](../competitors/cometly.md))
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024 ([conjura.md](../competitors/conjura.md))

**Theme: Customer-support-led mapping setup (channel rules as a service)**
- "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used. The customer support is unparalleled and they can actually get me answers to questions I've been trying to get at for months." — Béis ([daasity.md](../competitors/daasity.md))
- "Excellent customer support, especially during setup. Jim was very helpful with creating the reports we need." — Relish (UK), Shopify App Store, August 2025 ([conjura.md](../competitors/conjura.md))

## What users hate about this feature

**Theme: Filter-combination semantics are non-obvious (OR vs AND traps)**
- "Views combine with 'OR' logic, not 'AND.'" — Polar Analytics help docs, paraphrased ([polar-analytics.md](../competitors/polar-analytics.md), Views section). Documented gotcha that makes saved filter-sets produce wrong totals when users layer multiple Views.
- "Switching between views and reports can be slow sometimes" — bloggle.app review, 2024 ([polar-analytics.md](../competitors/polar-analytics.md))

**Theme: Channel taxonomy doesn't match merchant reality**
- "Could not break down or analyze performance by country within the same account." — Tomás Manuel J., Performance Manager (Apparel), Capterra, February 4, 2026 ([lebesgue.md](../competitors/lebesgue.md))
- "Too much centered on shopify. I would like to see the same features for other platforms such as Wordpress." — Antonios P., Owner (Marketing/Advertising), Capterra, January 29, 2025 ([lebesgue.md](../competitors/lebesgue.md))
- "still waiting on Amazon integration" — Evan J., Creative Director, Capterra, October 2021 ([thoughtmetric.md](../competitors/thoughtmetric.md))
- "Missing Amazon implementation" — Pollyana D., Capterra, October 2021 ([thoughtmetric.md](../competitors/thoughtmetric.md))

**Theme: Default channel grouping silently wrong (especially GA4)**
- "the new GA4 just HORRIBLE? It's like it's designed only for retail sites." — Trevor Long (@trevorlong), Twitter, June 23, 2021 ([ga4.md](../competitors/ga4.md))
- "the attribution model was dismal compared to Google Analytics 4 (GA4), and... failed to deliver the necessary depth and accuracy" — Trustpilot reviewer aggregated in [northbeam.md](../competitors/northbeam.md)
- "70% of Shopify brands misread their GA4 attribution" — Medium article cited in [ga4.md](../competitors/ga4.md). Default-channel-group mismatch with Shopify-side reality is the universal SMB pain.
- "Some of our more unique data sources didn't have a pre-built Conjura data connector. Custom-built connectors took a little longer." — Andy B., Capterra, January 2019 ([conjura.md](../competitors/conjura.md))

**Theme: Discrepancy without resolution**
- "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" — hugbel, Shopify App Store, March 2026 (1-star review, [thoughtmetric.md](../competitors/thoughtmetric.md))
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson (Brightleaf Organics), workflowautomation.net, March 16, 2026 ([triple-whale.md](../competitors/triple-whale.md))
- "Need side-by-side platform vs. actual data comparison" — Bill C., Founder (Sports), Capterra, July 2022 ([thoughtmetric.md](../competitors/thoughtmetric.md))

**Theme: Setup complexity / mapping requires expert**
- "requires a deep understanding of GTM and data layers" — Aimerce.ai, Top 5 Elevar Alternatives, 2026 ([elevar.md](../competitors/elevar.md))
- "Implementation involves mapping data sources, configuring channel groupings, and integrating tracking across platforms. Ongoing use demands someone who can interpret multi-touch attribution outputs." — SegmentStream alternatives article (2026), characterizing the analyst-dependency complaint cited in [northbeam.md](../competitors/northbeam.md) competitor cluster.
- "Tracking codes aren't intuitive to locate initially" — Jen W., Director of Marketing (Arts & Crafts), Capterra, December 2022 ([thoughtmetric.md](../competitors/thoughtmetric.md))

## Anti-patterns observed

- **Saved-filter Views combining with OR instead of AND (Polar Analytics).** Multiple Views unioning their results rather than intersecting silently produces over-counted totals; their own help docs warn "put all filters into a single View if you need AND semantics." Users without doc-discipline see inflated channel revenue.
- **Channel = data-integration section (Triple Whale Summary).** Implicit mapping where every connected platform spawns a section means a merchant with three Meta accounts (parent, brand A, brand B) gets three Meta sections rather than one rolled-up "Paid Social" channel — pushes the rollup work back onto the user via Pinned tiles.
- **Attribution Feed as primitive UTM table without translation (Elevar beta).** First-touch + last-touch UTM tuples are exposed as raw rows with no channel-name column. Their own docs disclaim it as "not a replacement for GA4." Acknowledged anti-pattern; surfaced because plumbing-tool positioning forecloses doing the translation properly.
- **Defaulting "Direct" to absorb missing/unknown UTMs (GA4, most tools).** When `utm_source` is empty AND referrer is empty, every tool collapses to Direct — but many of these are actually pixel-stripped paid traffic. Inflates Direct, deflates Paid Social. GA4 partially mitigates with `(other)` and `Cross-network`, but the SMB merchant rarely notices.
- **No "Unmapped" or "Other" surface on the main dashboard.** GA4 alone surfaces `Unassigned` in the channel list; every other competitor's UI hides the unmapped bucket inside admin (or doesn't expose it at all). The hidden bucket is where bad UTM hygiene rots invisibly.
- **Hardcoded UTM dropdowns out of sync with mapping rules.** No competitor ships an integrated UTM tag generator coupled to the mapping engine — implying every competitor's mapping rules drift from what merchants actually paste into Meta's URL builder over time. Validated by ThoughtMetric reviewer "Tracking codes aren't intuitive to locate initially."
- **Override-by-shadow editing (Northbeam Breakdowns).** "Editing a default breakdown" is done by creating a new breakdown with the same name. Functional but cognitively non-obvious — users can end up with two visible "Platform" breakdowns and not know which one is active.
- **Eight attribution models exposed simultaneously without an "explain why they differ" surface (Daasity).** Power-user UX that produces eight different channel-revenue numbers per row; without pedagogy, SMB merchants won't know which to trust.

## Open questions / data gaps

- **No competitor's rule-editor UI was directly observable** beyond GA4 (which is publicly documented with screenshots). Northbeam Breakdowns, Conjura channel-customization, ThoughtMetric "UTM-based custom channels," Triple Whale's mapping internals, Cometly's custom event configuration, and Lebesgue's channel taxonomy editor are all described in marketing/help-doc prose without screenshots — all sit behind paywalls. Hands-on trial signups would be needed to capture pixel-level UI.
- **Channel-mapping rule precedence semantics** (first-match-wins vs explicit priority list vs cascading override) are not consistently documented across competitors. GA4 is first-match-wins by row order; Northbeam appears to be shadow-override; Polar's Views are OR-union; the rest are unspecified.
- **UTM tag generators are absent from every competitor profile reviewed.** TagGenerator.tsx as a feature on the Nexstage side has no direct competitive reference — the closest thing is Google's third-party Campaign URL Builder. This may be a real whitespace or a "no one builds it because no one needs it" tell; couldn't disambiguate from public sources.
- **Discount-code → channel mapping admin UI** is described in Daasity and Conjura docs but not screenshotted. Whether it's a free-form table, a dropdown of pre-named channels, or a CSV import is unknown.
- **Survey-answer → channel mapping admin** is documented in Daasity (Survey-Based Channel as a queryable dimension) but the mapping config UI was not visible. ThoughtMetric and Triple Whale similarly opaque.
- **LLM-referrer handling** — Lebesgue surfaces `ChatGPT` as a channel; whether competitors silently bucket `chat.openai.com`, `chatgpt.com`, `perplexity.ai`, `claude.ai`, `gemini.google.com` referrers into Direct, Referral, or a dedicated bucket varies and is mostly undocumented.
- **GSC integration shape** — only GA4 + Looker Studio + Triple Whale + Conjura list GSC; how GSC's "Organic Search" rolls up against the mapping engine's broader "Organic Search" channel (which also includes referrer-based detection) is not spelled out anywhere reviewed.
- **Backfill / retroactive mapping behaviour** — when a merchant edits a channel rule, do prior orders get re-classified? GA4 says no on the property level (forward-only) unless reprocess is initiated; other competitors are silent. Critical because Nexstage's `RecomputeAttributionJob` semantics depend on the choice.

## Notes for Nexstage (observations only — NOT recommendations)

- **Default-channel taxonomy convergence:** Eight of the thirteen profiled competitors (GA4, Daasity, Northbeam, Polar, ThoughtMetric, Lifetimely, Conjura, Lebesgue) ship roughly the same ~10–14 channel default taxonomy (Direct / Organic Search / Paid Search / Paid Social / Organic Social / Email / SMS / Affiliate / Referral / Display / Video / Other). GA4's 24-channel `Default channel group` is the implicit shared dictionary the rest mirror. `ChannelMappingsSeeder.php` defaults will look familiar to merchants regardless of which list it picks; the Lebesgue addition of `ChatGPT` as a top-level channel is the only 2025-2026 novelty.
- **Northbeam's four-orthogonal-breakdowns model (Platform × Category × Targeting × Revenue Source) is structurally different from a flat channel column** — every other competitor collapses these into one `channel` string. If Nexstage ever needs "Branded Search vs Non-Branded" or "Prospecting vs Retargeting" as a first-class lens, Northbeam Breakdowns is the precedent worth deep-diving.
- **Polar's saved-filter "View" pattern is the closest analog to letting users author their own channel grouping without a dedicated rule editor** — but the OR-union gotcha is a real footgun. Any Nexstage saved-segment system should make AND-vs-OR explicit in the UI.
- **Dual-column attribution (Conjura, Triple Whale, Lifetimely) maps directly onto the Nexstage 6-source-badge thesis at the channel level.** Three competitors validate the "show the disagreement, don't pick a winner" posture for channel-level revenue. None go to 6 sources; the dual-column pattern is the maximum observed in production.
- **Survey-fed and discount-code-fed channels are first-class in Daasity and Conjura but absent from Nexstage's source thesis.** If `MetricSourceResolver` ever adds a "Survey" source, Daasity's Survey Response / Survey-Based Channel / Survey-Based Vendor as three separate queryable dimensions is a clean schema reference.
- **No competitor ships a UTM tag generator wired to the same source/medium dropdowns as the mapping rules.** TagGenerator.tsx + ChannelMappingsSeeder.php sync — flagged in CLAUDE.md as a maintenance concern — is uncharted territory; the closest public reference is Google's external Campaign URL Builder.
- **The "Other / Unmapped" bucket is a universal blind spot.** Only GA4 surfaces `Unassigned` on the main report; every other tool hides it. A surfaced "% of revenue unmapped" badge in Nexstage's mapping admin would be a transparency wedge.
- **Backfill/retroactive recomputation semantics are not publicly documented for any competitor except GA4 (forward-only).** Nexstage's `RecomputeAttributionJob` retroactive recompute behaviour is differentiated relative to what merchants experience elsewhere — they will likely be surprised by it (positively or negatively), so the "Recomputing…" UI banner per CLAUDE.md is doing real work.
- **UI-detail blocker:** Most rule-editor screens sit behind paywalls (Triple Whale, Northbeam, Conjura, Daasity, Cometly, Polar). A free-tier signup on Triple Whale (Founders Dash) and a 14-day trial on ThoughtMetric or Conjura would close the screenshot gap if pixel-level UI references are needed downstream.
- **Naming convention varies wildly:** Northbeam calls it "Breakdowns," Polar calls it "Views," Daasity calls it "Custom Attribution / Dynamic Attribution Method," GA4 calls it "Channel groups," Triple Whale calls it "Sections" (per data integration), Elevar calls the rendered output "Attribution Feed." Nexstage's "channel-mapping" feature label is closest to GA4's framing — the cleanest mental model for users coming from GA4.
- **Pricing/gating signal:** Channel mapping is included in every paid tier of every competitor reviewed — no one paywalls it. It is treated as table-stakes admin, not a premium feature.
