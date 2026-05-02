# Nexstage Database Schema

PostgreSQL 18. All tables use `workspace_id` scoping EXCEPT `fx_rates`, `holidays`, and `global_settings` (which are global). Ratios (ROAS, MER, CAC, AOV, CVR) are NEVER stored — always computed at query time from component columns.

---

## Table Groups

1. **Core** — workspaces, organizations, users, roles
2. **Integrations** — store connections, ad accounts, analytics properties
3. **Store Data** — orders, line items, refunds, products, variants, customers
4. **Ad Data** — campaigns, ad sets, ads, creatives, daily ad insights
5. **Analytics Data** — GA4 daily, GSC daily, page speed
6. **Costs** — COGS entries, shipping rules, operational costs, transaction fees
7. **Aggregates** — daily snapshots (the core performance table)
8. **Features** — alerts, annotations, saved views, segments, holidays
9. **System** — sync logs, job tracking, global settings

---

## 1. Core Tables
### organizations
For agencies with consolidated billing. Optional — solo merchants just have workspaces.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| billing_email | varchar(255) | |
| stripe_customer_id | varchar(255) | nullable |
| plan | varchar(50) | v2 (agency billing) — leave nullable at MVP |
| trial_ends_at | timestamp | nullable — v2 (agency billing) |
| created_at | timestamp | |
| updated_at | timestamp | |

### workspaces
One per brand. Central scoping entity.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | bigint FK | nullable (solo merchants have no org) |
| name | varchar(255) | Brand name |
| slug | varchar(100) | URL-safe identifier |
| reporting_currency | char(3) | ISO 4217 (EUR, USD, etc.) |
| reporting_timezone | varchar(50) | e.g. Europe/Ljubljana |
| default_cogs_margin_pct | decimal(5,2) | nullable — fallback % (e.g. 30.00) |
| vat_included_in_prices | boolean | default false |
| attribution_model | varchar(30) | last_click, first_click, linear (position_based: v2) |
| attribution_window_days | smallint | 7, 14, 30 |
| brand_keywords | jsonb | nullable — `["nexstage","nex stage"]` for SEO brand/non-brand split |
| naming_delimiter | char(3) | `\|`, `_`, `-` for ad name parsing |
| naming_dimensions | jsonb | `["country","funnel","audience","creative_type"]` |
| target_roas | decimal(6,2) | nullable |
| target_cac | decimal(10,2) | nullable |
| target_revenue | decimal(14,2) | nullable — monthly revenue target for pacing bar |
| onboarding_checklist | jsonb | nullable — `{"store":true,"facebook":false,"google":false,"ga4":false,"gsc":false,"klaviyo":false,"cogs":false}` |
| plan | varchar(50) | trialing, active, past_due, canceled — single flat rate (€39/mo or 0.4% of revenue) |
| stripe_customer_id | varchar(255) | nullable |
| stripe_status | varchar(20) | nullable — trialing, active, past_due, canceled |
| trial_ends_at | timestamp | nullable |
| syncs_paused_at | timestamp | nullable — set when subscription expires/cancels; checked by sync scheduler |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

**Index:** `(organization_id)`, `(slug)` unique

### workspace_slug_history
Preserves old slugs for 301 redirects when workspace name changes.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| old_slug | varchar(100) | unique |
| created_at | timestamp | |

**Index:** `(old_slug)` unique

### users

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) | unique |
| password | varchar(255) | |
| is_superadmin | boolean | default false |
| email_verified_at | timestamp | nullable |
| remember_token | varchar(100) | |
| created_at | timestamp | |
| updated_at | timestamp | |

### workspace_users (pivot)

| Column | Type | Notes |
|--------|------|-------|
| workspace_id | bigint FK | |
| user_id | bigint FK | |
| role | varchar(20) | owner, admin, member |
| can_access_financials | boolean | default true — COGS, profit, margins, cost columns |
| can_access_pii | boolean | default true — customer emails/names, order PII |
| can_access_settings | boolean | default false — integrations, workspace config, cost config |
| can_manage_members | boolean | default false — invite/remove users, change roles |
| created_at | timestamp | |

**PK:** `(workspace_id, user_id)`

Capability flags are enforced for `member` role only. Owner/admin always have full access (flags ignored). Admin configures these per member via Settings > Team. Nav items for restricted sections are hidden (not locked).

---

## 2. Integration Tables
### stores
Connected Shopify/WooCommerce stores.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| platform | varchar(20) | shopify, woocommerce |
| name | varchar(255) | Store display name |
| domain | varchar(255) | store-a.com |
| platform_store_id | varchar(100) | Shopify shop ID or WC site URL |
| access_token | text | encrypted |
| refresh_token | text | encrypted, nullable — for Shopify expiring offline tokens |
| token_expires_at | timestamp | nullable — for proactive token refresh (Shopify requires since Apr 2025) |
| api_version | varchar(20) | e.g. "2026-04" for Shopify, "v3" for WooCommerce |
| currency | char(3) | Store's native currency |
| vat_included_in_prices | boolean | per-store override |
| shipping_flat_rate | decimal(10,2) | nullable — fallback shipping cost |
| transaction_fee_pct | decimal(5,2) | nullable — fallback fee % |
| transaction_fee_fixed | decimal(10,2) | nullable — fallback fixed fee |
| sync_status | varchar(20) | active, paused, error, disconnected |
| last_synced_at | timestamp | nullable |
| last_order_synced_at | timestamp | nullable |
| initial_import_completed | boolean | default false |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`, `(workspace_id, platform, platform_store_id)` unique

### ad_accounts

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| platform | varchar(20) | meta, google, tiktok |
| platform_account_id | varchar(100) | |
| name | varchar(255) | |
| access_token | text | encrypted |
| refresh_token | text | encrypted, nullable |
| token_expires_at | timestamp | nullable — for proactive token refresh |
| currency | char(3) | |
| sync_status | varchar(20) | |
| last_synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`, `(workspace_id, platform, platform_account_id)` unique

