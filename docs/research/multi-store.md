# Multi-Store Per Workspace Patterns

Research conducted 2026-04-30.

---

## Key finding: Multiple stores per workspace IS supported by several competitors

| Tool | Shopify | WooCommerce | Mixed CMS in one workspace? | Multi-ad-account? |
|------|---------|-------------|---------------------------|-------------------|
| **Glew** | Yes | Yes | **Yes** (+ Magento, BigCommerce, PrestaShop) | Yes |
| **BeProfit/Viably** | Yes | Yes | **Yes** (+ Amazon) | Yes |
| **Putler** | Yes | Yes | **Yes** (+ BigCommerce, Etsy, PayPal, Stripe) | Yes |
| **Metorik** | Yes | Yes | **No** (separate companies per CMS) | N/A |
| **Triple Whale** | Yes | No | No | Yes (up to 20 Meta accounts) |
| **Polar Analytics** | Yes | No | No | Yes |
| **Northbeam** | Yes | Partial | No | Yes |
| **Lifetimely** | Yes | No | No (each store = separate subscription) | N/A |

## How multi-store works in practice

### Store attachment
- **Glew, Putler**: All stores connect to one account. Consolidated is default view, per-store is drill-down.
- **Triple Whale**: "Multi Shop View" (formerly Pods) groups multiple Shopify stores into named Views. Each store connects via separate OAuth.
- **Lifetimely**: Multi-store dropdown + Consolidated view, but each store = separate subscription.

### Multiple ad accounts
- **Triple Whale**: Up to 20 Meta ad accounts per workspace. Also supports ad-account-to-store segmentation (one FB account mapped across multiple stores).
- **Northbeam**: Multiple ad accounts per dashboard (Meta, Google, TikTok, Snap, Pinterest). Auto-applies UTM params across connected Google Ads accounts.
- **Polar**: Multiple ad accounts per workspace.

### Multiple GA4 / GSC properties
- **No tool explicitly advertises multi-GA4 per workspace.** But multi-store tools effectively get it by connecting one GA4/GSC per store.
- **GA4 converts all currencies to USD at processing time** — cross-property aggregation doesn't exist natively.
- Pattern: one GA4 property per domain, one GSC property per domain. Multi-store = connect one of each per store.

### Data aggregation
- **Currency conversion**: Putler benchmark — converts at **transaction-day mid-market rate**, not current rate. User picks one reporting currency.
- **Order deduplication**: Auto-dedup when same order appears in both store and payment gateway (Putler). No tool deduplicates same customer across separate stores.
- **Revenue rollup**: Aggregated total + stacked contribution bar per store (Glew pattern).
- **Toggle**: "All stores" vs single store as top-chrome toggle (Glew, Putler, Lifetimely).

## Nexstage implications

One workspace should support:
- Multiple stores (Shopify + WooCommerce + others in v2)
- Multiple ad accounts per platform (FB, Google, TikTok)
- Multiple GA4 properties (one per store/domain)
- Multiple GSC properties (one per store/domain)
- One Klaviyo/Omnisend account (email is usually brand-level, not store-level)

Data model:
```
Workspace
├── Stores[] (1..N — Shopify, WooCommerce, mixed)
│   ├── Store A (Shopify US)
│   ├── Store B (WooCommerce EU)
│   └── Store C (Shopify UK)
├── AdAccounts[] (1..N per platform)
│   ├── Meta Ad Account US
│   ├── Meta Ad Account EU
│   └── Google Ads Account (global)
├── AnalyticsProperties[] (1..N)
│   ├── GA4 Property — store-a.com
│   └── GA4 Property — store-b.com
├── SearchProperties[] (1..N)
│   ├── GSC — store-a.com
│   └── GSC — store-b.com
└── EmailAccounts[] (typically 1)
    └── Klaviyo
```

Currency: convert at transaction-day rate to workspace reporting currency. Display per-store contribution bars on aggregated KPIs.

---

## Sources
- Triple Whale Multi Shop View (kb.triplewhale.com)
- Triple Whale Ad Account Segmentation (triplewhale.com/blog)
- Northbeam Multi-Brand Configuration (docs.northbeam.io)
- Metorik Multiple Stores (help.metorik.com)
- Putler Data Consolidation (putler.com)
- BeProfit Shopify + WooCommerce (beprofit.co)
- Glew Multichannel (glew.io)
- Polar Analytics GA4 Connection (intercom.help/polar-app)
