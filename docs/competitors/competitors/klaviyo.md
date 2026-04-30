---
name: Klaviyo
url: https://klaviyo.com
tier: T2
positioning: B2C CRM and email/SMS platform with an embedded analytics layer for ecommerce brands; replaces Mailchimp/Omnisend on the messaging side and overlaps with retention analytics tools (Lifetimely, Repeat) on the data side.
target_market: SMB to mid-market Shopify, WooCommerce, BigCommerce, Magento, Salesforce Commerce Cloud and Wix merchants; global, English-first.
pricing: Free up to 250 profiles; paid Email plan from $20/mo (251-500 profiles). "Marketing Analytics" add-on starts at $100/mo for advanced analytics (CLV/RFM/Benchmarks tier).
integrations: Shopify, WooCommerce, BigCommerce, Magento, Wix, Salesforce Commerce Cloud, plus 350+ marketplace integrations; Meta and Google Ads supported as audience-sync destinations rather than performance ingestion sources.
data_freshness: real-time for events; CLV model retrains "at least once a week"; benchmarks "Performance Highlights" updates monthly on the 10th.
mobile_app: yes (iOS/Android — primarily for campaign monitoring and notifications)
researched_on: 2026-04-28
sources:
  - https://klaviyo.com
  - https://www.klaviyo.com/pricing
  - https://www.klaviyo.com/features/reporting
  - https://www.klaviyo.com/solutions/analytics
  - https://help.klaviyo.com/hc/en-us/articles/4708299478427
  - https://help.klaviyo.com/hc/en-us/articles/9974064152347
  - https://help.klaviyo.com/hc/en-us/articles/17797889315355
  - https://help.klaviyo.com/hc/en-us/articles/17797937793179
  - https://help.klaviyo.com/hc/en-us/articles/17797865070235
  - https://help.klaviyo.com/hc/en-us/articles/26685770823451
  - https://help.klaviyo.com/hc/en-us/articles/360050110072
  - https://help.klaviyo.com/hc/en-us/articles/360020919731
  - https://klaviyo.tech/the-research-behind-our-new-rfm-feature-4c38be17b184
  - https://www.retainful.com/blog/klaviyo-pricing
  - https://www.capterra.com/p/156699/Klaviyo/reviews/
  - https://apps.shopify.com/klaviyo-email-marketing/reviews
  - https://community.shopify.com/t/anyone-else-getting-screwed-by-klaviyos-pricing/561275
---

## Positioning

Klaviyo sells itself as a B2C CRM, but the analytics layer has become a meaningful surface in its own right — the marketing site frames it as a "Customer Analytics Platform to Unify Your Reporting." For ecommerce SMBs, Klaviyo is the data store that already ingests every order, every checkout, every email open and SMS click; the analytics product (Marketing Analytics + Advanced KDP) tries to monetize that data with predictive CLV, six-bucket RFM, peer-group benchmarks, and product-level repeat-purchase analysis. Direct competitive overlap with Nexstage is partial: Klaviyo's strength is owned-channel + first-party customer data; it does not ingest paid Meta/Google ad spend, GSC, or GA4 sessions as performance sources.

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0 | Up to 250 active profiles, 500 sends/mo, 150 SMS credits, basic reporting, RFM and overview dashboards | Hits 250-profile cap |
| Email (paid) | $20/mo (251-500 profiles), $30 (501-1k), $45 (1k-1.5k), $150 (5k-10k), $400 (25k), up to ~$2,300 (200k) | All core features — automation, segmentation, AI tools — at every tier; price scales with profile count only | Profile growth, not feature gating |
| Email + SMS | $35/mo entry | Adds SMS credits | Need MMS / heavier SMS volume |
| Marketing Analytics add-on | from $100/mo (≤13.5k profiles) | Predictive CLV dashboard, RFM analysis report, custom CLV window, advanced cohort/funnel reports, benchmarks | Wants RFM grid + predictive CLV |
| Advanced KDP | from $500/mo (100k+ profiles) | Full Klaviyo Data Platform — replaces Marketing Analytics; cannot run both simultaneously | Mid-market data needs |

Per Retainful's 2026 breakdown: "The feature set does not change as you pay more" across the base Email plans — pricing scales on contact count, while analytics depth is a separate add-on entirely.

## Integrations