### analytics_properties
GA4 properties.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | nullable — link to which store this belongs to |
| platform | varchar(10) | ga4 |
| property_id | varchar(50) | GA4 property ID |
| name | varchar(255) | |
| access_token | text | encrypted |
| refresh_token | text | encrypted |
| token_expires_at | timestamp | nullable — Google OAuth tokens expire ~1h |
| sync_status | varchar(20) | |
| last_synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

### search_properties
Google Search Console.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | nullable |
| site_url | varchar(255) | GSC property URL |
| access_token | text | encrypted |
| refresh_token | text | encrypted |
| token_expires_at | timestamp | nullable — Google OAuth tokens expire ~1h |
| sync_status | varchar(20) | |
| last_synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

### email_accounts
Klaviyo (Omnisend: v2).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| platform | varchar(20) | klaviyo, omnisend |
| access_token | text | encrypted — OAuth access token (Klaviyo uses OAuth, not API keys) |
| refresh_token | text | encrypted, nullable |
| token_expires_at | timestamp | nullable |
| api_key | text | encrypted, nullable — legacy fallback for platforms that use API keys |
| name | varchar(255) | |
| sync_status | varchar(20) | |
| last_synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

---

## 3. Store Data Tables
### orders
Normalized from both Shopify and WooCommerce.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | which store this came from |
| customer_id | bigint FK | nullable (our internal customer ID) |
| platform_order_id | varchar(50) | Shopify/WC order ID |
| order_number | varchar(50) | Display number (#1001) |
| created_at | timestamp | order placement time |
| financial_status | varchar(30) | authorized, cancelled, expired, paid, partially_paid, partially_refunded, pending, refunded, voided |
| fulfillment_status | varchar(30) | fulfilled, partial, unfulfilled |
| currency | char(3) | original order currency |
| total_price | decimal(12,2) | grand total |
| subtotal_price | decimal(12,2) | line items after discounts |
| total_discounts | decimal(12,2) | |
| total_tax | decimal(12,2) | |
| total_shipping | decimal(12,2) | what customer paid for shipping |
| actual_shipping_cost | decimal(12,2) | nullable — actual carrier cost |
| payment_gateway | varchar(50) | shopify_payments, stripe, paypal |
| transaction_fee | decimal(10,2) | nullable — actual fee (auto-pulled preferred) |
| transaction_fee_estimated | boolean | default false — true if formula, not actual |
| cogs_total | decimal(12,2) | nullable — sum of line item COGS at order time |
| cogs_estimated | boolean | default false — true if using default margin |
| handling_cost | decimal(10,2) | nullable |
| source_name | varchar(50) | web, pos, api |
| referring_site | varchar(500) | |
| landing_site | varchar(500) | first landing URL |
| landing_page_path | varchar(500) | nullable — normalized path (stripped protocol+host+queryparams), computed at sync. Indexed for GSC revenue join. |
| utm_source | varchar(100) | |
| utm_medium | varchar(100) | |
| utm_campaign | varchar(255) | |
| utm_content | varchar(255) | |
| utm_term | varchar(255) | |
| channel | varchar(50) | resolved channel: paid_social, organic, email, direct |
| is_new_customer | boolean | computed: customer's first order? |
| is_cod | boolean | default false — Cash on Delivery, detected from payment_gateway during sync |
| discount_codes | jsonb | `[{"code":"SUMMER20","amount":10.00,"type":"percentage"}]` |
| billing_country | char(2) | ISO country code |
| shipping_country | char(2) | |
| shipping_city | varchar(100) | |
| refund_total | decimal(12,2) | default 0 — updated when refunds sync |
| net_revenue | decimal(12,2) | computed: total - discounts - refunds |
| total_price_converted | decimal(12,2) | nullable — total in workspace reporting currency |
| net_revenue_converted | decimal(12,2) | nullable — net revenue in workspace reporting currency |
| contribution_margin | decimal(12,2) | nullable — computed: net_revenue - all costs (derived, rebuilt on COGS change) |
| matched_campaign_id | bigint FK | nullable — populated by background job matching UTM → campaign |
| touchpoints | jsonb | nullable — customer journey moments from Shopify `customerJourneySummary.moments` or WC UTM as single entry. `[{"source":"facebook","medium":"paid","campaign":"summer_tof","landing_page":"/products/x","occurred_at":"..."}]` |
| synced_at | timestamp | when we last synced this order |
| platform_updated_at | timestamp | when order was last updated on platform |
| updated_at | timestamp | |
| deleted_at | timestamp | nullable — soft delete (set via `orders/delete` webhook from Shopify, `order.deleted` from WC) ||

**Indexes:**
- `(workspace_id, created_at)` — primary dashboard query
- `(workspace_id, customer_id)` — LTV/cohort queries
- `(workspace_id, store_id, created_at)` — per-store filtering
- `(workspace_id, channel)` — channel breakdown
- `(workspace_id, shipping_country)` — country analysis
- `(workspace_id, landing_page_path)` — SEO revenue-per-query join
- `(platform_order_id, store_id)` unique — dedup

**Partitioning:** Hash by `workspace_id` at scale (64 partitions).

### order_line_items

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| order_id | bigint FK | |
| workspace_id | bigint FK | denormalized for query performance |
| product_id | bigint FK | our internal product ID |
| variant_id | bigint FK | nullable — our internal variant ID |
| platform_product_id | varchar(50) | |
| platform_variant_id | varchar(50) | |
| title | varchar(255) | |
| sku | varchar(100) | |
| quantity | int | |
| unit_price | decimal(10,2) | per-unit price before discounts |
| total_discount | decimal(10,2) | line-level discount |
| total_price | decimal(12,2) | quantity * price - discounts |
| unit_cogs | decimal(10,2) | nullable — COGS per unit at order time |
| cogs_source | varchar(20) | explicit, store_default, workspace_default, none |
| total_tax | decimal(10,2) | |

**Index:** `(order_id)`, `(workspace_id, product_id)`

### refunds

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| order_id | bigint FK | |
| workspace_id | bigint FK | |
| platform_refund_id | varchar(50) | |
| amount | decimal(12,2) | total refund amount |
| reason | varchar(255) | nullable — sizing, damaged, not_as_described, other |
| return_shipping_cost | decimal(10,2) | nullable — cost of return shipping |
| restocking_fee | decimal(10,2) | nullable |
| created_at | timestamp | when refund was issued |
| line_items | jsonb | `[{"line_item_id":1,"quantity":1,"amount":25.00}]` |

**Index:** `(order_id)`, `(workspace_id, created_at)`

### products
Normalized from both platforms.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | |
| platform_product_id | varchar(50) | |
| title | varchar(255) | |
| handle | varchar(150) | URL slug — Shopify `handle`, WooCommerce `slug`. Used for GSC page→product matching. |
| vendor | varchar(255) | |
| product_type | varchar(255) | |
| tags | jsonb | `["summer","bestseller"]` |
| status | varchar(20) | active, draft, archived |
| image_url | varchar(500) | primary image |
| has_variants | boolean | |
| notes | text | nullable — user-added notes for inventory/internal use |
| created_at | timestamp | |
| updated_at | timestamp | |
| synced_at | timestamp | |

**Index:** `(workspace_id, store_id)`, `(platform_product_id, store_id)` unique

### product_variants

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| product_id | bigint FK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | which store this variant belongs to |
| platform_variant_id | varchar(50) | |
| title | varchar(255) | e.g. "Large / Red" |
| sku | varchar(100) | |
| price | decimal(10,2) | |
| compare_at_price | decimal(10,2) | nullable — original price before sale |
| inventory_quantity | int | current stock level |
| weight | decimal(10,3) | nullable |
| weight_unit | varchar(5) | kg, lb, g, oz |
| barcode | varchar(50) | nullable |
| image_url | varchar(500) | nullable |
| notes | text | nullable — user-added notes |
| synced_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(product_id)`, `(workspace_id, store_id, sku)` — store-scoped to prevent SKU collision in multi-store

### customers
Normalized, deduped by email within workspace.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | which store they came from (first seen) |
| platform_customer_id | varchar(50) | nullable — 0/null for WC guests |
| email | varchar(255) | primary dedup key |
| first_name | varchar(100) | |
| last_name | varchar(100) | |
| country | char(2) | from billing/shipping address |
| city | varchar(100) | |
| first_order_at | timestamp | nullable — computed from orders |
| last_order_at | timestamp | nullable — computed from orders |
| orders_count | int | default 0 — computed from our orders table (Shopify removed these fields in API 2025-01) |
| total_spent | decimal(12,2) | default 0 — computed from our orders table (not from Shopify API) |
| total_refunded | decimal(12,2) | default 0 |
| total_contribution_margin | decimal(12,2) | default 0 — denormalized, updated on order sync |
| accepts_marketing | boolean | nullable — deprecated in Shopify, use email_marketing_consent object. Store as boolean for simplicity. |
| tags | jsonb | nullable |
| rfm_recency_score | smallint | nullable — 1-5 |
| rfm_frequency_score | smallint | nullable — 1-5 |
| rfm_monetary_score | smallint | nullable — 1-5 |
| rfm_segment | varchar(30) | nullable — champions, at_risk, hibernating, etc. |
| avg_days_between_orders | decimal(8,1) | nullable — for churn prediction |
| predicted_next_order_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, email)` unique, `(workspace_id, first_order_at)` for cohorts, `(workspace_id, rfm_segment)`

---

## 4. Ad Data Tables
### ad_campaigns

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| ad_account_id | bigint FK | |
| platform | varchar(20) | meta, google, tiktok |
| platform_campaign_id | varchar(50) | |
| name | varchar(500) | |
| status | varchar(20) | active, paused, archived |
| objective | varchar(50) | conversions, awareness, traffic |
| campaign_type | varchar(30) | nullable — search, shopping, pmax, video (Google) |
| daily_budget | decimal(10,2) | nullable |
| lifetime_budget | decimal(10,2) | nullable |
| parsed_dimensions | jsonb | nullable — `{"country":"SI","funnel":"TOF","audience":"broad"}` |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, ad_account_id)`, `(platform_campaign_id, ad_account_id)` unique

### ad_sets

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| campaign_id | bigint FK | our internal campaign ID |
| platform_adset_id | varchar(50) | |
| name | varchar(500) | |
| status | varchar(20) | |
| optimization_goal | varchar(50) | nullable |
| targeting | jsonb | nullable — platform targeting config |
| parsed_dimensions | jsonb | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(campaign_id)`, `(workspace_id)`
**Unique:** `(platform_adset_id, campaign_id)` — for sync upserts

