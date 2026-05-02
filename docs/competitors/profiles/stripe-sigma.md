---
name: Stripe Sigma
url: https://stripe.com/sigma
tier: T2
positioning: SQL + AI-assistant analytics layer over Stripe payments data, sold as a Dashboard add-on to existing Stripe merchants.
target_market: Stripe-native businesses of any size; finance / data / ops roles primarily. No platform tie to Shopify or Woo. Global (priced in USD and EUR).
pricing: Tiered subscription by monthly authorized charge volume. Starts at ~$10–€13/mo (Starter, up to 250 charges) and scales to ~$395+/mo (Enterprise, 25,000 charges) with per-charge overage fees.
integrations: Stripe-only data sources — Charges, Customers, Invoices, Subscriptions, Disputes, Balance Transactions, Payouts, Connect, Checkout, Issuing, Tax, Treasury, Terminal, Transfers. No external joins. Optional pipe to Snowflake/Redshift via separate Stripe Data Pipeline product.
data_freshness: ~3 hours for API-backed transaction data; 12–120 hours for derived/analytics tables; scheduled query results delivered after 14:00 UTC the day after run.
mobile_app: No dedicated app. Accessed inside the Stripe Dashboard web app (responsive).
researched_on: 2026-04-28
sources:
  - https://stripe.com/sigma
  - https://stripe.com/sigma/pricing
  - https://docs.stripe.com/stripe-data/sigma
  - https://docs.stripe.com/stripe-data/how-sigma-works
  - https://docs.stripe.com/stripe-data/write-queries
  - https://docs.stripe.com/stripe-data/available-data
  - https://support.stripe.com/questions/understanding-stripe-sigma-pricing
  - https://chartsy.app/blog/what-is-stripe-sigma-features-pricing-limitations
  - https://www.definite.app/blog/stripe-sigma-time-for-alternative
  - https://hamsterstack.com/how-to/stripe/query-data-sigma/
  - https://news.ycombinator.com/item?id=14463385
  - https://techcrunch.com/2017/06/01/stripe-sigma-data-analytics/
---

## Positioning

Stripe Sigma is an in-Dashboard SQL workbench plus AI assistant that lets Stripe customers query their own Stripe payments data without building an ETL pipeline. The marketing line is: "From founders to finance teams, anyone can effortlessly pull insights from their Stripe data – SQL knowledge not required." It does not compete on the same axis as ecommerce attribution dashboards (Triple Whale, Northbeam, Peel) — it replaces internal data-warehouse + BI work for businesses whose primary data of interest lives in Stripe (subscriptions, payments reconciliation, dispute analysis). For Nexstage's SMB Shopify/Woo target, Sigma is mostly an inspiration source for SQL-editor and AI-query UX, not a head-to-head competitor.

## Pricing & tiers

Sigma's pricing is based on **monthly authorized charge volume**, not number of queries or seats. Per Stripe's pricing page and the Chartsy 2026 breakdown:

| Tier | Monthly Charges | Monthly Cost | Annual Cost | Overage Rate | Common upgrade trigger |
|---|---|---|---|---|---|
| Starter | Up to 250 | €13 | €9/mo equivalent | €0.053/charge | Crossing 250 charges/mo |
| Growth | Up to 2,500 | €53 | €9/mo equivalent | €0.022/charge | Crossing 2,500 charges/mo |
| Professional | Up to 10,000 | €198 | €198/mo | €0.022/charge | Crossing 10,000 charges/mo |
| Enterprise | Up to 25,000 | €395 | €395/mo | €0.018/charge | Crossing 25,000 charges/mo |
| Custom | 25,000+ | Contact sales | Custom | Custom | n/a |

- **Free trial:** "New users get a free 30-day trial." (stripe.com/sigma/pricing)
- **Charge definition (verbatim):** "successful charges both on Stripe and through third-party payments processors in connection with any Stripe service."
- **Auto-renewal (verbatim):** "All of our standard subscription plans automatically renew at the end of your current term for an additional term."
- **Bundling:** Stripe Data Pipeline customers get "complimentary access to Stripe Sigma" (stripe.com/sigma).
- **No per-query metering on the surface,** though some 3rd-party blogs mention a $0.02-per-1,000-rows model — not corroborated on the official pricing page in 2026; treat as outdated.

USD pricing referenced elsewhere lands around $10/mo Starter and $225/mo at the 5,000-charge band, suggesting parity rather than identical rates between currencies.

## Integrations