**Sources (ingested):**
- Ecommerce platforms: Shopify, WooCommerce, BigCommerce, Magento, Wix, Salesforce Commerce Cloud (orders, line items, customers, products, refunds, checkout events, browse events)
- 350+ marketplace integrations (reviews — Yotpo/Okendo, loyalty — Smile/LoyaltyLion, helpdesk — Gorgias/Zendesk, subscriptions — Recharge/Skio, etc.)
- Custom events via Track API, SFTP, CSV, data warehouse sync (Klaviyo CDP)

**Destinations (push):**
- Meta Custom Audiences, Google Customer Match, TikTok Custom Audiences (audience sync only — Klaviyo pushes lists out, does not pull spend/impressions back)

**Coverage gaps relative to Nexstage's 6-source thesis:**
- No Meta Ads spend / impressions / ROAS ingestion as a performance source
- No Google Ads spend ingestion
- No Google Search Console
- No GA4 ingestion (Klaviyo computes its own attribution rather than reading GA4)

## Product surfaces (their app's information architecture)

- **Home dashboard** — "What did my marketing do today?" Conversion summary, top flows, recent campaigns, alerts.
- **Analytics > Overview dashboards** — Customizable multi-card dashboards with Conversion Summary, Campaign Performance, Flow Performance, Performance Highlights, Email Deliverability cards.
- **Analytics > Custom reports** — Build-your-own pivot-style reports across campaigns, flows, segments.
- **Analytics > Benchmarks > Overview** — Top-5 / bottom-5 metrics ranked by percentile vs ~100 peer companies.
- **Analytics > Benchmarks > Business performance** — KPIs framed as "business health" benchmarked against peers.
- **Analytics > Benchmarks > Email campaigns** — Open/click/conversion/deliverability percentile ranks.
- **Analytics > Benchmarks > Flows** — Per-flow benchmark percentiles.
- **Analytics > Benchmarks > Sign-up forms** — Form submission rate vs peer percentile.
- **Marketing Analytics > Customer insights > CLV dashboard** — Predictive + historic CLV, segments-using-CLV, campaigns/flows/forms-using-CLV.
- **Marketing Analytics > Customer insights > RFM analysis** — Six-bucket distribution, Sankey of group movement, median performance per group.
- **Marketing Analytics > Catalog Insights > Product Analysis** — Repeat purchase timing, "bought in same cart," "bought in next order."
- **Marketing Analytics > Funnel analysis** — Multi-step conversion funnels.
- **Marketing Analytics > Cohort analysis** — Retention/repurchase cohorts.
- **Profile page > Metrics and insights tab** — Per-customer predictive panel: predicted CLV, expected next order date, churn risk, predicted gender, average time between orders.
- **Flow Builder canvas (analytics overlay)** — Click any message node → 30-day analytics sidebar (opens/clicks/revenue per node).
- **Campaigns > Trends** — Multi-campaign trend graphs over time (clicks, opens, recipients).
- **Deliverability hub** — Sender-reputation monitoring across email and SMS by inbox provider, domain, country.
- **SMS Reporting Dashboard** — SMS-specific subscriber growth, deliverability, conversion.
- **Reviews > Sentiment analysis** — AI tagging of review sentiment.
- **Customer Hub** — Customer-facing portal where shoppers manage preferences (analytics surface for CSAT signals).

## Data they expose

### Source: Shopify / Woo / BigCommerce
- **Pulled:** orders, line items, customers, products, refunds, checkout-started events, browse events, viewed-product events.
- **Computed:** AOV, repeat purchase rate, predictive CLV, historic CLV, average days between orders, predicted next order date, churn risk score, RFM scores (1-3 each on R/F/M, combined into 3-digit composite), six RFM groups.
- **Attribution windows:** Customizable conversion window per metric on Email/SMS plans; Marketing Analytics add-on unlocks "flexible attribution settings that apply retroactively."

### Source: Klaviyo's own messaging (email/SMS/push)
- **Pulled:** all sends, deliveries, opens, clicks, bounces, unsubscribes, spam complaints, conversions tied to messages.
- **Computed:** open rate, click rate, conversion rate, revenue per recipient, attributed revenue, deliverability score, peer-percentile rank for each.