### ads

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| ad_set_id | bigint FK | |
| campaign_id | bigint FK | denormalized |
| platform_ad_id | varchar(50) | |
| name | varchar(500) | |
| status | varchar(20) | |
| creative_thumbnail_url | varchar(500) | nullable |
| creative_image_url | varchar(500) | nullable |
| creative_video_id | varchar(100) | nullable |
| creative_body | text | nullable — ad copy |
| creative_title | varchar(500) | nullable |
| creative_link_url | varchar(500) | nullable — landing page |
| parsed_dimensions | jsonb | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(ad_set_id)`, `(workspace_id, campaign_id)`
**Unique:** `(platform_ad_id, ad_set_id)` — for sync upserts

### ad_insights
Daily performance metrics per ad. This is the big table.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| ad_account_id | bigint FK | |
| campaign_id | bigint FK | |
| ad_set_id | bigint FK | nullable |
| ad_id | bigint FK | nullable — campaign-level rows have null |
| platform | varchar(20) | |
| level | varchar(10) | campaign, adset, ad |
| date | date | |
| spend | decimal(12,2) | in ad account currency |
| spend_workspace_currency | decimal(12,2) | converted at day rate |
| impressions | int | |
| clicks | int | |
| reach | int | nullable |
| frequency | decimal(6,2) | nullable |
| purchases | int | platform-reported conversions |
| purchase_value | decimal(12,2) | platform-reported revenue |
| add_to_cart | int | nullable |
| video_views_p25 | int | nullable |
| video_views_p50 | int | nullable |
| video_views_p75 | int | nullable |
| video_views_p100 | int | nullable |
| product_item_id | varchar(100) | nullable — Google Shopping SKU |
| extra_metrics | jsonb | nullable — platform-specific overflow |
| synced_at | timestamp | |
| updated_at | timestamp | |

**Primary key pattern:** `(workspace_id, ad_id, date)` is the logical key — but ad_id can be null for campaign-level rows, so use the bigint PK and a unique constraint.

**Unique:** `(workspace_id, platform, level, COALESCE(ad_id, ad_set_id, campaign_id), date)` — prevents duplicates. Level is required because same entity has separate campaign/adset/ad level rows.

**Indexes:**
- `(workspace_id, date)` — dashboard date range queries
- `(workspace_id, campaign_id, date)` — campaign drill-down
- `(workspace_id, ad_account_id, date)` — per-account totals

**Partitioning:** Hash by `workspace_id` at scale.

---

## 5. Analytics Data Tables
### ga4_daily
Daily aggregates from GA4.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| analytics_property_id | bigint FK | |
| date | date | |
| source | varchar(100) | sessionSource |
| medium | varchar(100) | sessionMedium |
| campaign | varchar(255) | nullable |
| landing_page | varchar(500) | |
| country | char(2) | nullable |
| device_category | varchar(20) | mobile, desktop, tablet |
| sessions | int | |
| engaged_sessions | int | |
| total_users | int | |
| new_users | int | |
| item_views | int | nullable — GA4 `itemViews` (product page views) |
| add_to_carts | int | nullable — GA4 `addToCarts` |
| checkouts_started | int | nullable — GA4 `checkouts` |
| purchases | int | ecommercePurchases |
| purchase_revenue | decimal(12,2) | |
| bounce_rate | decimal(5,4) | nullable |
| synced_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, date)`, `(workspace_id, landing_page, date)`
**Unique:** `(workspace_id, analytics_property_id, date, source, medium, COALESCE(landing_page, '__none__'), COALESCE(country, '__none__'), device_category)` — COALESCE prevents NULL duplicates