**Sources:**
- Stripe Charges, Payments, Refunds
- Stripe Customers, Customer Balance Transactions
- Stripe Invoices, Subscriptions, Plans, Pricing (Billing)
- Stripe Disputes
- Stripe Payouts, Transfers, Balance Transactions
- Stripe Connect (connected accounts, application fees)
- Stripe Checkout, Issuing, Tax, Treasury, Terminal
- Custom metadata fields on any of the above

**Destinations:**
- CSV export (full result set, not capped at the 1,000-row UI preview)
- Email delivery for scheduled queries
- Webhook delivery for scheduled queries
- Stripe Dashboard "publish report" (custom report visible inside the Dashboard)
- Sigma API (programmatic access to saved queries)
- Stripe Data Pipeline (separate paid product) for sync to Snowflake / Redshift

**Coverage gaps (deliberate):**
- No Shopify, no WooCommerce, no Meta Ads, no Google Ads, no GA4, no GSC, no Klaviyo, no TikTok — Sigma queries **only** data living inside the merchant's Stripe account. Definite (2026) summarizes this as: "the moment you need to combine payment data with your CRM, marketing platform, or product usage data, Sigma cannot help you."

## Product surfaces (their app's information architecture)

- **Sigma home (`/sigma/queries`)** — entry point listing user's saved queries and team's shared queries.
- **Query editor** — single-page SQL workbench: schema browser, editor, results, chart toggle, AI Assistant pane.
- **Templates / Stripe query templates** — curated list of pre-built SQL queries for common metrics ("the most common metrics and reports").
- **Saved queries / Team queries** — organized in the left navigator; team queries are shared read-only with copy-to-edit.
- **Chat history** — per-query slider showing all prior AI Assistant prompts and responses.
- **Schedules** — daily / weekly / monthly recurrence with email or webhook delivery.
- **Custom Metrics dashboard (`/custom-metrics`)** — separate Dashboard surface where Sigma queries can be promoted into "metric groups" tracked daily; capped at 20 Sigma reports across all metric groups.
- **Sigma settings (`/settings/sigma`)** — subscription management, cancel, tier change.
- **Sigma API** — programmatic interface for triggering queries and retrieving results.
- **Pricing / upgrade page (`/sigma/pricing`)** — tier comparison and overage rates.

That's roughly 8–10 distinct surfaces, dispersed inside the broader Stripe Dashboard rather than living in their own product shell.

## Data they expose

### Source: Stripe (only)
- **Pulled (API-backed, ~3h freshness):** charges, refunds, payment intents, balance transactions, customers, customer balance transactions, invoices, invoice line items, subscriptions, subscription items, plans, prices, products, disputes, dispute evidence, payouts, transfers, application fees, connected accounts, checkout sessions, issuing transactions, tax registrations, treasury transactions, terminal locations, custom metadata on any object.
- **Pulled (derived / analytics tables, 12–120h freshness):** revenue recognition data, dispute analysis tables, payout reconciliation tables, daily balance summaries.
- **Computed (left to the user via SQL):** MRR, ARR, churn rate, LTV, ARPU, AOV, cohort retention, dispute rate, contested-dispute %, payment-method mix, repeat-customer rate, failed-payment recovery, currency-FX-aware totals. None are precomputed metrics — Sigma exposes raw fact tables and the user (or AI Assistant) writes the math.
- **Attribution windows:** N/A. Sigma has no marketing attribution model — it's a payments-only dataset.
- **Variable exposed in queries:** `data_load_time` — timestamp of the most-recent data load, used for date-range parameterization in scheduled queries.

(Sigma exposes no other sources; there is nothing else to enumerate.)

## Key UI patterns observed

### Query editor
- **Path/location:** Dashboard sidebar > Sigma > query opens the editor at `dashboard.stripe.com/sigma/queries`.
- **Layout (prose):** Three-pane layout. **Left navigator panel** lists saved queries, team queries, the table-schema browser for all Stripe data sources, and the "Stripe query templates" library. **Central editor** is a standard ANSI SQL text editor with syntax highlighting and line numbers (the marketing page screenshot shows colored tokens for keywords/strings/columns, and a modal that pops up showing example queries). **Results section** sits below the editor as a tabular view rendering up to 1,000 rows on screen (CSV export returns the full set).
- **UI elements (concrete):** "Run" button executes the query; an "Export" button downloads CSV; a "Chart" toggle switches the result panel to a line/bar visualization with custom axes and grouping (only enabled when the result set is under 10,000 rows). Errors render inline with line + column position. Column headers are click-to-sort; columns are drag-to-resize. Chat-history slider button sits in the top-right of the editor.
- **Interactions:** Inline error messages reference exact `(line, position)` coordinates. Saved-query state persists per user; sharing produces a unique URL; recipients of a shared query get a read-only view and must "make a copy" to modify.
- **Metrics shown:** Whatever the user's SQL returns — Sigma is a workbench, not a dashboard.
- **Source/screenshot:** Marketing imagery on https://stripe.com/sigma (no PNG saved, per task constraints).