### Source: Meta / Google / TikTok Ads
- **Not ingested as performance data.** Klaviyo pushes audiences out for retargeting/lookalikes but does not import spend, impressions, CPM, ROAS, CTR — so blended ROAS or MER is not a Klaviyo concept.

### Source: GA4
- **Not ingested.** Klaviyo computes its own attribution from on-site events fired via the Klaviyo JS / server-side API.

### Source: GSC
- Not observed.

## Key UI patterns observed

### Home dashboard
- **Path/location:** Sidebar > Home (default landing).
- **Layout (prose):** Top: alerts strip + conversion-metric selector + time-period selector (up to 180 days). Main canvas (vertical scroll): "Business Performance Summary" card showing total revenue with an inline channel breakdown (email/SMS/push) and a flows-vs-campaigns split. Below: "Top-Performing Flows" — up to six flows ranked descending by conversion or revenue with status pill (Live / Manual), message-type icon, delivery count, conversion count, and percent-change vs prior period. Below that: "Recent Campaigns" list, most recent first, with name, message type, open rate, click rate, and conversion data per row.
- **UI elements:** Conversion-metric selector that re-pivots all cards globally; status pills (Live / Manual / Draft); percent-delta cells (no documented color coding observed publicly).
- **Interactions:** Selecting a different conversion metric recalculates all cards. Clicking a flow name opens flow detail.
- **Metrics shown:** Total revenue, attributed revenue, conversions, opens, clicks, sends, percent change vs prior period.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/9974064152347

### Analytics > Overview dashboard
- **Path/location:** Analytics > Dashboards > Overview Dashboard (or any custom dashboard built from the same card library).
- **Layout (prose):** Top filter strip: date range, conversion metric, comparison period. Below: a stack of cards the user composes from a library. Default load includes Conversion Summary (stacked bar split flows vs campaigns with percent attribution), Campaign Performance (multi-line chart — open rate in blue, click rate in teal, conversion metric in yellow), Campaign Performance Detail (table of delivery rate, unique opens, clicks, orders, revenue, revenue-per-recipient per campaign), Flows Performance (analogous to campaigns), Flow Performance Detail (alphabetized table), Performance Highlights, Email Deliverability (line chart of bounce/spam/unsubscribe rates).
- **UI elements:** Per-card channel tabs (Email / SMS / Mobile push); peer-benchmark badges directly on Campaign Performance card rated **"Excellent," "Fair," or "Poor"**; line-chart with three colored lines (blue / teal / yellow) per the help docs.
- **Interactions:** Date-range adjustment recalculates entire dashboard. Cards are composable — users can add/remove from a card library that includes forms performance, SMS deliverability, subscriber growth, email deliverability by domain.
- **Metrics shown:** Open rate, click rate, conversion rate, attributed revenue, revenue-per-recipient, delivery rate, bounce rate, spam rate, unsubscribe rate, peer percentile.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/4708299478427

### Marketing Analytics > RFM analysis
- **Path/location:** Advanced KDP > Intelligence > Customer insights > RFM analysis (or Marketing Analytics > Customer insights > RFM analysis).
- **Layout (prose):** Top: calendar pickers for start and end of report range. Main canvas is three stacked cards. **Compare Distribution of Customers** card has three tabs — *Customers* tab is a bar chart of group sizes at start vs end of period (hover reveals exact profile counts and percentage); *Added or Dropped* tab is per-group bar chart with **teal segments for added profiles, red segments for dropped profiles** over the period; *Percentage Change* tab is a static table with group totals, percentages, and change deltas. **Group Change Over Time** card is a **Sankey diagram** with start-date groups on the left and end-date groups on the right, ribbons connecting them representing migrations (hover reveals migration counts). **Median Performance** card has tabs to switch between start-date and end-date snapshots, displaying a static table with median Days Since Purchase, median Purchase Order Number, and median Placed Order Revenue per group.
- **UI elements:** Six fixed cohort labels — **Champions, Loyal, Recent, Needs Attention, At Risk, Inactive**. Each profile gets a 1-3 score on R, F, and M (e.g., 333 is a Champion; 111 is Inactive). Status terms "Excellent / Fair / Poor" badges appear on the broader Campaign Performance card but RFM uses cohort labels rather than badges.
- **Interactions:** Date pickers recalculate all cards. Hover Sankey ribbons for migration counts. Click into Customer Insights to drill into segments built from "Current RFM group" or "Previous RFM group" properties.
- **Metrics shown:** Group size (count + %), profiles added/dropped per group, group-to-group migration count, median Days Since Purchase, median Purchase Order Number, median Placed Order Revenue.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/17797889315355 ; https://help.klaviyo.com/hc/en-us/articles/17797937793179
- **Note:** Klaviyo's own Engineering blog (Nick Hartmann) confirms "six mutually exclusive cohorts" was a deliberate research-driven choice based on industry-standard practice plus user research conversations.