### gsc_daily
Daily aggregates from Google Search Console.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| search_property_id | bigint FK | |
| date | date | |
| query | varchar(500) | |
| page | varchar(500) | full URL from GSC API |
| page_path | varchar(500) | normalized path (host+params stripped at sync time) — used for revenue join |
| country | char(2) | |
| device | varchar(20) | mobile, desktop, tablet |
| clicks | int | |
| impressions | int | |
| position | decimal(5,1) | average position |
| synced_at | timestamp | |

**Index:** `(workspace_id, date)`, `(workspace_id, query, date)`, `(workspace_id, page_path, date)`
**Unique:** `(workspace_id, search_property_id, date, query, page, country, device)`

### page_speeds
Lighthouse/CrUX scores per URL.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | |
| url | varchar(500) | |
| strategy | varchar(10) | mobile, desktop |
| source | varchar(10) | crux, lighthouse |
| performance_score | smallint | 0-100 |
| lcp_ms | int | Largest Contentful Paint |
| inp_ms | int | Interaction to Next Paint |
| cls | decimal(5,3) | Cumulative Layout Shift |
| fcp_ms | int | nullable |
| ttfb_ms | int | nullable |
| measured_at | timestamp | |

**Index:** `(workspace_id, url, strategy)`, `(workspace_id, measured_at)`