### AI Assistant ("Sigma Assistant")
- **Path/location:** Embedded in the query editor as a prompt field, with two operating modes.
- **Layout (prose):** Prompt input near the editor; users select a mode then type in plain English. The assistant "loads the query suggestion into the editor and displays a summary describing the suggestion." A chat-history slider in the editor's top-right replays every prompt + response for that query, including the SQL the assistant produced for each turn.
- **UI elements (concrete):** Two modes — **Generate** (overwrites editor content with a new query produced from the prompt) and **Edit** (rewrites the current SQL based on the prompt). A helpfulness feedback control is attached to each response. The assistant outputs "standard ANSI SQL from a natural language prompt" (docs.stripe.com/stripe-data/write-queries) and is constrained to the Stripe schema and the English language.
- **Interactions:** Plain-English prompts like "What percentage of disputes did we contest?" or "How much revenue comes from different customer channels?" → SQL generated, then the user clicks Run. Per Definite (2026): "The AI generates SQL, and you're responsible for validating it. When the results look off (and they will, eventually) you need to debug the SQL yourself." No semantic layer locks down metric definitions.
- **Metrics shown:** SQL output only — no metric cards, no ratio computations beyond what the SQL says.
- **Source/screenshot:** https://docs.stripe.com/stripe-data/write-queries (no PNG saved).

### Schema browser
- **Path/location:** Left rail of the editor.
- **Layout (prose):** Hierarchical list grouped by Stripe product domain (Billing, Payments, Customers, Connect, Checkout, Issuing, Tax, Treasury, Terminal, Transfers). Each group expands into individual tables; each table expands into columns with type information.
- **UI elements (concrete):** "Quick sidebar access to a full map of the structure of your data stored in Stripe" (stripe.com/sigma).
- **Interactions:** Click table to insert into editor (per common SQL-IDE pattern; not explicitly confirmed in docs); browse columns to discover available fields.
- **Metrics shown:** Schema metadata only.
- **Source/screenshot:** stripe.com/sigma marketing imagery.

### Templates library
- **Path/location:** Inside the left navigator under "Stripe query templates."
- **Layout (prose):** Curated list. Stripe describes templates as covering "the most common metrics and reports." The marketing page surfaces template examples for: dispute analysis and evidence tracking, monthly charge volume / cash flow, unpaid-invoice identification, bank-payout reconciliation, daily balance calculation, active customer count, subscription-plan popularity, payment-method distribution.
- **UI elements (concrete):** Template selection inserts SQL into the editor — users "make a copy of the template and edit the report date intervals" (docs.stripe.com/stripe-data/how-sigma-works).
- **Interactions:** One-click copy-to-editor; full edit afterwards.
- **Metrics shown:** Template names; underlying SQL once selected.
- **Source/screenshot:** stripe.com/sigma.

### Custom Metrics / metric groups
- **Path/location:** `dashboard.stripe.com/custom-metrics` (separate from the Sigma editor).
- **Layout (prose):** Dashboard surface where Sigma reports are organized into "metric groups" for daily monitoring. UI details not available — only the feature description seen in docs.
- **UI elements (concrete):** "You can add up to 20 Sigma reports across all metric groups" (hard cap quoted from docs). A "Sigma chart" can be edited to "customize the metric preview."
- **Interactions:** Promote a saved Sigma report into a metric group; daily updates run automatically.
- **Metrics shown:** Whatever the underlying queries compute.
- **Source/screenshot:** docs.stripe.com/stripe-data/how-sigma-works (no public screenshot found).

### Scheduling
- **Path/location:** Per-query menu inside the editor.
- **Layout (prose):** UI details not available — only feature description in docs.
- **UI elements (concrete):** Frequency choices: daily, weekly, monthly. Delivery methods: email or webhook. Per Chartsy (2026): "Sigma scheduled query results are not available until 2pm UTC the day after they run."
- **Interactions:** Set schedule, recipients, format.
- **Source/screenshot:** docs.stripe.com/stripe-data/how-sigma-works.

### Sharing & collaboration
- **Path/location:** Save dialog and shared-link copy on saved queries.
- **Layout (prose):** Save creates a shareable URL with auto-generated title; recipients see a read-only view.
- **UI elements (concrete):** "Save frequently-used queries to run them again at any time or share a link" (stripe.com/sigma). Recipients "can make copies for modifications."
- **Source/screenshot:** stripe.com/sigma.

