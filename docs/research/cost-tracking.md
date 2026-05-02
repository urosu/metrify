# Complete Cost Tracking & COGS Research

Research conducted 2026-04-30.

---

## 1. WooCommerce Native COGS (as of WC 10.3, Oct 2025)

WooCommerce COGS is **now native** — graduated from beta in WC 9.8 to production in WC 10.3.

**Meta keys:**
- Products: `_cogs_total_value`
- Variations: `_cogs_value_overrides_parent`
- Order line items: `_cogs_value`
- Orders: `_cogs_total_value`
- Legacy third-party key: `_wc_cog_cost` (from SkyVerge/WPFactory plugins, still widely used)

**REST API (v3):** Products/variations expose a `cost_of_goods_sold` object:
- `defined_value` — explicitly set cost
- `effective_value` (read-only) — resolved cost accounting for parent/variation inheritance
- `total_value` (read-only) — total COGS for order

**Shopify vs WooCommerce difference:**
- Shopify: cost lives on `InventoryItem` resource (`inventory_item.cost` or GraphQL `unitCost`), not on product/variant directly
- WooCommerce: cost is product meta with `defined_value` + `effective_value` inheritance

**Third-party plugins still common:** WPFactory "Cost of Goods for WooCommerce" (free+pro), SkyVerge premium. Both use `_wc_cog_cost`.

## 2. COGS Date History — Best Practice

**TrueProfit is the gold standard.** Supports unlimited COGS periods per product/variant with date ranges. Default: current COGS covers entire lifetime. Add historical periods with start dates; preceding periods auto-fill. Changes apply to future orders only unless you explicitly "recalculate past orders."

**Most tools use a single current COGS value** applied retroactively to all orders. No date-range tracking: BeProfit, Lifetimely, Metorik, Profit Calc.

**Best practice:** Historical orders should use the COGS that was active at the time of the order. This requires:
1. COGS entries with effective date ranges
2. Orders store their COGS at order time (snapshot, not a live lookup)
3. Option to retroactively recalculate when COGS changes (with user confirmation)

## 3. Complete Cost Picture — All Line Items

**Ideal P&L formula:**
```
Gross Revenue
- Discounts
- Returns / Refunds
- Return shipping + restocking costs
= Net Revenue
- COGS (product cost)
- Shipping (actual carrier cost)
- Payment processing fees (actual from gateway)
- Platform / marketplace fees
- Handling / packaging costs
- Customs / duties / tariffs
= Gross Profit (CM1)
- Ad spend (per platform: Meta, Google, TikTok...)
= Contribution Margin (CM2)
- Operational costs (recurring: SaaS, rent, salaries)
- One-time costs
= Net Profit (CM3)
- Tax liability (output VAT - input VAT)
= True Net Profit
```

### Who covers what:

| Cost line | TrueProfit | Lifetimely | BeProfit | Bloom | Conjura |
|-----------|-----------|-----------|---------|-------|---------|
| COGS with date ranges | **Yes** | No | No | No | No |
| Shipping (auto-pull) | ShipStation | ShipStation, ShipBob | ShipStation, ShipHero, Shippo | ShipStation, ShipHero | Yes (SKU level) |
| Payment fees (actual) | **Auto from Shopify** | Line item | **Auto from Shopify/PayPal/Stripe** | Auto for Shopify, formula for others | No |
| Handling/packaging | **Per-product field** | No | No | No | No |
| Customs/duties | In COGS | No | No | No | No |
| Operational costs | Fixed (recurring) + Variable (% formula) | Custom Costs + QuickBooks sync | Manual | Manual | No |
| Input VAT | No | No | No | No | No |
| Per-variant COGS | **Yes** | No | **Yes** | **Yes** | CSV |

**Nobody shows ALL costs. Every tool has gaps.** Customs/duties are always manual. Input VAT is never separated. Operational costs require manual entry.

## 4. VAT/Tax Settings

- **No analytics tool offers a "prices include VAT" toggle.** They rely on what the store platform reports. Shopify/WooCommerce handle VAT-inclusive vs exclusive pricing natively. Analytics tools take `total` and `tax` fields from the order.
- **Multi-country VAT:** Handled at store level (WooCommerce tax classes, Shopify Markets). Analytics tools just read the tax amount charged per order.
- **P&L impact:** Revenue is shown net of tax (platform strips it before reporting).

## 5. What Nexstage Should Track (MVP)

**Per-product cost (stored with date history):**
- Product cost / COGS (auto-pulled from Shopify/WooCommerce + manual override)
- Per-variant COGS when variants differ
- Workspace default margin % as fallback (e.g., 30%)
- Date ranges on COGS entries (effective_from / effective_to)

**Per-order costs (computed or pulled):**
- COGS (from product cost at order date)
- Actual shipping cost (from store data or carrier integration)
- Actual payment processing fee (auto-pulled from Shopify Payments/Stripe/PayPal when available, formula fallback)
- Platform/channel fees
- Handling/packaging (per-product setting or flat per-order)

**Workspace-level costs:**
- Ad spend per platform (from ad account integrations)
- Recurring operational costs (SaaS, rent, salaries — user-entered, recurring schedule)
- One-time costs (user-entered with date)
- Custom expense categories
- Tax settings: "prices include VAT" toggle per store, VAT rate defaults

**Display rules:**
- Products using default margin %: amber badge "estimated cost"
- Products with no cost AND no default: rose badge "cost unknown"
- P&L shows "Revenue from uncosted products" as a separate line
- If >20% revenue is uncosted: persistent alert on Dashboard

## 6. COGS Entry Methods (Priority Order for MVP)

1. **Auto-pull from store** — Shopify `InventoryItem.cost` / WooCommerce `cost_of_goods_sold.effective_value`
2. **Workspace default margin %** — fallback (e.g., "assume 30% of price is cost")
3. **Per-product manual edit** — inline editable in Products table
4. **CSV bulk upload** — for merchants with hundreds of products
5. **Per-variant COGS** — when variant costs differ

**Post-MVP:**
6. Google Sheets live sync (BeProfit pattern)
7. Supplier auto-sync (CJ Dropshipping, Printful, Printify)
8. QuickBooks Online sync for operational costs (Lifetimely pattern)
9. COGS by quantity break (tiered supplier pricing)

---

## Sources
- WooCommerce COGS Documentation (woocommerce.com/document/cogs/)
- WooCommerce 10.3 release notes (developer.woocommerce.com)
- WooCommerce COGS REST API PRs (#51675, #52067)
- Shopify InventoryItem cost (community.shopify.com)
- TrueProfit Historical COGS (help.trueprofit.io)
- TrueProfit Custom Costs (help.trueprofit.io)
- Metorik COGS Reports (help.metorik.com)
- Lifetimely P&L (useamp.com)
- BeProfit integrations (beprofit.co)
- Bloom Analytics (bloomanalytics.io)
- Conjura Product Analytics (conjura.com)