### uptime_checks (v2 — create migration but do not schedule jobs at MVP)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | |
| status | varchar(10) | up, down |
| response_time_ms | int | |
| status_code | smallint | nullable |
| checked_at | timestamp | |

**Index:** `(workspace_id, checked_at)`

---

## 6. Cost Tables
### cogs_entries
Per-product COGS with date history.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| product_id | bigint FK | nullable — null = applies to variant directly |
| variant_id | bigint FK | nullable — null = applies to all variants of product |
| cost | decimal(10,2) | per-unit cost, ALWAYS stored in workspace reporting currency (converted at upload/sync time) |
| currency | char(3) | original input currency (audit trail only — `cost` is already converted) |
| source | varchar(20) | manual, csv, shopify, woocommerce, supplier |
| effective_from | date | when this cost starts applying |
| effective_to | date | nullable — null = current/ongoing |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, product_id, variant_id, effective_from)`

### shipping_rules
Per-country or per-product shipping cost rules.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | nullable — null = workspace-wide |
| country | char(2) | nullable — null = default |
| product_id | bigint FK | nullable — null = all products |
| cost_per_order | decimal(10,2) | nullable |
| cost_per_item | decimal(10,2) | nullable |
| additional_item_cost | decimal(10,2) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, store_id, country)`

### operational_costs
Recurring and one-time business costs.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| name | varchar(255) | e.g. "Office rent", "Klaviyo", "Photography" |
| category | varchar(50) | saas, rent, salary, marketing, other |
| amount | decimal(12,2) | |
| currency | char(3) | |
| frequency | varchar(20) | monthly, weekly, daily, one_time |
| starts_at | date | |
| ends_at | date | nullable — null = ongoing |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

### platform_fee_rules
Platform/marketplace fees (e.g., Shopify plan fees, payment plan percentages).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | nullable |
| name | varchar(100) | e.g., "Shopify Basic plan fee" |
| fee_type | varchar(20) | percentage, flat_monthly |
| percentage | decimal(5,2) | nullable — e.g., 2.0 for 2% |
| flat_amount | decimal(10,2) | nullable — monthly flat fee |
| currency | char(3) | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, store_id)`

### fx_rates
Currency exchange rates for multi-currency conversion.

| Column | Type | Notes |
|--------|------|-------|
| base_currency | char(3) | |
| target_currency | char(3) | |
| date | date | |
| rate | decimal(16,8) | mid-market rate |

**PK:** `(base_currency, target_currency, date)`

---

## 7. Aggregate Tables
### daily_snapshots
The core performance table. One row per workspace per store per day. Dashboards query THIS, not raw tables. **All amounts in workspace reporting currency** (converted during snapshot build using `fx_rates`).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| store_id | bigint FK | nullable — null = aggregated across all stores |
| date | date | |
| revenue | decimal(14,2) | total_price sum |
| net_revenue | decimal(14,2) | revenue - discounts - refunds |
| orders_count | int | |
| units_sold | int | |
| total_discounts | decimal(14,2) | |
| total_refunds | decimal(14,2) | |
| refund_count | int | |
| cogs_total | decimal(14,2) | |
| cogs_estimated_pct | decimal(5,2) | % of revenue from estimated COGS products |
| shipping_cost | decimal(14,2) | actual carrier costs |
| shipping_revenue | decimal(14,2) | what customers paid for shipping |
| payment_fees | decimal(14,2) | |
| payment_fees_estimated_pct | decimal(5,2) | % from formula vs actual |
| handling_costs | decimal(14,2) | |
| taxes_collected | decimal(14,2) | sum of order taxes |
| return_shipping_costs | decimal(14,2) | from refunds.return_shipping_cost |
| restocking_fees | decimal(14,2) | from refunds.restocking_fee |
| ad_spend_meta | decimal(14,2) | |
| ad_spend_google | decimal(14,2) | |
| ad_spend_tiktok | decimal(14,2) | |
| ad_spend_other | decimal(14,2) | pinterest, snap, etc. |
| ad_spend_total | decimal(14,2) | sum of all platforms |
| new_customers | int | first-time buyers |
| new_customer_revenue | decimal(14,2) | revenue from first-time buyers only (for ncROAS) |
| returning_customers | int | repeat buyers |
| sessions | int | from GA4 |
| organic_clicks | int | from GSC |
| organic_impressions | int | from GSC |
| email_revenue | decimal(14,2) | from Klaviyo |
| email_sends | int | |
| email_opens | int | |
| email_clicks | int | |
| built_at | timestamp | when this snapshot was computed |

**Indexes:**
- `(workspace_id, date)` — primary query path
- `(workspace_id, store_id, date)` — per-store queries

**Unique:** `(workspace_id, COALESCE(store_id, 0), date)` — one row per combo (COALESCE prevents NULL duplicates for workspace-level rows)

### daily_snapshot_cohorts
Pre-computed cohort data for the LTV heatmap. Built nightly by `BuildCohortSnapshotJob`.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| cohort_period | date | DATE_TRUNC('month', first_order_at) |
| period_offset | int | calendar months since cohort_period |
| customers_active | int | distinct customers with orders in this offset month |
| revenue | decimal(14,2) | total revenue from this cohort in this offset month |
| orders_count | int | |
| built_at | timestamp | |

**Unique:** `(workspace_id, cohort_period, period_offset)`
**Index:** `(workspace_id)`

All ratios computed at query time — see `coding-spec.md` sections 1-2 for formulas.

---

## 8. Feature Tables
### alerts

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| type | varchar(30) | metric_anomaly, integration_error, low_stock, speed_drop, rfm_migration, source_disagreement |
| severity | varchar(10) | critical, warning, info |
| title | varchar(255) | "Revenue dropped 15%" |
| body | text | nullable — detail description |
| metric_name | varchar(50) | nullable — which metric triggered |
| metric_value | decimal(14,2) | nullable |
| metric_threshold | decimal(14,2) | nullable |
| deep_link | varchar(500) | nullable — URL to relevant page with filters |
| acknowledged_at | timestamp | nullable |
| snoozed_until | timestamp | nullable |
| created_at | timestamp | |

**Index:** `(workspace_id, created_at)`, `(workspace_id, acknowledged_at)` partial (WHERE acknowledged_at IS NULL)

### alert_rules
User-defined alert configuration.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| metric | varchar(50) | revenue, orders, ad_spend, roas, cac, cvr, stock_days, speed_score |
| scope | varchar(20) | workspace, store, channel, campaign |
| scope_id | bigint | nullable — specific store/campaign ID |
| condition | varchar(10) | above, below, change_pct |
| threshold | decimal(14,2) | |
| severity | varchar(10) | critical, warning, info |
| channel | varchar(20) | in_app, email (slack: v2) |
| enabled | boolean | default true |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, enabled)`