## What users love (verbatim quotes, attributed)

- "Stripe Sigma has helped accelerate our financial close process. Instead of manually combining multiple data sources each month, we're now able to run a few simple queries in Sigma, enabling faster monthly reconciliation for credit card transactions." — Kelly Hofmann, Revenue Accounting, Slack (testimonial on stripe.com/sigma)
- "Sigma gives us legitimate evidence to challenge a chargeback, whereas before Stripe, we had no visibility whatsoever. The new level of data and insight we can get out of Stripe compared to what we could get previously is just night and day. It really helped us improve and speed up our decision-making." — Jez Bristow, Chief Product Manager, Green Flag (testimonial on stripe.com/sigma)
- "With a query that took less than 5 minutes to write, our team has been able to identify unpaid invoices and recapture tens of thousands of dollars of revenue – 8 percent of failed payments – in just two months." — Steven Moldavskiy, Business Intelligence, Harri (testimonial on stripe.com/sigma)
- "Before Stripe Sigma, we built our own tool to analyse our Stripe data, but it took our engineers weeks to build, required ongoing work to maintain and update and it wasn't always accurate. Sigma now gives all our teams accurate data without any engineering work." — Tracy Rogers, Data Scientist, ClickFunnels (testimonial on stripe.com/sigma)
- "I get excited whenever Stripe releases a new product because their demo pages are bar-none the best ever made." — mmanfrin, Hacker News (June 2017, news.ycombinator.com/item?id=14463385)
- "SQL is quickly turning into the new Excel. Most excel power users at our company have picked up SQL easily." — teej, Hacker News (June 2017, same thread)

