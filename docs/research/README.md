# Research

Topic-specific research findings. Each file answers "how should we build X?" based on competitor analysis and web research.

## By topic

| File | Topic | Key finding |
|------|-------|-------------|
| `cost-tracking.md` | Complete cost picture (COGS → net profit) | WooCommerce COGS native since WC 10.3. TrueProfit only tool with date-range COGS. Nobody tracks ALL costs. |
| `cogs-configuration.md` | COGS entry methods across competitors | Lifetimely has global % fallback. Most tools treat missing COGS as $0. |
| `multi-store.md` | Multiple stores per workspace | Glew/BeProfit/Putler support mixed Shopify+WooCommerce. Up to 20 Meta ad accounts per workspace. |
| `shipping-country.md` | Shipping cost per country + COD + what-if | No competitor does COD tracking or free-shipping what-if simulator. |
| `funnel-flow.md` | User flow / funnel visualization | Horizontal bars beat Sankey. No tool shows ad→landing→product→purchase cleanly. |
| `inventory-prediction.md` | Stock velocity, prediction, reorder | Stocky/Cogsy patterns. 28-day rolling avg. Color coding by days-of-stock. |
| `utm-naming-builder.md` | UTM builders + ad naming convention parsers | Motion is gold standard. Delimiter + position mapping + live preview. |
| `drilldown-ux.md` | How to display detail data (drawer vs modal) | Right-side overlay drawer 480-560px. 14px font floor. WCAG AA contrast. |
| `performance-page.md` | Page speed beyond Google scores | Add uptime monitoring + TTFB trending. Shopify doesn't cover these. |
| `holidays-calendar.md` | Holiday/special days databases | Calendarific API (230+ countries, $100/yr) + manual ecommerce event seeds. |
| `superadmin-panel.md` | Internal admin panel patterns | Filament v5 on /admin route. Impersonation via evo-mark/laravel-impersonate. |
| `gap-check.md` | Comprehensive feature gaps across ALL pages | Return/refund tracking, creative fatigue, payment fee auto-pull, purchase latency, keyword cannibalization. |
| `2026-trends.md` | 2026 ecommerce analytics trends | AI agent traffic exceeds human traffic. Server-side tracking recovers 37% more conversions. |
| `winners-losers.md` | Winners & losers feature patterns | Ranked-delta leaderboard with momentum arrows. |
| `best-creatives.md` | Creative analysis patterns | Thumbnail-first grid + triage classification. |
| `onboarding-flow.md` | Onboarding UX patterns | <24h to useful dashboard. |
| `ga4-oauth.md` | GA4 OAuth integration details | Technical implementation notes. |
| `polaris-pivot.md` | Shopify Polaris design system notes | Design system reference. |
| `date-compare.md` | Date comparison patterns | Match day-of-week option. |
| `daily-journal.md` | Daily journal / activity log concept | Annotations as chart markers. |
| `chrome-layout.md` | Chrome/header layout patterns | Top chrome elements. |
| `integrations-page.md` | Integration settings page patterns | Entity cards with status dots. |
| `tools-utilities.md` | Helper tools patterns | Profit calculator, shipping estimator. |
| `user-flow-funnel.md` | Earlier funnel research | Superseded by funnel-flow.md for most purposes. |
| `shipping-country-analysis.md` | Earlier shipping research | Superseded by shipping-country.md for most purposes. |
| `drilldown-pattern.md` | Earlier drill-down research | Superseded by drilldown-ux.md. |
| `pii-masking.md` | PII masking patterns | Privacy considerations. |
