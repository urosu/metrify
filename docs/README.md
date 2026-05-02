# Nexstage Documentation

## For coding agents: start here

Read files in this order:

1. **`plan/feature-list.md`** — What we're building. All MVP features with data requirements and competitor baselines.
2. **`plan/pages-outline.md`** — How each page looks. UI elements, layout, interactions, navigation structure.
3. **`plan/workspace-architecture.md`** — Data model. Workspaces, integrations, settings hierarchy, COGS tracking, cost structure, user roles, superadmin panel.

## When building a specific feature

Check `research/` for topic-specific findings. Key files:

| Building... | Read |
|-------------|------|
| P&L / profit page | `research/cost-tracking.md`, `research/cogs-configuration.md` |
| Shipping & countries | `research/shipping-country.md` |
| Ad campaigns | `research/utm-naming-builder.md` |
| Products / inventory | `research/inventory-prediction.md` |
| Funnel | `research/funnel-flow.md` |
| Site health / speed | `research/performance-page.md` |
| Holiday calendar | `research/holidays-calendar.md` |
| Multi-store setup | `research/multi-store.md` |
| Drill-down UX / drawers | `research/drilldown-ux.md` |
| Superadmin panel | `research/superadmin-panel.md` |
| Any page (gap check) | `research/gap-check.md` |

## When you need competitor context

Check `competitors/` — only when you need to understand how others solve a problem.

| Need | Read |
|------|------|
| How competitors solve feature X | `competitors/features/<feature-name>.md` (33 features) |
| UI patterns to use/avoid | `competitors/patterns.md` (189 patterns) |
| Metric naming conventions | `competitors/crosscuts/metric-dictionary.md` |
| Specific competitor deep-dive | `competitors/profiles/<name>.md` (41 profiles) |
| Screen-by-screen teardowns | `competitors/teardowns/` (5 tools) |
| Non-ecommerce UX inspiration | `competitors/inspiration/` (Stripe, Linear, Vercel, Plausible, GA4) |

## Directory structure

```
docs/
├── plan/           — WHAT WE BUILD (specs for coding)
├── research/       — HOW TO BUILD IT (topic research, findings, decisions)
└── competitors/    — WHAT OTHERS DO (reference only)
    ├── profiles/   — per-competitor (41 tools)
    ├── features/   — per-feature deep dives (33 features)
    ├── teardowns/  — screen-by-screen UI analysis (5 tools)
    ├── crosscuts/  — cross-competitor analyses (7 topics)
    └── inspiration/ — non-ecommerce UX refs (5 tools)
```