(Note: G2/Capterra/Trustpilot do not list Stripe Sigma as a separately reviewable product; SourceForge has 0 reviews. Public sentiment is concentrated on HN, vendor blogs, and Stripe's own testimonials. Limited independent review data available.)

## What users hate (verbatim quotes, attributed)

- "Is the data freshness a joke? 2 DAYS to get data into the data warehouse?" — logvol, Hacker News (June 2017)
- "The pricing seems a bit much considering that Stripe is already making good deal of money off their users." — joshontheweb, Hacker News (June 2017)
- Per-charge pricing "makes it far more expensive for businesses selling cheaper products." — scrollaway, Hacker News (June 2017)
- "It doesn't give access to any NEW data...it's an SQL wrapper on top of their API." — Josh Pigford (then-CEO of Baremetrics), Hacker News (June 2017)
- "The AI generates SQL, and you're responsible for validating it. When the results look off (and they will, eventually) you need to debug the SQL yourself." — Definite blog, Stripe Sigma alternative guide (2026)
- "Sigma requires SQL, a skill that maybe a few people at your company actually have." — Definite blog (2026)
- "Most businesses hit Sigma's limits within 6-12 months. The SQL barrier frustrates non-technical teammates. The data silos block cross-functional analysis. The lack of context makes insights shallow." — Definite blog (2026)
- "Sigma scheduled query results are not available until 2pm UTC the day after they run." — Chartsy blog, "What Is Stripe Sigma? Features, Pricing & Limitations" (2026)
- "Two-day lag seems excessively laggy without a business reason." — koolba, Hacker News (June 2017)
- "How do you prevent resource exhaustion / DoS attacks through overly complicated queries?" — LunaSea, Hacker News (June 2017)

## Unique strengths

- **Zero-setup native data access for Stripe customers.** No ETL, no API key management, no warehouse — Sigma is "an SQL wrapper on top of their API" that already authenticates to your account. For finance teams whose only data lives in Stripe, this collapses the time-to-first-query from weeks to minutes.
- **Stripe-schema-aware AI Assistant with two modes (Generate vs Edit).** The Edit mode is interesting — it operates on the current SQL rather than overwriting it, which is a thoughtful UX choice for iterative query refinement. Per-query chat history persists every prompt + response as a slider in the top-right.
- **Templates library tied to Stripe-specific business questions** (dispute evidence tracking, unpaid-invoice recovery, payout reconciliation) — the templates are genuinely operationally useful, not just SELECT-COUNT-* boilerplate.
- **Pricing aligned to charge volume, not seats or queries.** A team of 50 finance users on a small business pays the same Sigma fee as a team of 2 on the same charge volume. Removes adoption friction inside customers.
- **Promotion path from ad-hoc query → saved query → scheduled report → published Custom Metric.** Same artifact graduates through four levels of formality without rebuilding.
- **Integrated billing.** Sigma is just a Dashboard upsell — no separate subscription, no separate vendor relationship. Buying friction is roughly zero for existing Stripe users.

## Unique weaknesses / common complaints

- **SQL barrier despite "SQL knowledge not required" marketing.** The AI Assistant generates SQL, but Definite (2026) and Chartsy (2026) both flag that users still need SQL skill to validate, debug, and customize results. Two people asking the same question can get different answers because there is no semantic layer.
- **Data silo — Stripe-only.** No marketing, no product, no CRM, no support data. Cross-functional questions (e.g., "which channel drives highest LTV?") are structurally impossible inside Sigma.
- **Stale data.** API-backed tables refresh ~3h. Derived/analytics tables refresh 12–120h. Scheduled query results land "after 2pm UTC the day after they run" (Chartsy 2026). Several HN commenters called the freshness "laggy without a business reason."
- **Visualization is minimal.** Per the Definite 2026 critique: no drag-and-drop dashboard builder, no embedded charts, no scheduled email reports beyond the basic CSV. Charts cap at 10,000-row results. Most users export to spreadsheets.
- **Per-charge overage pricing punishes low-AOV merchants.** A merchant with €0.99 microtransactions pays the same overage as one with €99 transactions on Starter/Growth tiers.
- **No semantic layer / no canonical metric definitions.** MRR, churn, LTV must be re-implemented in SQL each time. Several reviewers note that complex SaaS-metric SQL runs to 20–50+ lines.
- **20-report cap on Custom Metrics.** Hard limit on how many Sigma reports can be promoted into the daily-monitoring surface.
- **Read-only dataset, no modifications.** "The available data within Sigma is read-only. Queries can't modify existing data or create new transactions" — limiting for any workflow that wants to write back tags or annotations.
- **No mobile app.** Sigma lives entirely inside the responsive web Dashboard.

## Notes for Nexstage

- **Sigma is not a head-to-head competitor.** Their universe is Stripe payments data; Nexstage's universe is Shopify/Woo + ad platforms + GSC + GA4 + (eventually) Stripe. Different paradigm — Sigma is a workbench, Nexstage is a dashboard. Sigma is interesting *as a UX reference* for the SQL-editor and AI-query patterns, not as a feature checklist.
- **Two-mode AI Assistant (Generate vs Edit) is a useful pattern.** If Nexstage ever ships natural-language query (over `daily_snapshots` etc.), separating "draft from scratch" from "modify what's on screen" is a cleaner mental model than a single chat input. The chat history slider per artifact is also nice — every iteration of a metric definition stays attached to the artifact.
- **Sigma exposes the schema explicitly in the left rail.** This is the opposite of Nexstage's "MetricSourceResolver hides which source you're reading from" thesis. Sigma users *want* to see "this column comes from `charges.amount`." For Nexstage's "6 source badges" approach, the Sigma model is a counter-example: Sigma trusts the user to understand provenance; Nexstage abstracts it.
- **Templates as onboarding scaffold.** Sigma's templates library is organized by business question ("disputes," "cash flow," "active customers"). Nexstage's onboarding could borrow this — a curated set of "common questions" pre-wired against the underlying data, where the user can either run them as-is or fork.
- **Promotion path is interesting.** Ad-hoc query → saved query → scheduled report → published Custom Metric is a four-stage formalization ladder. Nexstage's equivalent (custom view → pinned dashboard tile?) is worth thinking about as a progressive-formality pattern.
- **Pricing model parallel — and a warning.** Sigma's per-charge tiered pricing maps to Nexstage's per-store / per-revenue ideas. The HN complaint that low-AOV merchants get punished is a real risk for any volume-based pricing.
- **Data freshness reality check.** Sigma — the canonical "ETL is for chumps" pitch — still runs at 3h freshness for raw and 12–120h for derived. That makes Nexstage's hourly snapshot strategy feel reasonable, not slow.
- **"No semantic layer" critique applies to us too.** Definite's point that "two people asking similar questions might get different answers depending on how the AI interprets their prompts" is the exact case for `MetricSourceResolver` as a single source of truth — worth quoting in any internal pitch for keeping that resolver authoritative.
- **No public reviews on G2/Capterra/Trustpilot/SourceForge for Sigma specifically.** Stripe sells it as a Dashboard add-on rather than a standalone product, so it gets bundled into general Stripe reviews. This makes independent sentiment hard to gather; most criticism comes from competitor blogs (Definite, Chartsy) and the original 2017 HN launch thread.
- **No PNG screenshots saved per task constraints.** Marketing imagery on stripe.com/sigma shows a syntax-highlighted SQL editor with schema sidebar and a modal-style query example panel; the AI Assistant prompt input is pictured near the editor. UI details for Schedules and Custom Metrics surfaces were not directly observable from public sources — only documentation descriptions.