### annotations
User event markers on charts.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| user_id | bigint FK | who created it |
| date | date | which day the annotation marks |
| title | varchar(255) | "Summer sale started" |
| body | text | nullable |
| category | varchar(30) | sale, campaign, price_change, mailing, other |
| color | varchar(20) | nullable — for chart marker color |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, date)`

### saved_views

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| user_id | bigint FK | |
| page | varchar(50) | orders, products, campaigns, customers |
| name | varchar(100) | "Unprofitable orders" |
| filters | jsonb | `{"contribution_margin_lt":0}` |
| columns | jsonb | nullable — column visibility/order |
| sort | jsonb | nullable — `{"column":"margin","direction":"asc"}` |
| is_pinned | boolean | default false — shows in sidebar |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id, page)`

### customer_segments

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| name | varchar(100) | "High-value EU customers" |
| rules | jsonb | `{"total_spent_gte":500,"country_in":["DE","AT","CH"]}` |
| customer_count | int | cached count, refreshed daily |
| avg_ltv | decimal(12,2) | cached, refreshed daily |
| sync_destination | varchar(20) | nullable — klaviyo, meta |
| sync_destination_id | varchar(100) | nullable — Klaviyo list ID or Meta audience ID |
| last_synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

### customer_segment_members (pivot)

| Column | Type | Notes |
|--------|------|-------|
| segment_id | bigint FK | |
| customer_id | bigint FK | |

**PK:** `(segment_id, customer_id)`

### holidays

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| date | date | |
| name | varchar(255) | "Black Friday" |
| type | varchar(20) | ecommerce, public, religious |
| country | char(2) | nullable — null = global |
| is_ecommerce_event | boolean | BF, CM, Prime Day, Singles Day |

**Index:** `(date)`, `(country, date)`

### workspace_holidays
Which holidays a workspace watches.

| Column | Type | Notes |
|--------|------|-------|
| workspace_id | bigint FK | |
| holiday_id | bigint FK | |
| notify | boolean | default true |

**PK:** `(workspace_id, holiday_id)`

### channel_mappings
UTM-to-channel mapping rules.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | nullable — null = global default |
| priority | smallint | lower = checked first |
| utm_source | varchar(100) | nullable — matches if set |
| utm_medium | varchar(100) | nullable |
| utm_campaign_pattern | varchar(255) | nullable — regex or LIKE pattern |
| referring_site_pattern | varchar(255) | nullable |
| channel | varchar(50) | paid_search, paid_social, paid_video, paid_shopping, cross_network, display, email, sms, affiliate, mobile_push, organic_search, organic_social, organic_video, organic_shopping, referral, direct, unassigned |
| created_at | timestamp | |

**Index:** `(workspace_id, priority)`

---

### workspace_invitations

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| email | varchar(255) | invited email |
| role | varchar(20) | admin, member |
| token | varchar(64) | unique, for accept URL |
| accepted_at | timestamp | nullable |
| expires_at | timestamp | 7 days from creation |
| created_at | timestamp | |

**Index:** `(token)` unique, `(workspace_id)`

### shared_links

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| user_id | bigint FK | who created it |
| token | varchar(64) | unique, URL-safe |
| page | varchar(50) | dashboard, profit, marketing, etc. |
| filters | jsonb | nullable — frozen filter state |
| date_range | jsonb | frozen date range |
| is_live | boolean | default false — live = updates, frozen = snapshot |
| expires_at | timestamp | nullable |
| created_at | timestamp | |

**Index:** `(token)` unique

---

