# COGS / Cost Configuration -- Competitor Research

_Date: 2026-04-30_

## 1. Default / Fallback COGS

| Tool | Default COGS? | How it works |
|---|---|---|
| **Lifetimely** | Yes -- "Default gross margin" | User sets a % (e.g. 40%). Applied as `price * margin%` only to products with no explicit cost. Priority: Lifetimely cost > Shopify cost > default margin. |
| **Bloom** | No global default | No default margin toggle. Products without cost are highlighted red; toggle to filter "products without Shopify cost". Uses most-recent cost entry if date-range gaps exist. |
| **Conjura** | Percentage-of-revenue fallback | CSV import supports a `Perc of Revenue Cost Amount` column used as fallback when no fixed cost exists. Not a single global setting. |
| **TrueProfit** | No global % fallback found | Default COGS value covers a product's lifetime but must be set per-product. No evidence of a workspace-wide "assume X%" fallback. |
| **BeProfit** | Not documented | No evidence of a global default margin. Support article exists ("missing COGS") but content not publicly accessible. |
| **Profit Calc** | Not documented | No default margin feature found in docs. |
| **StoreHero** | Not documented | Enterprise/agency-oriented; COGS setup details not publicly documented. |

**Takeaway:** Only Lifetimely has a clean, user-friendly global fallback. Conjura offers per-product % fallback via CSV. Most tools simply leave gaps.

---

## 2. COGS Entry Methods

| Method | Lifetimely | TrueProfit | BeProfit | Bloom | Profit Calc | Conjura | StoreHero |
|---|---|---|---|---|---|---|---|
| Per-product manual | Yes | Yes | Yes | Yes | Yes | Yes | -- |
| Per-variant | No | Yes | Yes | Yes | -- | No (SKU-level) | -- |
| CSV bulk upload | Yes | Yes | -- | Yes | -- | Yes | -- |
| Google Sheets sync | No | No | Yes | No | No | No | No |
| Auto-pull Shopify "Cost per item" | Yes | Yes | Yes | Yes | Yes | Yes (platform sync) | Yes |
| CJ Dropshipping sync | No | Yes | Yes | No | Yes | No | No |
| AliExpress sync | No | No | Yes (Chrome ext) | No | Yes | No | No |
| Printful / Printify sync | No | No | No | No | Yes | No | No |
| COGS by date range | Yes (start/end dates) | Yes (unlimited periods) | No | Yes (date + tiers) | No | Yes (start/end dates) | -- |
| COGS by quantity break | No | Yes | No | Yes (tiered) | Yes | No | No |

**Takeaway:** TrueProfit and Bloom lead on flexibility (date ranges + per-variant). BeProfit leads on supplier integrations (Google Sheets, AliExpress Chrome extension). Profit Calc has widest POD/dropship coverage (Printful, Printify, CJ, AliExpress).

---

## 3. COGS Zones (TrueProfit)

- Zones let you set **different COGS per delivery destination** for the same product.
- Use case: dropshipping from different warehouses/countries where landed cost varies by destination.
- CJ Dropshipping integration auto-maps zone costs based on delivery location.
- "Worldwide" zone includes all products as baseline.
- CSV export/import is scoped to the selected zone.
- Available on Ultimate plan (unlimited zones).
- **No other tool offers this.** Closest is Bloom's country-based shipping rules, but those are shipping costs, not COGS.

---

## 4. Shipping Cost Configuration

| Method | TrueProfit | BeProfit | Bloom | Profit Calc | Conjura | Lifetimely |
|---|---|---|---|---|---|---|
| Flat rate per order | Yes | Yes | Yes (Shopify sync) | Yes | Yes (flat or % of revenue) | No |
| Per-item shipping | Yes (first item + additional) | Yes (multiply by qty) | -- | Yes | -- | Yes (CSV column) |
| Weight-based rules | Yes | Yes | -- | Yes (country+weight) | -- | No |
| Country-based rules (zones) | Yes (shipping profiles) | Yes (shipping profiles) | Yes (custom rules) | Yes (country combos) | -- | No |
| Auto-sync Shopify shipping labels | -- | -- | Yes | Yes | -- | -- |
| ShipStation / ShipBob / Shippo sync | No | No | No | Yes (ShipBob, Shiphero, Shippo) | No | No |
| Free shipping threshold | Not found | Not found | Not found | Not found | Not found | Not found |