### Marketing Analytics > CLV dashboard
- **Path/location:** Marketing Analytics > Customer insights > CLV (or Advanced KDP > Intelligence > Customer insights > CLV).
- **Layout (prose):** Five vertically stacked cards. **Current Model** card pinned at top — shows historic date range, predicted date range, last model retrain date, and **five example customer profiles** demonstrating CLV calculation. **Segments Using CLV** card — table of segments leveraging predictive CLV attributes with profile count, CLV attribute used, last update date. **Upcoming Campaigns Using CLV** — table of scheduled campaigns by CLV attribute with status pill (Scheduled/Sending) and channel icon. **Flows Using CLV** — analogous table with status (Live / Manual / Draft). **Forms Using CLV** — connected forms with form type (Popup / Flyout / Embed / Full Page).
- **Predicted CLV visualization on profile pages:** A horizontal stacked bar where the **blue segment is historic CLV (already spent)** and the **green segment is predicted CLV (next 365 days)**; the full bar represents Total CLV. On the profile's order timeline, **diamond-shaped tick marks** mark the predicted next-order date.
- **UI elements:** Status pills (Live / Manual / Draft / Scheduled / Sending); colored stacked bar (blue/green); diamond glyph for predicted next order on timeline; CLV-window selector (Marketing Analytics tier lets you customize prediction window beyond default 365 days).
- **Interactions:** Click any segment row to view profiles. Click campaigns/flows/forms to navigate into builders. Customize prediction window in Marketing Analytics tier.
- **Metrics shown:** Predicted CLV, Historic CLV, Total CLV, Predicted Number of Orders, Historic Number of Orders, Average Order Value, Average Days Between Orders, expected date of next order, churn risk score, predicted gender.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/17797865070235 ; https://help.klaviyo.com/hc/en-us/articles/360020919731

### Benchmarks > Overview
- **Path/location:** Analytics > Benchmarks (default landing on Overview tab).
- **Layout (prose):** Two side-by-side ranked tables. **Top Performing Metrics** lists the user's strongest five metrics ordered by descending percentile vs peer group; **Bottom Performing Metrics** lists the weakest five ordered by ascending percentile. Each row shows the metric name, the user's raw value, and the user's percentile.
- **UI elements:** Drill-down view per metric reveals **25th percentile / 50th percentile (median) / 75th percentile** values from peer cohort. Peer group is "roughly one hundred companies that are similar to your own in size and scope (e.g., industry, average item value, total revenue, year over year growth rate)."
- **Interactions:** Click a metric row to expand percentile distribution. Industry can be edited in Organization settings to change peer group composition.
- **Metrics shown:** All major email/SMS/flow/form/business KPIs with user value + user percentile + 25th/50th/75th peer percentiles.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/360050110072

### Profile page > Metrics and insights tab (per-customer predictive panel)
- **Path/location:** Profiles > [single profile] > Metrics and insights.
- **Layout (prose):** Card grid of predictive metrics for that single customer. Each metric in its own card.
- **UI elements:** Churn-risk uses **traffic-light color coding — green for low risk, yellow for medium, red for high**. Order timeline at the bottom of the profile has tick marks for past orders and a **diamond tick** at the predicted next order date.
- **Interactions:** Hover metrics for definitions. Add this customer to a segment from inline action.
- **Metrics shown:** Predicted CLV, expected date of next order, churn risk (low/medium/high), average time between orders, predicted gender (likely male / likely female / uncertain).
- **Activation requirements:** "at least 500 customers have placed an order," 180+ days of order history, orders in the last 30 days, three or more repeat purchasers — predictive features hide until thresholds are met.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/360020919731