### utm_templates
Saved UTM link builder templates (T2).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| name | varchar(100) | "Facebook TOF template" |
| source | varchar(100) | |
| medium | varchar(100) | |
| campaign_pattern | varchar(255) | nullable — with macro placeholders |
| content_pattern | varchar(255) | nullable |
| term_pattern | varchar(255) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(workspace_id)`

### exports
Track async export jobs for polling.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| user_id | bigint FK | |
| page | varchar(50) | orders, products, campaigns |
| filters | jsonb | nullable |
| columns | jsonb | |
| status | varchar(20) | queued, processing, completed, failed |
| file_path | varchar(500) | nullable — S3 path |
| download_url | varchar(500) | nullable — signed S3 URL (15-min TTL) |
| error | text | nullable |
| completed_at | timestamp | nullable |
| created_at | timestamp | |

**Index:** `(workspace_id, user_id, status)`

---

## 9. System Tables
### sync_logs
Track every sync operation for debugging.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| integration_type | varchar(20) | store, ad_account, analytics, search, email |
| integration_id | bigint | FK to the relevant integration table |
| status | varchar(20) | started, completed, failed, partial |
| records_synced | int | nullable |
| records_failed | int | nullable |
| error_message | text | nullable |
| started_at | timestamp | |
| completed_at | timestamp | nullable |
| duration_ms | int | nullable |

**Index:** `(workspace_id, started_at)`, `(integration_type, integration_id, started_at)`

### global_settings
Superadmin-managed defaults.

| Column | Type | Notes |
|--------|------|-------|
| key | varchar(100) PK | e.g. `channel_mapping.defaults`, `attribution.default_model` |
| value | jsonb | |
| updated_at | timestamp | |
| updated_by | bigint FK | user who last changed it |

---

## Email Data Tables
### email_campaigns

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| email_account_id | bigint FK | |
| platform_campaign_id | varchar(50) | |
| name | varchar(255) | |
| channel | varchar(10) | email, sms |
| status | varchar(20) | sent, scheduled, draft |
| sent_at | timestamp | nullable |
| recipients | int | |
| opens | int | |
| clicks | int | |
| conversions | int | |
| revenue | decimal(12,2) | attributed revenue |
| bounce_count | int | |
| unsubscribe_count | int | |
| synced_at | timestamp | |

**Index:** `(workspace_id, sent_at)`

### email_flows

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| email_account_id | bigint FK | |
| platform_flow_id | varchar(50) | |
| name | varchar(255) | |
| status | varchar(20) | live, manual, draft |
| trigger_type | varchar(50) | nullable |
| total_revenue | decimal(14,2) | cumulative |
| total_conversions | int | |
| synced_at | timestamp | |

**Index:** `(workspace_id)`

---

## Digest & Notification Tables

### digest_schedules

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| workspace_id | bigint FK | |
| user_id | bigint FK | |
| frequency | varchar(10) | daily, weekly |
| day_of_week | smallint | nullable — 1=Monday for weekly |
| time | time | e.g. 07:00 |
| timezone | varchar(50) | |
| delivery_channel | varchar(10) | email (slack: v2) |
| slack_webhook_url | varchar(500) | nullable, encrypted — v2, contains secret token |
| metrics | jsonb | `["revenue","orders","ad_spend","net_profit","roas"]` |
| enabled | boolean | default true |
| created_at | timestamp | |

**Index:** `(workspace_id)`

---

## Eloquent Model Map

**WorkspaceScope required:** Store, AdAccount, AnalyticsProperty, SearchProperty, EmailAccount, Order, OrderLineItem, Refund, Product, ProductVariant, Customer, AdCampaign, AdSet, Ad, AdInsight, Ga4Daily, GscDaily, PageSpeed, UptimeCheck (v2), CogsEntry, ShippingRule, OperationalCost, PlatformFeeRule, DailySnapshot, DailySnapshotCohort, Alert, AlertRule, Annotation, SavedView, CustomerSegment, EmailCampaign, EmailFlow, DigestSchedule, SyncLog, SharedLink, WorkspaceInvitation, UtmTemplate, Export.

**Special scope — ChannelMapping:** `workspace_id` is nullable (null = global default). Do NOT apply WorkspaceScope. Instead, query manually: `WHERE workspace_id = :id OR workspace_id IS NULL ORDER BY priority ASC` to include both workspace-specific and global default rules.

**WorkspaceScope (simple FK, no global scope):** WorkspaceSlugHistory.

**No WorkspaceScope:** Organization, Workspace, User, FxRate, Holiday, GlobalSettings.

**SoftDeletes:** Workspace and Orders.

**Pivot models (no auto-increment):** WorkspaceUser (PK: workspace_id+user_id), WorkspaceHoliday (PK: workspace_id+holiday_id), CustomerSegmentMember (PK: segment_id+customer_id).

**JSONB $casts needed:** Workspace (brand_keywords, naming_dimensions, onboarding_checklist), Order (discount_codes, touchpoints), Refund (line_items), Product (tags), Customer (tags), AdCampaign/AdSet/Ad (parsed_dimensions), AdSet (targeting), AdInsight (extra_metrics), SavedView (filters, columns, sort), CustomerSegment (rules), DigestSchedule (metrics), GlobalSettings (value), WorkspaceUser (page_permissions).

**Key relationships beyond FKs:**
```
Organization 1──N Workspace
Workspace    M──N User (pivot: workspace_users → role, page_permissions)
Workspace    M──N Holiday (pivot: workspace_holidays → notify)
Store        1──N Order, Product, ProductVariant, Customer, PageSpeed, UptimeCheck
AdAccount    1──N AdCampaign, AdInsight
AnalyticsProperty ──belongs to Store (nullable), has many Ga4Daily
SearchProperty    ──belongs to Store (nullable), has many GscDaily
Order        ──belongs to AdCampaign (via matched_campaign_id, nullable)
Customer     M──N CustomerSegment (pivot: customer_segment_members)
EmailAccount 1──N EmailCampaign, EmailFlow
SyncLog      ──polymorphic integration_id (no real FK)
```

**No standard timestamps (`$timestamps = false`):** FxRate, PageSpeed, UptimeCheck, DailySnapshot, DailySnapshotCohort, ChannelMapping, EmailCampaign, EmailFlow, SyncLog, GlobalSettings. (Alert and DigestSchedule have `created_at` only — set `const UPDATED_AT = null` in model.)

---

## Migration Order
Build in this order (each step enables testing of the next):

1. `organizations`, `workspaces`, `workspace_slug_history`, `users`, `workspace_users`, `workspace_invitations`
2. `stores`, `ad_accounts`, `analytics_properties`, `search_properties`, `email_accounts`
3. `products`, `product_variants`, `cogs_entries`
4. `customers`
5. `ad_campaigns`, `ad_sets`, `ads`, `ad_insights` (before orders — orders.matched_campaign_id FK)
6. `orders`, `order_line_items`, `refunds`
7. `ga4_daily`, `gsc_daily`
8. `daily_snapshots`, `daily_snapshot_cohorts`
9. `fx_rates`, `shipping_rules`, `operational_costs`, `platform_fee_rules`
10. `channel_mappings`, `holidays`, `workspace_holidays`
11. `alerts`, `alert_rules`, `annotations`, `saved_views`
12. `customer_segments`, `customer_segment_members`
13. `email_campaigns`, `email_flows`
14. `digest_schedules`
15. `page_speeds`, `uptime_checks` (v2 — create table, no scheduled job at MVP)
16. `sync_logs`, `global_settings`, `shared_links`, `utm_templates`, `exports`

**Requires `DB::statement()` (not expressible via Laravel Schema builder):**
- `daily_snapshots`: unique constraint with COALESCE — `UNIQUE (workspace_id, COALESCE(store_id, 0), date)`
- `ga4_daily`: unique constraint with COALESCE — `UNIQUE (workspace_id, analytics_property_id, date, source, medium, COALESCE(landing_page, '__none__'), COALESCE(country, '__none__'), device_category)`
- `ad_insights`: unique constraint with COALESCE — `UNIQUE (workspace_id, platform, level, COALESCE(ad_id, COALESCE(ad_set_id, campaign_id)), date)`
- `cogs_entries`: CHECK constraint — `CHECK (product_id IS NOT NULL OR variant_id IS NOT NULL)`
- `orders`: partial index — `CREATE INDEX ON orders (workspace_id, matched_campaign_id) WHERE matched_campaign_id IS NOT NULL`
- `products`: index on handle — `CREATE INDEX ON products (workspace_id, handle)`

**SyncLog polymorphic pattern:** Uses `integration_type` enum + `integration_id`, NOT Laravel's `morphTo()`. No _type column with class names. Resolve manually based on integration_type enum value.

**Notes:**
- `orders.matched_campaign_id` is nullable FK to `ad_campaigns` — campaigns must be created first
- `sync_logs.integration_id` is polymorphic (references stores/ad_accounts/analytics/search/email) — no real FK constraint
- `orders.customer_id` nullable = guest checkout or unresolved dedup
- `cogs_entries` with both `product_id` and `variant_id` null is invalid — enforce via `CHECK (product_id IS NOT NULL OR variant_id IS NOT NULL)` constraint
- COGS lookup priority (full hierarchy in workspace-architecture.md): variant-specific → product-level → auto-pulled from store → per-store settings → workspace default margin % → global default. Query: `WHERE (variant_id = :vid OR (variant_id IS NULL AND product_id = :pid)) AND effective_from <= :order_date ORDER BY (variant_id IS NOT NULL) DESC, effective_from DESC LIMIT 1`
- Workspace soft delete (`deleted_at`): child records remain, filtered by `WorkspaceScope`. Hard delete after 30-day grace period cascades all child tables.

**Missing unique constraints to add:**
- `analytics_properties`: `(workspace_id, property_id)` unique
- `search_properties`: `(workspace_id, site_url)` unique
- `email_accounts`: `(workspace_id, platform, platform_account_id)` unique — add `platform_account_id varchar(255)` column (Klaviyo account ID from API). Do NOT use access_token (changes on every refresh)
- `refunds`: `(order_id, platform_refund_id)` unique (prevent duplicate refund records)
- `ad_insights`: `level` already in unique constraint (updated inline above)

**Missing indexes to add:**
- `ga4_daily`: `(workspace_id, source, medium, date)` — funnel source filter queries
- `orders`: `(workspace_id, matched_campaign_id) WHERE matched_campaign_id IS NOT NULL` — store-side revenue per campaign (partial index)
- `products`: `(workspace_id, handle)` — GSC page→product matching
