---
name: Forecasting
slug: forecasting
purpose: Project where revenue, LTV, and cash will land next month so SMB owners can plan spend, headcount, and inventory before the period closes.
nexstage_pages: dashboard, profit, customers (LTV), performance (pacing)
researched_on: 2026-04-28
competitors_covered: lifetimely, polar-analytics, klaviyo, lebesgue, triple-whale, storehero, segmentstream, wicked-reports, putler, everhort, fospha, conjura, daasity, northbeam, ga4, metorik, bloom-analytics, beprofit, rockerbox, shopify-native, woocommerce-native, peel-insights, trueprofit
sources:
  - ../competitors/lifetimely.md
  - ../competitors/polar-analytics.md
  - ../competitors/klaviyo.md
  - ../competitors/lebesgue.md
  - ../competitors/triple-whale.md
  - ../competitors/storehero.md
  - ../competitors/segmentstream.md
  - ../competitors/wicked-reports.md
  - ../competitors/putler.md
  - ../competitors/everhort.md
  - ../competitors/fospha.md
  - ../competitors/conjura.md
  - ../competitors/daasity.md
  - ../competitors/northbeam.md
  - ../competitors/ga4.md
  - ../competitors/metorik.md
  - ../competitors/bloom-analytics.md
  - ../competitors/beprofit.md
  - ../competitors/rockerbox.md
  - ../competitors/shopify-native.md
  - ../competitors/woocommerce-native.md
  - ../competitors/peel-insights.md
  - ../competitors/trueprofit.md
  - https://useamp.com/products/analytics/lifetime-value
  - https://help.klaviyo.com/hc/en-us/articles/17797865070235
  - https://help.klaviyo.com/hc/en-us/articles/360020919731
  - https://lebesgue.io/lebesgue-mmm
  - https://lebesgue.io/customer-lifetime-value
  - https://www.fospha.com/platform/beam
  - https://help.everhort.com/article/10-ltv-summary
  - https://www.putler.com/docs/category/putler-dashboards/time-machine/
  - https://storehero.ai/features/
  - https://intercom.help/polar-app/en/collections/12139761-incrementality-testing
---

## What is this feature

Forecasting answers the SMB owner's most-asked question of the week: "Given everything I know today, what will revenue, LTV, and cash actually be next month?" Every connected source platform (Shopify, Meta Ads, Klaviyo, GA4) already has a "trend chart" of historical data — that is **not** forecasting. Forecasting is the synthesis layer: a model (linear regression, Prophet/ARIMA, Bayesian saturation curve, ML cohort decay, or human-curated goal-with-pace) that turns that history into a forward bar, line, or single number with a confidence band. The merchant cares about three downstream decisions — should I scale ad spend, can I afford the next inventory PO, should I hire a fulfilment headcount — and a credible forecast collapses dozens of trend charts into one "this is where you'll land" answer.

