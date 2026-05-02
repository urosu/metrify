# Lifetimely (by AMP) -- Competitor Research (May 2026)

Sources: useamp.com, apps.shopify.com/lifetimely, help.lifetimely.io, attnagency.com review, storecensus.com, mcpanalytics.ai comparison.

---

## 1. P&L / Income Statement

Lifetimely's Income Statement is their flagship. Breakdown:

- **Revenue:** Gross Sales -> Discounts -> Returns/Refunds -> Net Sales
- **Cost layers:** COGS (auto-pulled from Shopify + manual + CSV import), Shipping (auto from ShipStation/ShipBob/Addition), Transaction Fees, Ad Spend (auto from Meta/Google/TikTok/Snapchat/Pinterest)
- **Operating Expenses:** Handling costs, personnel, agency fees, custom one-time or recurring costs
- **QuickBooks Online integration:** OAuth connect, map QBO expense categories to Lifetimely P&L lines. Auto-syncs after mapping.
- **Output:** Real-time P&L dashboard. No explicit CM1/CM2/CM3 layering -- it's a flat income statement, not a contribution margin waterfall.

**Nexstage comparison:** Our F2 P&L is more structured (CM1 -> CM2 -> CM3 -> Net Profit waterfall). Lifetimely's QBO integration is a differentiator we don't have yet.

---

## 2. LTV / Cohort Analysis

- **Cohort heatmap:** Acquisition month x months-since-purchase. Revenue, orders, profit per cohort.
- **Segmentation filters:** First purchase date, first product purchased, marketing channel, geography.
- **Predictive LTV:** AI-driven forecasting at 3, 6, 12, and 24-month windows. Proprietary models trained on store's order history.
- **LTV by segment:** Per-channel, per-product, per-country LTV curves.
- **Claim:** Customers increase LTV by avg 12% using the tool.

**Nexstage comparison:** Our F8 covers cohort heatmap + LTV + retention + CAC payback. We don't have predictive LTV (that's a v2 consideration). Their segmentation filters match ours.

---

## 3. Customer Segmentation

- **RFM segmentation:** Pre-built RFM categories. Filter by country, channel, product.
- **No unique approach:** Standard cohort-based segmentation with built-in filters. No custom segment builder or audience push (Klaviyo push not confirmed).
- **Dashboard customization:** Drag-and-drop KPI dashboard builder. Choose specific KPIs, share via automated email reports.

**Nexstage comparison:** Our F9 RFM is comparable. We add Klaviyo/Meta push which they seem to lack.

---

## 4. Dashboards

- **Customizable:** Drag-and-drop interface. Pick KPIs that matter to your team.
- **Automated email reports:** Schedule dashboard delivery.
- **Not modular blocks:** It's KPI card selection, not a full BI-style block builder like Polar.
- **Product reporting:** Track new customers and product journeys by LTV.

---

## 5. Pricing (2026)

| Plan | Price | Orders/mo | Key extras |
|------|-------|-----------|------------|
| Free | $0 | 50 | Basic features |
| M | $149/mo | 3,000 | All features, app onboarding, live chat, AMP AI |
| L | $299/mo | 7,000 | + 1:1 onboarding, custom reports by their team, dedicated Slack |
| XL | $499/mo | 15,000 | + dedicated AM, monthly profit reporting sessions |

Amazon add-on: +$75/mo on any plan. 14-day free trial on all paid plans.

---

## 6. What Lifetimely Does NOT Have

- No server-side pixel / attribution pixel
- No multi-touch attribution (relies on platform-reported data)
- No incrementality testing
- No custom report builder (BI-style)
- No WooCommerce / non-Shopify support
- No Snowflake / data warehouse access
- No AI chat assistant (they have "AMP AI recommendations" but not conversational)
- No creative analysis / ad-level performance
