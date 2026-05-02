# Nexstage Plan

## For coding agents: read in this order

### Step 1 — Understand the data layer
1. `database-schema.md` — 40+ tables, columns, types, indexes, relationships, migration order
2. `workspace-architecture.md` — multi-store model, settings hierarchy, COGS tracking, roles

### Step 2 — Understand integrations
3. `integrations.md` — 8 data sources with API versions, endpoints, rate limits, sync schedules
4. `tech-stack.md` — Laravel 13 + Inertia v3 + Vue 3.5 + PG18 + Redis + ECharts

### Step 3 — Implementation reference
5. `enums.md` — 30 PHP 8.5 backed enums with all case values, state transitions, platform mappings
6. `service-contracts.md` — method signatures for all services, value objects, base classes, middleware
7. `api-payloads.md` — example JSON for every external API response and webhook payload
8. `frontend-spec.md` — design tokens, TypeScript types, Vue component contracts, ECharts configs
9. `pages-outline.md` — 11 sidebar pages, layouts, UI elements, cross-page data flows

### Step 4 — Start coding
10. **`coding-spec.md`** — every formula, query, service, route, component, and scheduled job
11. `onboarding.md` — signup → connect store → import → dashboard

### Step 5 — Read before writing any code
12. **`non-obvious-issues.md`** — build order, runtime traps, security requirements, implementation details

### Archive (audit trails — do not need to read)
- `archive/schema-review.md` — schema fixes audit trail
- `archive/audit-round2.md` — data flow fixes audit trail
- `archive/final-audit.md` — cross-doc consistency audit
- `archive/fact-check-corrections.md` — 145 claims verified, 56 corrected

---

All hard rules and coding instructions are in `CLAUDE.md` (project root).