For Shopify/Woo SMB owners specifically, forecasting matters because their margin of error is tiny (a $30k cash gap kills the brand) and their planning horizon is short (this month, this quarter — not annual budgets). Klaviyo predicts next-order date per customer, Lifetimely predicts cohort LTV at 3/6/9/12 months, Putler predicts month-end revenue, Lebesgue forecasts 60 days of revenue with Prophet/ARIMA/exponential smoothing, Fospha forecasts daily revenue ranges with confidence intervals, StoreHero auto-pro-rates an annual goal into seasonally-adjusted monthly benchmarks. The "feature" is whichever of these collapses the future into one legible visual, not the underlying math.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.total_price`, `orders.created_at`, `orders.customer_id`, `orders.line_items.cost`, `orders.refunds`, `customers.first_order_at`, `customers.orders_count` — the cohort spine for LTV forecasts and the time series for revenue forecasts.
- **Source: Shopify subscription / ReCharge** — `subscriptions.next_charge_at`, `subscriptions.recurring_amount`, `subscriptions.churned_at`, `subscriptions.frequency` — required for subscription-rebill re-pricing (Wicked Reports' patents-pending; Lifetimely's ReCharge feed).
- **Source: Meta / Google / TikTok Ads** — `campaigns.spend`, `campaigns.impressions`, `campaigns.conversions` — required for ad-spend forecasts, saturation curves (Fospha Beam), MMM (Lebesgue), and pacing (StoreHero, Triple Whale Order/Revenue Pacing Agent).
- **Source: Klaviyo** — `events.email_send`, `events.email_click`, `flows.attributed_revenue` — required for predictive CLV and predicted-next-order-date (Klaviyo's own model).
- **Source: GA4** — `events.purchase`, `users.predicted_purchase_probability`, `users.predicted_revenue` — Google's pre-trained predictive metrics (gated behind 1,000 positive + 1,000 negative samples over 28 days).
- **Source: User-input** — `annual_revenue_goal`, `monthly_revenue_goal`, `cac_target`, `ltv_horizon_months`, `forecast_growth_rate`, `forecast_churn_rate`, `forecast_traffic_multiplier`, `forecast_conversion_multiplier`, `forecast_arpu_multiplier`, `holiday_calendar` — Putler's Time Machine takes all five multipliers as sliders; StoreHero takes a single annual goal; Lifetimely takes a manual CAC value to anchor the green payback bar.
- **Source: Computed** — `cumulative_ltv_per_cohort = SUM(orders.contribution_margin) GROUP BY cohort_month, age_in_months`; `forecasted_ltv = linear_regression(recent_cohorts) extrapolated to horizon` (Everhort verbatim); `predicted_clv = ML_BTYD_model(orders, customer_history)` (Klaviyo); `saturation_curve = bayesian_fit(spend, revenue) per channel` (Fospha Beam, Lebesgue MMM); `payback_month = month where cumulative_ltv >= cac` (Lifetimely green bar); `revenue_forecast_60d = ensemble(prophet, arima, exp_smoothing)` (Lebesgue).
- **Source: Eligibility thresholds** — `customer_count >= 500`, `order_history_days >= 180`, `repeat_purchasers >= 3`, `recent_orders_30d >= 1` (Klaviyo predictive gate); `monthly_ad_spend >= $5,000`, `history_months >= 3` (Lebesgue MMM gate); `pixel_data_days >= 5-7` (Triple Whale baseline); `1,000 positive + 1,000 negative samples / 28d` (GA4 predictive audiences).

## Data outputs (what's typically displayed)

- **KPI: Forecasted revenue (next 30/60/90d)** — point estimate with confidence interval (Fospha: "$6.5k between $5.5k–$7.5k"); Lebesgue 60-day prediction; Putler 12-month projection.
- **KPI: Predicted CLV per customer** — single dollar value per profile (Klaviyo: "Predicted CLV"); horizons at 3/6/9/12 months (Lifetimely); 1Y/2Y/3Y bars (Everhort).
- **KPI: CAC ceiling** — `forecasted_LTV / 3` (Everhort fixed industry-standard ratio).
- **KPI: Payback month** — month-since-acquisition where cumulative LTV crosses the CAC threshold (Lifetimely green bar overlay).
- **KPI: Predicted next-order date** — date per customer (Klaviyo diamond glyph on order timeline).
- **KPI: Churn risk** — low/medium/high traffic-light per customer (Klaviyo).
- **KPI: Pacing vs. goal** — month-to-date actual divided by pro-rated target; green/red traffic-light deviation (StoreHero); on-pace vs. drifted (Polar Goals & Forecasts).
- **KPI: Forecasted month-end revenue** — single number rendered inline with daily-sales mini-chart (Putler Pulse zone).
- **Dimension: Cohort (acquisition month)** — required for LTV forecasts; one line/bar per cohort.
- **Dimension: Channel** — for saturation-curve forecasts (Fospha Beam per-channel; Rockerbox budget-mix scenario).
- **Dimension: Time horizon** — 30 / 60 / 90 / 180 / 365 days; 1Y / 2Y / 3Y for LTV; 12-month for revenue.
- **Breakdown: Cumulative LTV × cohort × month-since-acquisition** — line chart (Everhort), heatmap with predictive overlay (Lifetimely cohort matrix).
- **Breakdown: Spend → revenue saturation curve × channel** — scatter with fitted line + CI shading (Fospha Beam).
- **Breakdown: Historic revenue + predicted revenue × time** — single bar per customer or stacked overlay (Klaviyo CLV bar; Lebesgue revenue forecast graph).
- **Slice: Cohort filtered by acquisition source / channel / product** — applies above outputs to a sub-cohort (Wicked Reports cohort-with-attribution-filter).
- **Confidence indicator** — explicit CI bracket `[low%, high%]` (SegmentStream geo lift); `Good`/`Excellent` accuracy band (Fospha); RMSE/R² (Fospha glass-box); model-confidence score (Lebesgue MMM PDF).

## How competitors implement this

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Lifetime Value Report; Predictive LTV (AI) is a sub-tab / metric overlay inside the cohort heatmap.
- **Visualization:** Cohort heatmap with predictive overlay (rows = acquisition month, columns = months-since-first-order, cells render predicted LTV at 3/6/9/12mo when toggled) **plus** a separate cumulative cohort waterfall bar with a user-configurable horizontal **green CAC-payback line**.
- **Layout (prose):** "Top: filter strip (first-order product, source/medium, country, tags). Left rail: time-period toggle (weekly / monthly / yearly cohorts). Main canvas: 2-D heatmap matrix with color-gradient cells (light → saturated for higher accumulated values), 13+ selectable cell metrics including 'predicted LTV at 3/6/9/12mo'. Bottom: cohort waterfall — vertical accumulating bar segments per month, with a horizontal green line drawn at the user-entered CAC value; payback month is read off where bar crosses line."
- **Specific UI:** "User-configurable horizontal **green CAC-payback bar** annotated on the cumulative cohort waterfall — user types their CAC value into a numeric input and the green threshold redraws; the bar visually crosses it at the payback month." (1800DTC). Color-gradient cells "clean grid structure with color gradients to represent performance variations across customer cohorts" (useamp.com).
- **Filters:** Date range, cohort timeframe (W/M/Y), first-order product, first-order category, source/medium, marketing channel (first-touch OR last-touch), country, customer tags, order tags, discount codes.
- **Data shown:** Accumulated sales per customer, accumulated gross margin per customer, accumulated contribution margin per customer, predicted LTV at 3/6/9/12mo, repurchase rate, AOV — all by cohort × age.
- **Interactions:** Filter chips, cohort-timeframe toggle, metric dropdown, predictive overlay toggle, manual CAC entry to position the green bar.
- **Why it works (from reviews/observations):** "helped us understand our Lifetime Value better than anything else" (Blessed Be Magick, Shopify App Store). "removes the hassle of calculating a customer's CAC and LTV" (ELMNT Health). 12% average LTV increase claim (no methodology published).
- **Source:** [lifetimely.md](../competitors/lifetimely.md); https://useamp.com/products/analytics/lifetime-value; https://1800dtc.com/breakdowns/lifetimely.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Sidebar > Goals & Forecasts (separate from Causal Lift / Incrementality which carries its own forecasted-impact panel).
- **Visualization:** Target-line overlay on existing dashboard charts — annual targets pro-rated to daily milestones rendered as a horizontal target line on the metric's main chart. Causal Lift uses a different surface — point estimate + confidence interval line graph for in-flight test forecasts.
- **Layout (prose):** "Set annual or monthly targets per metric; system auto-pro-rates to daily milestones and renders a target line on charts." Causal Lift dashboard layout: "live experiment dashboard, showing in-flight metrics, forecasted impact, and final lift results with confidence intervals."
- **Specific UI:** Target line overlaid on existing dashboard chart blocks. Causal Lift confidence-interval bounds shown around point estimate. "True CAC" / "True ROAS" with statistical significance label.
- **Filters:** Standard dashboard filters (Views — saved-filter system); date range; metric.
- **Data shown:** Goal value, pro-rated daily milestone, pace vs. milestone; for Causal Lift — incremental revenue, true CAC, true ROAS, statistical significance, CI bounds.
- **Interactions:** Set annual/monthly target → auto pro-rating; live experiment monitoring; comparison of forecasted vs realized lift at end of test.
- **Why it works (from reviews/observations):** Polar emphasizes Causal Lift / incrementality as a sales hook ("Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory, Shopify App Store, September 2025). The Goals & Forecasts surface itself is mentioned in feature lists but not in any verbatim user testimonial.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md); https://www.polaranalytics.com/business-intelligence; https://intercom.help/polar-app/en/collections/12139761-incrementality-testing.

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Marketing Analytics > Customer insights > CLV dashboard; Profile page > Metrics and insights tab (per-customer predictive panel).
- **Visualization:** **Horizontal stacked bar — blue segment is historic CLV (already spent), green segment is predicted CLV (next 365 days); full bar represents Total CLV.** On the profile's order timeline, **diamond-shaped tick marks** mark the predicted next-order date.
- **Layout (prose):** "Five vertically stacked cards on the CLV dashboard. **Current Model** card pinned at top — shows historic date range, predicted date range, last model retrain date, and five example customer profiles demonstrating CLV calculation. **Segments Using CLV** card — table of segments leveraging predictive CLV attributes with profile count, CLV attribute used, last update date. **Upcoming Campaigns Using CLV** — table of scheduled campaigns by CLV attribute with status pill (Scheduled/Sending) and channel icon. **Flows Using CLV** — analogous table with status (Live / Manual / Draft). **Forms Using CLV** — connected forms with form type (Popup / Flyout / Embed / Full Page)."
- **Specific UI:** "Horizontal stacked bar where the blue segment is historic CLV (already spent) and the green segment is predicted CLV (next 365 days); the full bar represents Total CLV. On the profile's order timeline, diamond-shaped tick marks mark the predicted next-order date." Churn-risk uses **traffic-light color coding — green for low risk, yellow for medium, red for high**.
- **Filters:** CLV-window selector (Marketing Analytics tier lets you customize prediction window beyond default 365 days); date range; segment.
- **Data shown:** Predicted CLV, Historic CLV, Total CLV, Predicted Number of Orders, Historic Number of Orders, AOV, Average Days Between Orders, expected date of next order, churn risk score, predicted gender.
- **Interactions:** Click any segment row to view profiles; click campaigns/flows/forms to navigate into builders; customize prediction window (Marketing Analytics tier).
- **Eligibility gate:** "at least 500 customers have placed an order," 180+ days of order history, orders in the last 30 days, three or more repeat purchasers — predictive features hide until thresholds are met. Model retrains "at least once a week."
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group. The blue/green stacked CLV bar is the most-quoted predictive primitive in any retention tool — splits "what happened" + "what will happen" into one scannable visual.
- **Source:** [klaviyo.md](../competitors/klaviyo.md); https://help.klaviyo.com/hc/en-us/articles/17797865070235; https://help.klaviyo.com/hc/en-us/articles/360020919731.

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** KPI dashboard / forecasting (Henri AI hub); Meta Ads Forecast (predicted ad performance); Marketing Mix Modeling (weekly PDF + in-app prediction graph); LTV & Cohort dashboard (predicted LTV value cards).
- **Visualization:** Mixed — line chart for 60-day revenue prediction (Prophet/ARIMA/exponential-smoothing ensemble); multi-channel budget allocation diagram for MMM; predicted LTV value cards + CAC vs. LTV comparison block; in-app "revenue prediction graph showing current vs. optimized marketing spend."
- **Layout (prose):** "MMM page shows in-app 'revenue prediction graph showing current vs. optimized marketing spend' and 'budget allocation dashboard displaying multi-channel connections.' Weekly PDF deliverable contains: budget redistribution recommendations, revenue forecasts, channel saturation insights, model confidence score." Henri responses include "Key Takeaways sections, and Recommendations formatted as actionable next steps beneath performance analysis charts."
- **Specific UI:** "Color-coded performance indicators (blue for improvements, red for declines)" — note **blue, not green, for positive deltas** (unusual and explicitly documented). Multi-channel budget allocation visualization; current-vs-optimized revenue prediction line; confidence-score indicator; predicted LTV value cards.
- **Filters:** Marketing channel (Google / Meta / TikTok), product, geography (state / country); date range; period grouping (day / week / month).
- **Data shown:** Revenue forecast (60-day predictions), predicted LTV, average LTV, retention rate by cohort, CAC vs. LTV, high-LTV products, budget redistribution recommendations, model confidence score, channel saturation insights.
- **Interactions:** Henri natural-language prompts (sample: "Analyze how our store performed over the last 30 days compared to the same period last year"); MMM PDF arrives weekly; in-app current-vs-optimized toggle.
- **Eligibility gate:** MMM "recommended minimum $5,000/mo ad spend and 3+ months of history. Free during beta."
- **Why it works (from reviews/observations):** "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, **forecast for sales**." — Sasha Z., Founder (Retail), Capterra, September 30, 2025. "The metrics and pacing data delivered via email save time." — Marco P., Owner (Online Media), Capterra, January 6, 2025.
- **Source:** [lebesgue.md](../competitors/lebesgue.md); https://lebesgue.io/lebesgue-mmm; https://lebesgue.io/customer-lifetime-value; https://www.producthunt.com/products/lebesgue-ai-cmo.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Moby Agents > Order & Revenue Pacing Agent; Moby Chat (returns generated forecasts in conversational answers); 60/90 LTVs surfaced in Cohorts/Customer Insights.
- **Visualization:** Agent output rendered back to dashboard tile or email — exact chart type is agent-configurable. Moby Chat returns "generated charts, tables, forecasts" inline. Cohorts panel surfaces "real-time MER/ncROAS/POAS, 60/90 LTVs."
- **Layout (prose):** "Agent collection pattern — pre-built agents per role (Media Buying, Retention Marketing, Website Performance, Anomaly Detection, Measurement, Creative Strategy, **Order & Revenue Pacing Agent**, Revenue Anomaly Agent). Each agent runs autonomously and writes outputs back to dashboards or email." Moby Chat panel — natural-language questions return forecasts.
- **Specific UI:** Per-agent configuration card with output destination selector (dashboard vs email vs Slack). Credit-based pricing ("Moby AI Pro") with **fail-closed billing — credits pause when depleted, no auto-overages**. UI details beyond this not directly verifiable from public sources.
- **Filters:** Per-agent configuration; standard dashboard filters.
- **Data shown:** Pacing data (revenue / orders); 60- and 90-day LTV; forecasts surfaced through Moby chat.
- **Interactions:** Configure agent → autonomous run → output to chosen destination; Moby Chat natural-language query → embedded chart/forecast.
- **Why it works (from reviews/observations):** "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." — Steve R., Capterra. Counter-signal: "Building with the AI tool Moby is very buggy and crashes more than half the time." — Trustpilot reviewer.
- **Source:** [triple-whale.md](../competitors/triple-whale.md); https://triplewhale.com/moby-ai; https://triplewhale.com/blog/product-event.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Goals & Forecasting module (standalone tab); Spend Advisor (forward simulator).
- **Visualization:** Goals: "annual goal entry → auto-generated month-by-month seasonally-adjusted benchmark grid" with **two-state green/red traffic-light dots** attached to each monthly KPI tile. Spend Advisor: input → live profit calculation → discrete recommendation pill (pause / pivot / scale).
- **Layout (prose):** "Goals & Forecasting standalone module — annual goal entry input then auto-seeded month-by-month seasonally-adjusted benchmarks. Each monthly benchmark or KPI tile carries a green/red dot showing on-pace vs. drifted." Spend Advisor: "Watch how every $100 you invest into ads changes profit in real time" — interactive slider or live-updating numeric input with profit-impact panel beside it; recommendation pill surfaces pause / pivot / scale verdict.
- **Specific UI:** "Green & red traffic-light dots, no amber/yellow middle — explicitly two-state, not three-state." Spend Advisor produces "instant recommendation to pause, pivot, or scale" — three discrete recommendation states surfaced as labels/pills.
- **Filters:** Annual goal input; date range; channel (Spend Advisor scoping).
- **Data shown:** Goals — monthly benchmarks vs. actuals for revenue, contribution margin, channel-level ad spend. Spend Advisor — Net Sales, MER, Ad Spend, Contribution Profit, plus pause/pivot/scale recommendation.
- **Interactions:** Annual goal entry → auto month seeding; ad-spend input → live profit recalculation; drift triggers visible alert.
- **Why it works (from reviews/observations):** "I'm so happy with the platform — we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" — Jordan West (storehero.ai). "StoreHero has helped us multiply our business by finding the sweet spot between scale and profit." — Donal Breslin, Nomad the Label.
- **Source:** [storehero.md](../competitors/storehero.md); https://storehero.ai/features/.

### SegmentStream ([profile](../competitors/segmentstream.md))
- **Surface:** Geo Tests / Incrementality (test design + results); Cross-channel attribution + maturation curve (separate); Lead Scoring / LTV Scoring panels; Marginal Analytics saturation curve.
- **Visualization:** **Per-row format `<Channel> — <Treatment> | <Significance pill> · [CI low, CI high] | <point estimate>`** for incrementality test results. Maturation curve: "Observed vs Projected cumulative conversions over 42 days, with confidence metrics" — two-line overlay (observed dotted/colored, projected dashed) plus confidence band. LTV Scoring: customer-profile card with single highlighted "predicted 12-month LTV ($4,800)."
- **Layout (prose):** Geo Tests results: each test row shows "Significance pill (green 'Significant' vs gray 'Inconclusive') as a leading badge per row, **confidence interval bracket shown verbatim with brackets `[low%, high%]`, distinct from the point estimate**, and the point estimate (leading-positive `+35%`) to the right of the CI." Maturation table: "Last 7d: 53%, +1 week: 78%, +2 weeks: 95%, +3 weeks: 99%."
- **Specific UI:** "Significance pill — green 'Significant' vs gray 'Inconclusive' tag as a leading badge per row. CI bracket shown verbatim. Point estimate leading-positive. Synthetic-control weights exposed in methodology: 'California×0.64 Ohio×1.19 Nevada×2.08'." Marginal Analytics: revenue/spend curve per channel with two annotated points: "Optimal Spend" and "Diminishing returns".
- **Filters:** Channel, Duration, MDE, Sales cycle, Test regions, Control regions; per-channel toggle on saturation curve.
- **Data shown:** Incremental lift %, confidence interval, MDE, ROAS at lift, sales cycle adjustment window; observed vs projected cumulative conversions; maturation %; predicted 12-month LTV; saturation curves with optimal/diminishing-returns annotations.
- **Interactions:** Configure test from UI OR from MCP-connected AI tools (Claude Code, Cursor, Codex). Power analysis (MDE) calculated upfront and flagged if the test is underpowered.
- **Why it works (from reviews/observations):** "A one-of-a-kind attribution, optimisation and budget allocation tool." — G2 reviewer. Note the **glass-box transparency** — synthetic-control weights, RMSE, R², CI brackets all surfaced rather than hidden.
- **Source:** [segmentstream.md](../competitors/segmentstream.md); https://segmentstream.com/measurement-engine/incrementality.

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** Customer Cohort / LTV Report; ReCharge subscription-aware re-pricing (continuous update of revenue and ROI as subscriptions rebill); 5 Forces AI verdict view (forward weekly verdict).
- **Visualization:** Cohort matrix with attribution filter as a slicer (rows = acquisition months, columns = subsequent months of accumulated revenue per cohort). 5 Forces AI: three-state verdict pill — **Scale / Chill / Kill** — per campaign, weekly cadence.
- **Layout (prose):** "Filter strip above the matrix lets users slice the entire grid by 'attributed source, campaign, ad, email, and targeting that generated the new lead or customer'. Individual customer records are clickable from related views." 5 Forces AI: "Weekly verdicts surface per campaign — three-state output: Scale / Chill / Kill — each with justification text 'you can defend' (verbatim, brand page)."
- **Specific UI:** Cohort matrix with attribution slicer at the top. Three-state pill or badge labeling per campaign with **user-tunable nCAC threshold settings as inputs** that drive the verdict; **justification text** rendered alongside each verdict.
- **Filters:** Attributed source, campaign, ad, email, targeting; nCAC threshold (5 Forces AI input); date range; lookback / lookforward window.
- **Data shown:** Cumulative customer LTV per cohort over time; attributed marketing credit sources per cohort; subscription rebill revenue (when ReCharge integrated); nCAC vs. user threshold; recommended action verdict; justification text.
- **Interactions:** Filter cohort by attributed source / campaign / ad / email / targeting; click a customer record to open the full journey detail; **infinite lookback / lookforward window** ("Attribution Time Machine"); user-tunable nCAC thresholds.
- **Why it works (from reviews/observations):** "[Got] 500% ROI from some of the traffic sources" when analyzing "lifetime value of customers over time." — Henry Reith. "Continuous update of revenue and ROI when subscriptions rebill" reveals "cold traffic top of the funnel winning campaigns that look unprofitable with the delayed subscription rebills."
- **Source:** [wicked-reports.md](../competitors/wicked-reports.md); https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting; https://www.wickedreports.com/wicked-recharge.

### Putler ([profile](../competitors/putler.md))
- **Surface:** Time Machine Dashboard (sidebar > Time Machine); Home > Pulse zone (forecasted month-end revenue inline); Products dashboard (predicted monthly sales per product card).
- **Visualization:** **Three primary forecasting modules stacked or tabbed: Revenue Forecast (12-month projection chart), Customers Forecast (12-month customer-count projection), and 10x Forecast (a reverse-engineered scenario showing what traffic/conversion/ARPU multipliers would be required to 10x revenue).** Line/area charts for forecasts.
- **Layout (prose):** "Three primary forecasting modules stacked or tabbed. The dashboard also exposes a 'Performance Comparison Report' for side-by-side metric analysis between any two date ranges, and a Holiday Season tracking module covering Halloween, Thanksgiving, Black Friday, Cyber Monday, and Christmas." Pulse zone (Home): "current-month-to-date sales, daily-sales mini-chart, 3-day trend, current-month target setting, year-over-year comparison vs same month previous year, and a forecasted month-end sales number — all stacked together as one widget."
- **Specific UI:** "Adjustable variable inputs (growth rate, churn rate, traffic multiplier, conversion multiplier, ARPU multiplier) — described as 'interactive forecasting with adjustable variables for scenario planning'. **10x model uses linear growth assumption** ('assumes a 10x growth, although it's technically a 12x growth'). Holiday module compares this-year vs prior-year revenue for each named holiday window."
- **Filters:** Variable inputs (growth rate, churn rate, traffic multiplier, conversion multiplier, ARPU multiplier); date range; product; holiday window.
- **Data shown:** Projected MRR, projected revenue, projected customer count, churn rate, growth rate, holiday-window revenue YoY, predicted future product sales, forecasted month-end sales (Pulse zone).
- **Interactions:** Slider/input controls for forecast variables → recompute on change; side-by-side compare picker for the Performance Comparison Report.
- **Why it works (from reviews/observations):** Time Machine 10x Forecast praised as novel reverse-engineered planning ("what would have to be true to hit X?"). Pulse-zone month-end forecast paired with daily mini-chart is a unique IA choice.
- **Source:** [putler.md](../competitors/putler.md); https://www.putler.com/docs/category/putler-dashboards/time-machine/.

### Everhort ([profile](../competitors/everhort.md))
- **Surface:** Forecasted Average LTV (LTV Summary report).
- **Visualization:** **Two-column layout — bar chart on the left (projected average LTV at multiple horizons, e.g. 1Y/2Y/3Y for a 12-month baseline), metrics table on the right (estimated CAC targets and corresponding payback periods per horizon).** When filters applied, bars become side-by-side dual bars: **light green bars show baseline performance, gray bars represent filtered cohort performance** — direct A/B between cohort and baseline.
- **Layout (prose):** "Bars show projected average LTV at multiple horizons. Right-side table lists 'estimated CAC (customer acquisition cost) targets along with corresponding payback periods for each of the forecasted LTV time periods.'"
- **Specific UI:** Side-by-side bars (light green baseline + gray filtered) for filter-vs-baseline comparison. **Forecast computed by transparent linear regression** — "linear regression of the blended average LTV of recent cohort performance" extrapolates "the best possible straight line." CAC target uses fixed LTV/CAC = 3 ratio (KB example: "$108 two-year LTV forecast" → "$36 CAC target"). Tabular companion below has dashed-border cells for ongoing/incomplete periods.
- **Filters:** Customer (tagged), Order (5 dimensions × 4 matchers × first/subsequent/any qualifier), Channel.
- **Data shown:** Forecasted LTV at 1Y / 2Y / 3Y; recommended CAC ceiling at each horizon; payback period.
- **Interactions:** Filter toggle → side-by-side baseline-vs-filtered bars; CSV download.
- **Why it works (from reviews/observations):** Methodology is **glass-box** — KB explicitly states "linear regression of the blended average LTV of recent cohort performance" with no black-box ML. Filter-vs-baseline overlay is the core analytical motif; works the same way on the forecast bars.
- **Source:** [everhort.md](../competitors/everhort.md); https://help.everhort.com/article/10-ltv-summary.

### Fospha ([profile](../competitors/fospha.md))
- **Surface:** Beam (Incremental forecasting); Spend Strategist (predictive spend allocation); Glow (long-term brand campaign forecast — Beta).
- **Visualization:** **Headline Beam visualization is a scatter plot of revenue vs. spend with a fitted trend line and confidence-interval shading (the saturation curve).** Predictive metric tiles render forecasted daily revenue ranges (e.g., "$6.5k between $5.5k–$7.5k") and ROAS projections. Accuracy indicators are line graphs tracking model performance within "Good" and "Excellent" ranges.
- **Layout (prose):** "Scatter plot with confidence-interval shading (not just a line); explicit ranges in metric tiles ('$5.5k–$7.5k' rather than a point estimate); accuracy line graph with named bands ('Good,' 'Excellent')."
- **Specific UI:** Bayesian saturation curves per channel — claimed accuracy "83% of actual outcomes lying within predicted range." **RMSE / R² model accuracy metrics surfaced in the UI** ("glass-box transparency"). AI-powered insight callouts warn of revenue drops and corresponding ROAS impacts. Spend Strategist: per-channel spend slider → forecasted ROAS / conversions / new conversions / revenue at different spend levels.
- **Filters:** Channel; spend level (Spend Strategist slider); date range.
- **Data shown:** Forecasted daily revenue (with confidence intervals — explicit ranges, not point estimates), forecasted ROAS, channel saturation level, RMSE / R² accuracy metrics, optimal-vs-diminishing-returns annotations.
- **Interactions:** Spend slider per channel → recompute forecast; AI insight callouts surface revenue-drop warnings.
- **Why it works (from reviews/observations):** "Smartly's Predictive Budget Allocation helped us scale paid social with confidence." — Daniel Green, Gymshark. "Attribution is a new way of looking at things which the team are really excited about, especially being able to identify headroom in channels." — Thomas May, Thread.
- **Source:** [fospha.md](../competitors/fospha.md); https://www.fospha.com/platform/beam.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Owly AI assistant (forecasting subset baked in: "30, 60 and 90-day revenue and SKU-level stock predictions").
- **Visualization:** No visualization confirmed publicly — Owly responses output as "instant answers (text replies), deep-dive reports (longer documents), strategic insights/recommendations (action prompts), complete proposal documents." Whether forecasts render as auto-generated charts, cards, or pure text is **not specified** on public pages.
- **Layout (prose):** Plain-English query → AI scans data → returns answer + recommendations. No standalone forecast surface; lives inside the conversational AI module.
- **Specific UI:** UI details not available — only feature description seen on marketing page.
- **Filters:** Natural-language; product / SKU; horizon (30/60/90 days).
- **Data shown:** Revenue prediction at 30/60/90d; SKU-level stock prediction; Promotion Predictor (Daasity-adjacent — model and forecast campaign impact before deployment).
- **Interactions:** Natural-language query → text/document response.
- **Why it works (from reviews/observations):** Forecasting is positioned as a "subset" of the AI's broader Q&A surface, not a discrete dashboard.
- **Source:** [conjura.md](../competitors/conjura.md).

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** MMM+ (separate Enterprise product/upsell) — "industry's first self-service MMM dashboard."
- **Visualization:** "Browser-based, customizable dashboards" with budget-mix scenarios and forecasts. Live/dynamic rather than static report exports.
- **Layout (prose):** "Marketed as 'the industry's first self-service MMM dashboard' with 'browser-based, customizable dashboards' — users 'adjust their models and budget mixes on the fly' and create flexible forecasts and budget scenarios. Ingests native MTA data and exogenous features."
- **Specific UI:** UI details not directly observable — gated behind Enterprise tier; not in public docs.
- **Filters:** Channel; budget mix scenario; model parameters.
- **Data shown:** Budget-mix scenarios, projected revenue/efficiency per scenario.
- **Interactions:** Adjust models and budget mixes on the fly; flexible forecasts and budget scenarios.
- **Why it works (from reviews/observations):** Northbeam's **Day 30/60/90 progressive feature unlock** is itself a forecasting-adjacent UX — Apex, Clicks + Modeled Views, Profit Benchmarks unlock sequentially as the model trains. Honest signal about ML calibration time.
- **Source:** [northbeam.md](../competitors/northbeam.md).

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Audiences > Predictive (Predictive metrics: Purchase probability, Churn probability, Predicted revenue).
- **Visualization:** "Histogram slider lets the user pick a threshold percentile and shows estimated audience size + likelihood-to-convert score live as the slider moves." "Ready to use" badge appears when ML model thresholds met.
- **Layout (prose):** "Modal/page split into left 'templates' rail and right canvas. Templates: General, Template (Demographics, Predictive, Recently active), Predictive (must meet ML model thresholds — surfaced as 'Ready to use' badge). The canvas builds a step-based condition group."
- **Specific UI:** Histogram slider with threshold percentile selector; live audience-size estimate updates as slider moves; "Ready to use" badge when eligible.
- **Filters:** Threshold percentile (slider); time window; condition group (sequence-step toggle, "for any visit" / "across all sessions" scope).
- **Data shown:** Purchase probability, Churn probability, Predicted revenue per user; estimated audience size; likelihood-to-convert score.
- **Interactions:** Drag slider → live audience-size and conversion-likelihood updates.
- **Eligibility gate:** Requires ≥1,000 positive + ≥1,000 negative samples over 28 days — "most Nexstage-target SMBs (under ~$1M ARR) don't qualify."
- **Why it works (from reviews/observations):** Predictive metrics syncable to Google Ads as audiences (closing the measurement → activation loop).
- **Source:** [ga4.md](../competitors/ga4.md); https://support.google.com/analytics/answer/9805833.

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Forecasts (sales forecasting); Products Report (inventory forecasts).
- **Visualization:** Sales-forecast and inventory-forecast modules — UI details not directly observable from public sources.
- **Layout (prose):** Listed as standalone "Forecasts" surface and as inventory forecasts inside the Products Report.
- **Specific UI:** UI details not available — only feature description seen.
- **Filters:** Date range; product.
- **Data shown:** Forecasted sales; inventory forecasts; net profit (after COGS / ad spend / shipping).
- **Interactions:** Standard report filters.
- **Why it works (from reviews/observations):** Not surfaced in public verbatim reviews specific to forecasting.
- **Source:** [metorik.md](../competitors/metorik.md).

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** "Profit Forecast (adding soon)" — listed on pricing page roadmap.
- **Visualization:** Not yet shipped.
- **Layout (prose):** Roadmap-only — "Marketing Intelligence (adding soon), **Profit Forecast (adding soon)**, AI Insights (adding soon), and Amazon integration."
- **Specific UI:** Not observed — pre-launch.
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md).

### Rockerbox ([profile](../competitors/rockerbox.md))
- **Surface:** Budget-allocation/forecast wizard.
- **Visualization:** "Generate Forecast" plan view comparing baseline vs. proposed allocation.
- **Layout (prose):** "Step-driven flow: configure objective, set constraints, set optional budget cap, generate forecast. Constraint default: 30% per-channel change tolerance; user-selectable steps at 15%, 30%, 50%, 100%. Output: model-recommended channel mix with projected revenue/efficiency."
- **Specific UI:** Constraint sliders/selector with discrete preset values (15/30/50/100%); per-channel constraint refinement; "Generate Forecast" CTA.
- **Filters:** Objective, constraints, optional budget cap.
- **Data shown:** Projected revenue/efficiency per scenario; baseline vs. proposed channel mix.
- **Interactions:** Set global constraint, override per channel, set budget cap (optional), Generate Forecast.
- **Source:** [rockerbox.md](../competitors/rockerbox.md).

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Promotion Predictor — "model and forecast campaign impact before deployment."
- **Visualization:** UI details not available — only feature description seen on marketing page.
- **Source:** [daasity.md](../competitors/daasity.md).

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Magic Dash (cohort metrics include pacing graphs); explicit constraint that "Magic Dash cannot generate audiences from a widget, cannot answer forecasting/predictive questions" — Peel does NOT do forecasting via the AI surface (deliberate scope decision).
- **Visualization:** Pacing graphs available as a chart type alongside line/bar/stacked-bar/pie/cohort-table/cohort-curve. UI details on the pacing-graph specific render not available.
- **Layout (prose):** Pacing graphs are one of the chart types available in the cohort builder; not a forecasting product per se.
- **Specific UI:** Pacing graphs chart type; annotation pins on graphs to mark campaign moments.
- **Filters:** Standard cohort filters.
- **Data shown:** Pacing across cohort metrics.
- **Source:** [peel-insights.md](../competitors/peel-insights.md).

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** No predictive LTV / cohort grid. **Explicitly absent**.
- **Visualization:** No forecasting visualization. CLV is "current-state ratios, not cohort-based forecasting."
- **Source:** [trueprofit.md](../competitors/trueprofit.md).

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** "Predicted Cost" feature — calculates a return estimation curve over multiple days (e.g., "10% of order value first day, 7% second day"). Models returns as a decaying function rather than waiting for actual returns to land.
- **Visualization:** Decay curve. UI details not directly observable.
- **Source:** [beprofit.md](../competitors/beprofit.md).

### Shopify Native / WooCommerce Native ([shopify](../competitors/shopify-native.md), [woo](../competitors/woocommerce-native.md))
- **Surface:** Neither native admin ships forecasting. Shopify: "No native predictive / anomaly alerts. Backward-looking only — no churn prediction, no automatic notification when conversion drops." Woo: "No forecasting of any kind — explicitly 'entirely backward-looking' per Putler."
- **Source:** [shopify-native.md](../competitors/shopify-native.md), [woocommerce-native.md](../competitors/woocommerce-native.md).

## Visualization patterns observed (cross-cut)

Synthesized count by viz type across the 23 competitor profiles read:

- **Stacked bar (historic + predicted, two segments):** 1 competitor (Klaviyo — blue historic + green predicted CLV). Single most-quoted predictive primitive in the retention category.
- **Cohort heatmap with predictive overlay (toggleable):** 1 competitor (Lifetimely — predicted LTV at 3/6/9/12mo as a selectable cell metric inside the existing cohort grid).
- **Cohort waterfall with user-positioned threshold line:** 1 competitor (Lifetimely — green CAC-payback bar overlay on cumulative-cohort waterfall).
- **Side-by-side baseline-vs-filtered bars:** 1 competitor (Everhort — light green baseline bars + gray filtered bars at 1Y/2Y/3Y horizons).
- **Scatter + fitted curve + confidence-interval shading (saturation curve):** 2 competitors (Fospha Beam, SegmentStream Marginal Analytics) — Bayesian saturation curve presentation.
- **Line chart with confidence interval band / range tile:** 2 competitors (Fospha — "$5.5k–$7.5k" range tiles; SegmentStream — observed-vs-projected line with CI band).
- **Multi-module forecast dashboard with adjustable variable inputs (sliders):** 1 competitor (Putler — Revenue / Customers / 10x forecasts with growth-rate, churn-rate, traffic, conversion, ARPU multipliers).
- **Goal-pacing target line on existing chart:** 2 competitors (Polar Goals & Forecasts — daily-pro-rated target line; StoreHero Goals — month-by-month benchmarks).
- **Two-state traffic-light dot (green/red):** 1 competitor (StoreHero — explicitly "green & red", no amber).
- **Three-state traffic-light (green/yellow/red):** 1 competitor (Klaviyo churn-risk — low/medium/high).
- **Three-state action verdict pill:** 2 competitors (Wicked Reports 5 Forces AI — Scale / Chill / Kill; StoreHero Spend Advisor — pause / pivot / scale).
- **Significance pill + CI bracket + point estimate row:** 1 competitor (SegmentStream geo-test results — verbatim row format `<Channel> — <Treatment> | <Significance> · [CI low, CI high] | <point estimate>`).
- **Diamond glyph on order timeline:** 1 competitor (Klaviyo — predicted next-order date marker on profile timeline).
- **Per-channel spend slider → forecast recompute:** 2 competitors (Fospha Spend Strategist; Rockerbox budget-allocation wizard).
- **Inline month-end forecast number + daily mini-chart:** 1 competitor (Putler Pulse zone).
- **Chat-emitted forecast (text + inline chart in response):** 3 competitors (Triple Whale Moby Chat, Lebesgue Henri, Conjura Owly).
- **Weekly PDF deliverable:** 1 competitor (Lebesgue MMM — async, not interactive; gated behind ≥$5k/mo ad spend).
- **No forecasting at all:** 4 references (Shopify Native, WooCommerce Native, Peel — explicit "cannot answer forecasting/predictive queries", TrueProfit — explicit absence).

**Recurring conventions:**
- **Color rule for "predicted":** Green is the dominant "predicted/future" color (Klaviyo CLV bar green segment, Everhort baseline bars, Lifetimely CAC-payback overlay). One outlier — Lebesgue uses **blue** for positive deltas and red for negative, **not green**. No competitor uses purple for predicted.
- **Confidence is shown 4 ways:** explicit CI bracket `[low, high]` (SegmentStream), shaded CI band on a curve (Fospha), point-estimate range tiles "$X – $Y" (Fospha), named accuracy band ("Good" / "Excellent" / model confidence score) (Fospha, Lebesgue MMM).
- **Eligibility gates are universal:** Every ML-based forecast publishes (or quietly enforces) a data-volume threshold. Klaviyo: 500 customers + 180d history + 3 repeat purchasers; GA4: 1,000 positive + 1,000 negative samples / 28d; Lebesgue MMM: $5k/mo ad spend + 3 months history; Triple Whale pixel: 5–7 days for stabilization. Empty states are a real concern for new stores.
- **"Forward simulator" interaction:** input → live recompute → discrete recommendation. Pattern shared by StoreHero Spend Advisor (input: $100 spend → output: pause/pivot/scale), Putler Time Machine (5 multiplier sliders → 12-month projection), Rockerbox (constraint sliders → Generate Forecast), Fospha Spend Strategist (per-channel slider → forecast).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Predictive LTV bridges "what happened" and "what will happen"**
- "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store review (Lifetimely profile)
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Shopify App Store review (Lifetimely profile)
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group (Klaviyo profile — context: predictive CLV by RFM)

**Theme: Forecast saves planning time**
- "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, forecast for sales." — Sasha Z., Founder (Retail), Capterra, September 30, 2025 (Lebesgue profile)
- "The metrics and pacing data delivered via email save time." — Marco P., Owner (Online Media), Capterra, January 6, 2025 (Lebesgue profile)
- "Smartly's Predictive Budget Allocation helped us scale paid social with confidence. Combined with Fospha's unified measurement, it eliminated excessive time spent on budget decisions allowing us to focus more on creative strategy and growth." — Daniel Green, Head of Digital Marketing at Gymshark (Fospha profile)

**Theme: Confidence to scale ad spend**
- "I'm so happy with the platform — we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" — Jordan West (StoreHero profile, on Spend Advisor as the live-next-$100 simulator)
- "StoreHero has been a massive help growing 5X over the last year — I can scale advertising spend knowing that we're still profitable on new customer orders." — Sean Leddin, Fíor Jewellery (StoreHero profile)
- "[Got] 500% ROI from some of the traffic sources" when analyzing "lifetime value of customers over time." — Henry Reith, Marketing Consultant (Wicked Reports profile)

**Theme: Subscription / cohort re-pricing reveals winners that look like losers on day 1**
- "Continuous update of revenue and ROI when subscriptions rebill" reveals "cold traffic top of the funnel winning campaigns that look unprofitable with the delayed subscription rebills" — Wicked Reports own marketing copy (verbatim, brand page) (Wicked Reports profile)

**Theme: Glass-box methodology builds trust**
- "A one-of-a-kind attribution, optimisation and budget allocation tool." — G2 reviewer (SegmentStream profile)
- "Every time management challenged the numbers, I could open Fospha to prove what was really happening." — Rabee Sabha, Digital Marketing Manager at ARNE (Fospha profile, on glass-box accuracy metrics)

## What users hate about this feature

**Theme: Forecast accuracy / model wobble**
- "Building with the AI tool Moby is very buggy and crashes more than half the time." — Trustpilot reviewer (Triple Whale profile)
- "Occasional bugs or over-optimistic recommendations still appear in 2025–2026 operator feedback." — AI Systems Commerce, 2026 review (Triple Whale profile)
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, Atlas Engineering, workflowautomation.net, November 20, 2025 (Triple Whale profile)

**Theme: Insights too shallow / generic**
- "AI insights described as shallow — Capterra synthesis surfaces complaints like 'insights' being 'simply noting that CAC increased and conversion rate dropped off.'" — Capterra synthesis (Lebesgue profile)
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce, 2026 review (Triple Whale profile)

**Theme: Predictive features paywalled / gated by data volume**
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025 (Klaviyo profile — Marketing Analytics add-on for predictive CLV)
- Klaviyo predictive analytics gated by data thresholds (500+ customers with orders, 180+ days history, 3+ repeat purchasers) — small/new stores see empty CLV / churn cards (Klaviyo profile, structural complaint).
- "Free tier doesn't offer too much, so definitely thinking more so about a paid version." — Sasha Z., Founder (Retail), Capterra, September 30, 2025 (Lebesgue profile)

**Theme: Forecast lives in a PDF / isn't actionable in-app**
- Lebesgue MMM is delivered as a weekly PDF, not an interactive in-app surface (Lebesgue profile, structural observation). Implies "real" MMM is computationally expensive enough that even a heavily-resourced competitor can't refresh it on demand.

**Theme: Long testing windows / slow time-to-trust**
- Geo holdout = 7-week minimum (1w market selection + 2-3w A/A + 4-8w execution). SegmentStream's own blog acknowledges this is poorly suited to fast-moving brands (SegmentStream profile).
- "Despite paying over $600 each month, [we] still do not receive any customer support and have been waiting for a resolution to an issue for three months." — paraphrased G2 reviewer (Triple Whale profile, applies to forecast-related support).

## Anti-patterns observed

- **Hidden methodology / black-box forecasting:** Klaviyo and Lifetimely both ship predictive LTV without publishing the model (BTYD? linear extrapolation? ML?). Reviewers don't complain about this directly, but Everhort's KB explicitly differentiates by stating "linear regression of the blended average LTV of recent cohort performance" — i.e., publishing the model is a wedge against the black-boxes. Anti-pattern: predictive overlays without methodology documentation.

- **Forecast in PDF rather than UI:** Lebesgue MMM is a weekly PDF deliverable, not an interactive in-app screen. Users can't explore alternative budgets, can't slice by region, can't refresh on demand. Anti-pattern: relegating the most expensive computation to async email — kills "what if" exploration.

- **Vague point estimates without confidence:** Putler Time Machine renders 12-month revenue / customer-count forecasts as line/area charts with adjustable variable inputs, but no confidence interval is documented. **Anti-pattern: a single line forward implies precision the model can't deliver.** Fospha's `$6.5k between $5.5k–$7.5k` range tiles and SegmentStream's `[+22%, +49%]` brackets are the corrective.

- **AI assistant treats forecasting as out of scope:** Peel Magic Dash explicitly "cannot provide answers to forecasting or predictive queries" (FAQ verbatim). Anti-pattern: shipping an AI surface that punts on the most-asked merchant question. (Counter-pattern: Lebesgue Henri, Triple Whale Moby Chat, Conjura Owly all explicitly include forecasts in chat answers.)

- **Eligibility gate without empty state:** Klaviyo's 500-customer / 180-day / 3-repeat-purchaser threshold creates an "empty CLV card" experience for new stores. GA4 predictive audiences require 1,000 positive samples / 28d — most SMBs never qualify. **Anti-pattern: a predictive feature shipped without a credible empty-state explainer.** Northbeam's Day 30/60/90 progressive feature unlock is the opposite — it sets the expectation up front.

- **Forecast that retroactively invalidates yesterday's number:** Wicked Reports' continuous re-pricing of past-period KPIs as cohort revenue accumulates is a feature, not a bug — but it's also a gotcha. A campaign's ROAS literally changes day to day with no new spend. Without a "this is a moving number, here's why" explainer, users perceive it as data instability.

- **Two-state vs three-state traffic light inconsistency:** StoreHero Goals uses **green/red only** (no amber); Klaviyo churn-risk uses **green/yellow/red**. Inside the same dashboard family, mixing semantics is confusing. Anti-pattern: traffic-light cells with inconsistent state cardinality.

- **Forecast scenario without a "vs. status quo" baseline:** Rockerbox Generate Forecast outputs a recommended channel mix; Putler Time Machine outputs a 12-month projection. Neither always ships with the **explicit "if you do nothing" baseline overlaid** for comparison. Everhort's filter-vs-baseline dual bars is the corrective.

## Open questions / data gaps

- **Klaviyo predictive CLV model.** Documentation describes the visual (blue + green stacked bar, diamond glyph) and the eligibility gate (500 customers / 180d / 3 repeat purchasers / model retrains weekly), but the underlying model is not published. Would require Klaviyo Engineering blog dive or paid-eval to extract.
- **Lifetimely "Predictive LTV (AI)" methodology.** Marketing copy claims "12% average LTV increase" for users but no model description, no confidence interval, no eligibility gate published. Would need authenticated dashboard to capture the predictive-overlay UI.
- **Triple Whale Order/Revenue Pacing Agent UI.** Surface only described in marketing copy ("Order & Revenue Pacing Agent" is named in the Moby Agents collection); the actual chart/output it generates is not surfaced publicly. Free-tier signup needed.
- **Polar Causal Lift confidence-interval visual.** Help docs say "live experiment dashboard, showing in-flight metrics, forecasted impact, and final lift results with confidence intervals" — but the chart format (bar with whiskers? shaded line band? bracketed text?) is not in public docs.
- **Putler Time Machine confidence indicator.** Adjustable variable sliders are documented but no public source describes whether the 12-month forecast renders with a confidence band, a single line, or scenario fan-out. Probably a single line per the marketing imagery, but not verifiable without paid trial.
- **StoreHero Spend Advisor exact UI.** Marketing copy describes "Watch how every $100 you invest into ads changes profit in real time" but the public screenshots don't show the slider/pill render at pixel level.
- **Northbeam MMM+ UI.** Gated entirely behind Enterprise tier — no public screenshot, no public help doc with screenshots. The only accessible information is marketing prose.
- **Lebesgue Meta Ads Forecast UI.** Listed as a product surface but no screenshot or detailed layout description was extractable.
- **Fospha Beam scatter-with-CI exact rendering.** Confirmed via marketing imagery but pixel-level color tokens and tooltip behavior are not extractable without paid login.
- **G2 / Capterra paywalls** returned 403/404 for Polar, SegmentStream, Wicked Reports, Triple Whale during research — most negative verbatim quotes about forecast accuracy come from secondary review aggregators rather than direct platform reviews.

## Notes for Nexstage (observations only — NOT recommendations)

- **The Klaviyo CLV blue+green stacked bar is the most concrete "historic vs predicted" primitive in the category.** One bar, two color segments, single number — splits "what happened" from "what will happen" in a single horizontal pixel row. Nexstage's existing 6-source-badge thesis already separates "Real" (observed) from modeled lenses; the blue/green bar is a direct visual analog at the per-customer level. Note that it lives on the per-profile page, not in a dashboard tile.
- **Lifetimely's user-configurable green CAC-payback bar on the cohort waterfall is a small, copyable UI primitive.** A single horizontal threshold line, user-entered value, drawn over a cumulative cohort series. Concrete, single-purpose, very high "I get it" recognition value. Cheap to implement.
- **Klaviyo's diamond glyph for predicted-next-order on the customer timeline is the only "future-event marker" observed in the category.** All other timelines are backward-looking. Diamond shape distinguishes prediction from past events without needing color.
- **Confidence-interval rendering has 4 conventions in the wild — and Nexstage has no token system for it yet.** Bracket text `[low, high]`, shaded band on curve, range tile "$X–$Y", named-band label ("Good"/"Excellent"). If forecasting is shipped, picking one and standardizing it across all predictive surfaces would be simpler than mixing.
- **The "two-state vs three-state traffic light" question is a real fork.** StoreHero ships green/red only (binary on-pace/drifted). Klaviyo ships green/yellow/red (low/medium/high). Mixing inside Nexstage's dashboard would be confusing — a workspace-wide convention call is needed.
- **Lebesgue's blue-for-positive convention conflicts with Nexstage's source-color tokens.** `--color-source-google` and `--color-source-facebook` are blues; if Nexstage adopts blue for "improvement / positive delta" the way Lebesgue does, source-badge colors collide. Lebesgue chose blue likely for R/G colorblindness reasons; this is a token-collision question to resolve before forecasting ships.
- **"Forward simulator" pattern is universal but the recommendation cardinality varies.** StoreHero (3-state pause/pivot/scale), Wicked Reports (3-state Scale/Chill/Kill), Fospha (continuous spend slider, no discrete pill), Rockerbox (constraint sliders + baseline-vs-proposed comparison), Putler (5 multipliers, no recommendation pill). 3-state pill is the dominant "what should I do?" output.
- **Eligibility-gate empty-state is universally weak.** Every predictive feature has a data-volume threshold, but only Northbeam (Day 30/60/90 progressive unlock) treats the wait period as a first-class UX moment. Klaviyo and GA4 just hide the cards. For Nexstage's onboarding, "your forecast unlocks at X data points" is missing infra in nearly every competitor.
- **Forecasting in-chat (Triple Whale Moby, Lebesgue Henri, Conjura Owly) vs. dedicated forecast surface (Putler Time Machine, Lifetimely Predictive LTV, Fospha Beam) is a category-level fork.** AI-chat surfaces the forecast as a one-off answer; dashboard surfaces let the user "live in" the forecast. Both patterns coexist; the chat pattern often blocks deep configuration while the dashboard pattern is harder to build.
- **Wicked Reports' continuous re-pricing logic is structurally incompatible with snapshot-style pre-aggregation.** Their patents-pending claim depends on retroactively re-pricing past periods as new cohort data lands. Nexstage's `daily_snapshots` / `hourly_snapshots` / `daily_snapshot_products` model writes immutable rolled-up rows — would need either a recompute-on-cohort-arrival model (similar to `RecomputeAttributionJob` for cost-config changes) or a runtime-overlay lens that joins live cohort revenue to historical snapshot rows. Worth a decision before implementing forecast surfaces.
- **GSC absence in every forecasting surface.** 0 of 23 competitors with forecasting include GSC clicks/impressions as a forecast input. Nexstage's 6-source thesis (Real, Store, Facebook, Google, GSC, GA4) creates a structural opening — "predicted GSC traffic" is uncharted whitespace. (Also: SEO has its own seasonality patterns that paid-channel forecasts don't capture.)
- **Cash-flow forecasting is barely addressed.** Stripe Sigma references "monthly charge volume / cash flow" as a template; no other competitor specifically builds a cash-runway / cash-balance forecast. The phrasing in the Nexstage user question — "what will cash look like next month" — has no clean competitor analog. Most competitors forecast revenue, LTV, and ROAS; cash projections (revenue minus COGS minus fixed costs minus AP, projected forward) are an open space. Closest analog is StoreHero's contribution-margin forecast (which is closer to gross profit than to cash).
- **Subscription rebill re-pricing is Wicked Reports' moat.** No SMB competitor handles "ROAS for a campaign changes day-to-day even with no new spend, as the cohort matures." Either Nexstage builds this, or accepts it's a vertical-specific feature (subscription brands disproportionately need it).
- **Holiday-season forecast adjustment is rare.** Only Putler explicitly ships a Holiday Season tracking module (Halloween, Thanksgiving, Black Friday, Cyber Monday, Christmas with YoY comparison). StoreHero auto-applies "seasonally-adjusted" benchmarks but doesn't expose the adjustment to the user. Most other competitors don't adjust at all, leaving the user to mentally subtract Q4 from any forward extrapolation.