**Takeaway:** TrueProfit and Profit Calc are most granular (country + weight + quantity combos). Bloom auto-syncs Shopify shipping data. No tool offers explicit "free shipping threshold" configuration. Profit Calc uniquely integrates ShipBob/Shippo/Shiphero.

---

## 5. Transaction Fee Configuration

| Tool | Approach | Details |
|---|---|---|
| **TrueProfit** | Auto-sync actual fees | Pulls actual fees from Shopify Payments, PayPal, Stripe. Also supports formula fallback (% + fixed) but reviews note formula can be inaccurate. |
| **BeProfit** | Auto-sync + formula | Pulls PayPal and Shopify processing fees automatically. Manual COD fee config via Settings > Processing Fees. |
| **Bloom** | Auto-sync + formula | Shopify Payments fees imported automatically. Other gateways: user configures % fee + fixed fee per gateway. |
| **Lifetimely** | Not detailed | Transaction fees mentioned as part of P&L but setup docs unclear. |
| **Profit Calc** | Not auto-synced | Transaction fees not automatically calculated; must be included in cost-per-item or CAC manually. |
| **Conjura** | Custom import | Fees added via custom data import or as per-order costs. |
| **StoreHero** | Included in "fully loaded COGS" | Details not documented; appears automated for Shopify stores. |

**Takeaway:** Users strongly prefer actual fee sync over formula. TrueProfit and BeProfit lead here. Formula-based (% + fixed fee) is the common fallback for non-Shopify-Payments gateways. Bloom's hybrid (auto for Shopify, manual formula for others) is the most practical middle ground.

---

## 6. Missing COGS Handling

| Tool | Behavior |
|---|---|
| **Bloom** | Rows highlighted **red** in product cost table. Toggle to filter "products without Shopify cost". No auto-exclusion; requires manual fix. |
| **Lifetimely** | Falls back to default gross margin %. If no margin set either, behavior unspecified (likely $0 cost = inflated profit). |
| **TrueProfit** | No explicit warning documented. Products without COGS likely calculated with $0 cost. |
| **BeProfit** | Support article exists for "orders missing COGS" but resolution unclear. Google Sheets sync can backfill. |
| **Conjura** | System COGS from platform is baseline. CSV imported cost overrides. If both absent, unclear. |
| **Profit Calc** | No documentation on missing cost handling. |
| **StoreHero** | Not documented. |

**Takeaway:** Bloom's red-highlight approach is the clearest UX for surfacing gaps. Lifetimely's fallback margin is the most forgiving. Most tools silently treat missing COGS as $0, which inflates profit -- a common user complaint across reviews.

---

## Design Implications for Nexstage

1. **Must-have: Global fallback margin** (like Lifetimely). Simple % input, clearly labeled "applied to products without explicit cost". Show which products use fallback vs explicit in a product cost table.
2. **Must-have: Visual gap indicator** (like Bloom). Red/warning rows for products missing cost. Dashboard-level "X% of revenue has no COGS data" warning badge.
3. **COGS entry priority**: Shopify sync as default > manual override > CSV bulk > fallback %. Display the active source per product.
4. **Date-range COGS** is a differentiator few tools do well. Bloom and TrueProfit lead.
5. **Transaction fees**: Auto-sync from Shopify Payments is table stakes. Formula (% + fixed) as fallback for other gateways.
6. **Shipping**: Country-based shipping profiles cover 80% of use cases. Per-item + weight-based for power users.
7. **COGS Zones** (TrueProfit-unique) are only relevant for dropshippers with multi-warehouse fulfillment -- consider as a later feature.