### Flow Builder (per-message embedded analytics)
- **Path/location:** Flows > [select flow] > Edit (canvas view).
- **Layout (prose):** Canvas shows the flow as a vertical/branching node tree (trigger → time delays → message nodes → conditional splits). **Click any message card and a left-hand sidebar slides in showing 30-day analytics for that specific node** — opens, clicks, revenue per recipient. A "View all Analytics" link at the bottom of the sidebar deep-links to the Overview tab for that flow message.
- **UI elements:** In-canvas analytics sidebar; per-node metric snapshot; deep-link affordance to full report.
- **Interactions:** Click message node to load analytics; toggle between message versions in A/B-split paths to compare side-by-side.
- **Metrics shown:** Opens, open rate, clicks, click rate, attributed revenue, revenue per recipient, conversion count — all 30-day default window.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/115002779351

### Catalog Insights > Product Analysis
- **Path/location:** Marketing Analytics > Catalog Insights (or Advanced KDP > Intelligence > Catalog Insights).
- **Layout (prose):** Left rail: sortable product list (Revenue High-Low / Revenue Low-High / Customers High-Low / Customers Low-High). Main canvas: customizable analysis cards. **Repeat Purchase Timing** card is a histogram with x-axis "days between purchases" (capped at 90-day view) and y-axis "customer count" — shows the distribution of when buyers come back. **Products Bought in Same Cart** is a ranked table with co-purchase rate %. **Products Bought in Next Order** is a ranked table with post-purchase rate % and median days between purchases.
- **UI elements:** Sortable product list; histogram chart; co-purchase % cells.
- **Interactions:** Select a product to refresh all cards. Sort/filter product list. Date range defaults to last two years; refunded/cancelled orders excluded by default.
- **Metrics shown:** Repeat purchase timing distribution, co-purchase rate, post-purchase rate, median days between purchases, top 500 product recommendations per category.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/26685770823451

### Performance Highlights card
- **Path/location:** Embedded in Overview dashboard and Home; surfaces top + bottom metrics for the last month.
- **Layout (prose):** Single card. Two columns — top performers / bottom performers — listing metric names. "Updates on the 10th of every month" per Klaviyo's docs. Lists metrics "based on Klaviyo best practices compared to a group of your peers."
- **Interactions:** Click a metric to drill into its benchmark detail page.
- **Source:** https://help.klaviyo.com/hc/en-us/articles/4708299478427

## What users love (verbatim quotes, attributed)

- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group, quoted on Klaviyo.com features page
- "Easy ability to see how much money generated for each email sent, good visibility of who opened emails." — Lee W., Capterra, January 2026
- "It offers strong automation, detailed segmentation, and solid reporting, which makes it very effective." — Linda S., Capterra, April 2026
- "The platform's ability to handle intricate segmentation and real-time triggers has allowed me to deliver measurable growth." — Marc G., Capterra, March 2026
- "Advanced segmentation, in-depth analytics, and automation that's perfect for eCommerce... powerful automation, and in-depth analytics." — Jeannie V., Capterra, October 2024
- "What I like most about Klaviyo is how powerful its segmentation and automation features are, while still being relatively easy to use." — G2 reviewer, surfaced via search results

## What users hate (verbatim quotes, attributed)

- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025
- "Absolute scammers! BE CAREFUL with this guys! I setup in my Klaviyo settings not to be charged if i exceed my limit of $45 plan." — Innovato Design, Shopify App Store, February 2026
- "Scammers! I was charged $390 when suppose to be $45." — HYKLE, Shopify App Store, February 2026
- "Their cost is roughly $1 for 10000 sends and they charge you 20x markup just on the sends." — Fight Against Screens, Shopify App Store, March 2026
- "A nightmare to setup, zero support, stay away!" — tshirtjunkies.co, Shopify App Store, April 2026
- "We have been locked out of our account and cannot access the app...customer service has not responded to requests." — 528 Innovations, Shopify App Store, January 2026
- "Do NOT use them for order confirmation emails. You cannot update any info or resend confirmations to a different address." — Vermont Nut Free Chocolates, Shopify App Store, April 2026

## Unique strengths

- **Six-bucket RFM with deliberately fixed cohort taxonomy** (Champions / Loyal / Recent / Needs Attention / At Risk / Inactive) and a **Sankey diagram showing customer migration between cohorts over a date range** — most ecommerce analytics tools either skip RFM entirely or expose raw 3x3x3 = 27 cell scores; Klaviyo's editorial decision to ship six fixed groups (research-justified on their engineering blog) is rare.
- **Predictive CLV visualized as a two-color stacked bar (historic blue + predicted green)** at the per-profile level, with a **diamond marker on the order timeline for next predicted order date** — concrete, scannable, customer-by-customer prediction surface.
- **Per-message embedded analytics inside the flow canvas** — click any node and a 30-day open/click/revenue snapshot loads in-context, so users never leave the builder to evaluate node performance.
- **Peer-percentile benchmarking with explicit 25/50/75th percentile drill-downs** against ~100 similar companies (industry, AOV, revenue, YoY growth) — most retention-analytics tools quote vague "industry averages"; Klaviyo names the cohort math.
- **Deeply integrated with the data they already own** — every email send, click, browse, and order is in one event store, so attribution is consistent across all dashboards (no source-reconciliation problem like Nexstage's 6-source thesis solves).

## Unique weaknesses / common complaints

- **Analytics features are paywalled behind Marketing Analytics ($100/mo) or Advanced KDP ($500/mo)**, on top of the base Email plan — multiple Capterra reviewers flag this as features that "should be baked into the core product."
- **Profile-based pricing climbs aggressively** — community thread "Anyone else getting screwed by Klaviyo's pricing? 😤" on Shopify community is representative; multiple users on the Shopify App Store report sudden charge jumps after billing-model changes.
- **No paid-ad performance ingestion** — Klaviyo cannot show blended ROAS, MER, or paid-channel cost; analytics is owned-channel only.
- **Reporting UI criticized as "clunky"** with key metrics buried (Capterra, April 2026); learning curve called out repeatedly.
- **Predictive analytics gated by data thresholds** (500+ customers with orders, 180+ days history, 3+ repeat purchasers) — small/new stores see empty CLV / churn cards.
- **Customer support quality** is a recurring 1-star theme on the Shopify App Store — bot-only chat, slow email replies, "ticket-closing" rather than problem-solving.

## Notes for Nexstage

- **Klaviyo's six-bucket RFM names are a category convention** — Champions / Loyal / Recent / Needs Attention / At Risk / Inactive. If Nexstage ships RFM, deviating from these names will create a learning-curve tax for users coming from Klaviyo. The status-badge pattern (Excellent / Fair / Poor) lives on Klaviyo's *campaign benchmarks*, not RFM cells — worth noting before assuming Klaviyo's RFM grid uses badges.
- **The Sankey "Group Change Over Time" diagram is the visual that gets quoted in user reviews** — it's the storytelling layer ("how did my Champions migrate to At Risk?") that flat distribution charts can't deliver. If Nexstage wants RFM to be a differentiator, the migration-flow visual is the bar.
- **CLV stacked bar (historic blue + predicted green)** is a small but potent visual primitive — splits the metric into "what happened" + "what will happen" without needing a chart. Direct analog to Nexstage's "Real" badge thesis: separate observed reality from modeled prediction.
- **Klaviyo treats Meta/Google as audience-push destinations only**, not as performance sources. Nexstage's 6-source ingestion thesis is genuinely complementary — a Klaviyo user has zero blended-ROAS view today.
- **Benchmark percentile distribution (25/50/75) with named peer-cohort criteria** is more transparent than "industry average" claims competitors make. If Nexstage ever ships benchmarks, this is the bar.
- **Predictive CLV gating ("requires 500+ customers, 180+ days history")** is honest but creates an empty-state problem for new stores. Worth thinking about the empty state explicitly when designing predictive surfaces.
- **Per-node analytics sidebar inside the flow builder** is a strong "embedded analytics" pattern that exists nowhere on a marketing-only canvas; Nexstage doesn't have a flow builder, but the principle (analytics scoped to the artifact you're editing) translates to campaign editors and ad creative review.
- **"Marketing Analytics is a separate paid tier" is a packaging signal** — even Klaviyo, which already owns the data, hasn't bundled advanced analytics into the base plan. Suggests advanced retention analytics is a real willingness-to-pay tier, not a baseline expectation.
- **Verified Shopify App Store reviewer count** at the install volume Klaviyo has indicates this is a major incumbent; Nexstage will be compared to Klaviyo by Shopify SMBs even though the products solve different problems.
