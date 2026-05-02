# Page-Level User Permissions — Competitor Research

Research date: 2026-05-01

## Product-by-Product Findings

### Triple Whale

- **Roles:** Owner, Admin, User (read-only)
- **Page-level permissions:** No true page-level gating. Instead, per-user **feature flags** can be toggled independently of role. Two notable flags:
  - **Expenses access** — hides payment gateways, COGS, handling fees, shipping, custom expenses, net profit
  - **PII access** — hides all personally identifiable information
- **Configuration:** Admin toggles feature flags per user in Settings > Team
- **Navigation gating:** Features the user lacks access to are hidden from the UI (not shown with a lock or "no access" message)
- **Dashboard sharing:** Per-dashboard permissions (invited only, all shop members, or owner-only)

Sources:
- [User Permissions (Access)](https://kb.triplewhale.com/en/articles/13185012-user-permissions-access)
- [Invite Team Members](https://kb.triplewhale.com/en/articles/5740601-invite-team-members)

---

### Lifetimely

- **Roles:** Inherits Shopify staff permissions (no independent role system)
- **Page-level permissions:** Not applicable — Lifetimely runs as a Shopify embedded app and relies on Shopify's native staff permission model. No independent page-level gating found.
- **Configuration:** Managed via Shopify admin staff accounts

Sources:
- [Lifetimely on Shopify App Store](https://apps.shopify.com/lifetimely-lifetime-value-and-profit-analytics)

---

### Peel Insights

- **Roles:** No public documentation found on user roles or permissions
- **Page-level permissions:** No evidence of page-level or section-level permissions in public documentation
- **Notes:** Peel focuses on pre-built dashboards (Customer, Subscriptions, Marketing, Attribution) with customization, but team permission details are not publicly documented

Sources:
- [Peel Dashboards](https://help.peelinsights.com/docs/dashboards)

---

### Polar Analytics

- **Roles:** Four default roles: Admin, Editor, Viewer + one more (likely Contributor-level). Also supports **custom roles**.
- **Page-level permissions:** Not page-level per se, but granular permission atoms:
  - View Content (all workspace content)
  - Edit Reports/Views (create/modify custom reports, views, custom metrics)
  - Manage Users (add, remove, modify roles)
  - Manage Connectors (admin-only)
- **Configuration:** Admins create custom roles by combining permission atoms. Roles assigned at invite time or in Settings > Users.
- **Navigation gating:** Polar hides features the role cannot access

Sources:
- [User Roles & Permissions](https://intercom.help/polar-app/en/articles/7834432-user-roles-permissions)
- [Managing Users](https://intercom.help/polar-app/en/articles/10844089-managing-users)

---

### BeProfit

- **Roles:** No public documentation found on independent user roles
- **Page-level permissions:** No evidence found. Likely inherits Shopify staff permissions as an embedded app.
- **Notes:** BeProfit does not appear to have a standalone team management or permissions system in public documentation

---

### Shopify Analytics (native)

- **Roles:** Store Owner, Staff (with granular permission checkboxes)
- **Page-level permissions:** Yes — **section-level checkboxes**. Staff permissions are organized by area:
  - Analytics (view dashboards/reports)
  - Reports (view detailed reports, create custom reports)
  - Products, Orders, Customers, Marketing, Settings each have their own permission toggle
- **Configuration:** Checkbox grid per staff member in Settings > Users. Organization-level permissions can also be set for multi-store setups.
- **Navigation gating:** Staff members only see nav items they have permissions for — hidden, not locked
- **Notable:** The Analytics permission is a single toggle (all-or-nothing for dashboards). Reports is a separate permission. No per-report or per-dashboard granularity.

Sources:
- [Store Permissions](https://help.shopify.com/en/manual/your-account/users/roles/permissions/store-permissions)
- [Organization Permissions](https://help.shopify.com/en/manual/your-account/users/roles/permissions/organization-permissions)
- [Staff Permissions Blog](https://www.shopify.com/blog/staff-permissions)

---

### Google Analytics 4

- **Roles:** 5 roles: Administrator, Editor, Marketer, Analyst, Viewer
- **Page-level permissions:** No page-level gating. Instead, role determines capability tier (admin > edit settings > edit reports > view). Additionally, **data restrictions** gate specific metric types:
  - **No Cost Metrics** — hides cost data (Google Ads cost, cost per conversion, etc.)
  - **No Revenue Metrics** — hides revenue data (ad revenue, event revenue, etc.)
- **Configuration:** Roles assigned at Account or Property level. Data restrictions layered on top (can inherit from account to property). Configured in Admin > Account/Property Access Management.
- **Navigation gating:** All users see the same navigation. Restricted data shows as unavailable/hidden within reports, not at the page level.
- **Notable pattern:** The data restriction model (cost vs revenue) is independent of the role hierarchy. A Viewer can have full data access; an Editor can have revenue restricted. This separation of "what you can do" vs "what you can see" is the most sophisticated model in this research.

Sources:
- [GA4 Roles](https://support.google.com/analytics/answer/9356045?hl=en)
- [Access and Data Restriction Management](https://support.google.com/analytics/answer/9305587?hl=en)
- [GA4 User Roles — MeasureU](https://measureu.com/google-analytics-permissions/)
- [GA4 Permissions — Analytify](https://analytify.io/ga4-access-levels-and-permissions/)

---

### Databox

- **Roles:** 4 user types: Administrator, Editor (implied), Viewer, and at least one more
- **Page-level permissions:** Yes — **per-Databoard access control**:
  - Everyone (all account users)
  - Selected Users (specific users only)
  - Private (creator only)
- **Data Source permissions:** Separate from dashboard permissions. New data sources are shared with all users except Viewers by default.
- **Configuration:** Per-dashboard permission dropdown + per-data-source sharing. Role determines edit vs view-only capability on dashboards the user can access.
- **Navigation gating:** Dashboards the user lacks access to are hidden from their view
- **Notable pattern:** Two-axis model: (1) account role determines capabilities, (2) per-dashboard sharing determines visibility. This means a Viewer role + "Selected Users" access = can see but not edit a specific dashboard.

Sources:
- [Overview: User Management](https://help.databox.com/overview-user-management)
- [Databoard Access Permissions](https://help.databox.com/how-to-set-databoard-access-permissions)
- [Data Source Access Permissions](https://help.databox.com/how-to-set-data-source-access-permissions)

---

### Klipfolio

- **Roles:** 4 default roles for Klips: Admin, Editor, Contributor, Viewer. 4 for PowerMetrics: Account Administrator, Editor, Contributor, Viewer. Also supports **custom roles**.
- **Page-level permissions:** Per-dashboard sharing (View or Edit access per user/group). Not page-level in the nav sense, but per-asset.
- **Configuration:** Custom roles created via checkbox grid of permission atoms. Dashboards shared individually with View or Edit access.
- **Navigation gating:** Users see only dashboards shared with them. Role permissions override sharing (e.g., a View-Only role user cannot edit even if given Edit sharing access).
- **Notable pattern:** Role caps override asset-level sharing. The minimum of (role permission, share permission) applies. Custom roles allow fine-grained checkbox selection of capabilities.

Sources:
- [User Roles and Access Permissions (new)](https://support.klipfolio.com/hc/en-us/articles/360057431193)
- [Adding Custom Roles](https://support.klipfolio.com/hc/en-us/articles/215548578)
- [PowerMetrics Roles](https://support.klipfolio.com/hc/en-us/articles/360052717014)
- [Sharing FAQs](https://support.klipfolio.com/hc/en-us/articles/360001464493)

---

## UX Best Practices Summary

From general SaaS RBAC research:

1. **Hide, don't lock.** Industry consensus is to remove navigation items and UI elements the user cannot access, rather than showing them with lock icons or "no access" messages. This reduces clutter and avoids frustration.

2. **Three-layer permission model.** Effective SaaS permissions operate across: Page-Level (access to sections/modules), Operation-Level (what users can do: view, edit, delete), Data-Level (what data they can see: cost metrics, PII, etc.).

3. **Roles as permission bundles.** Start with predefined role presets (Admin, Editor, Viewer) that bundle common permissions. Allow custom roles for power users on higher plans.

4. **Separation of "capabilities" and "visibility."** Best practice is to keep "what you can do" (role) separate from "what you can see" (data restrictions / dashboard sharing). Google Analytics does this most cleanly.

5. **Backend enforcement is mandatory.** UI hiding alone is insufficient. Every API endpoint, export, and background job must enforce the same permissions server-side.

Sources:
- [How to Design Effective SaaS Roles and Permissions](https://www.perpetualny.com/blog/how-to-design-effective-saas-roles-and-permissions)
- [Enterprise Ready RBAC Guide](https://www.enterpriseready.io/features/role-based-access-control/)
- [RBAC for Embedded Dashboards — Bold BI](https://www.boldbi.com/blog/role-based-access-control-embedded-dashboards/)
- [User Permissions in Admin Dashboard — BootstrapDash](https://www.bootstrapdash.com/blog/user-permissions-in-admin-dashboard)

---

## Cross-Product Comparison Matrix

| Product | Page-level gating? | Per-dashboard sharing? | Data restrictions? | Custom roles? | Nav approach |
|---|---|---|---|---|---|
| Triple Whale | Feature flags (expenses, PII) | Yes (per dashboard) | Yes (expense/PII flags) | No | Hide |
| Lifetimely | No (inherits Shopify) | No | No | No | N/A |
| Peel Insights | Unknown | Unknown | Unknown | Unknown | Unknown |
| Polar Analytics | Permission atoms | No (workspace-wide) | No | Yes | Hide |
| BeProfit | No (inherits Shopify) | No | No | No | N/A |
| Shopify Analytics | Section checkboxes | No | No | No (preset sections) | Hide |
| Google Analytics 4 | No | No | Yes (cost/revenue) | No (5 preset roles) | Show all, restrict data |
| Databox | No | Yes (per databoard) | Implicit via data source sharing | No | Hide |
| Klipfolio | No | Yes (per dashboard) | No | Yes (checkbox grid) | Hide |

---

## Recommendation for Nexstage (Owner / Admin / Member)

### Proposed Model: Role Presets + Page Overrides + Data Restrictions

**Base roles (3 presets):**
- **Owner** — full access, billing, delete workspace, transfer ownership
- **Admin** — full access except billing/ownership transfer. Can manage members, integrations, settings.
- **Member** — view all analytics pages, cannot change settings or integrations

**Page-level overrides (per-member toggles):**
- Implemented as a set of **permission flags** on the `workspace_user` pivot, not a separate permissions table
- Flags: `can_access_financials` (COGS, expenses, profit), `can_access_pii` (customer data, order details with names/emails), `can_access_settings` (integrations, workspace config), `can_manage_members` (invite/remove users)
- Admin/Owner roles ignore these flags (always full access)
- Member role defaults to `financials=true, pii=false, settings=false, manage_members=false`
- Admin can override any flag per member

**Navigation behavior:**
- **Hide** nav items and sections the user cannot access (industry standard)
- Backend middleware enforces the same restrictions on every route and API call
- No "upgrade to see this" or lock icons — simply absent from nav

**Why this works:**
- Matches the dominant pattern (Triple Whale feature flags, Shopify section checkboxes, GA4 data restrictions)
- Avoids over-engineering (no custom role builder needed at launch — that's a v2 feature for agencies)
- The financial/PII split covers 90% of real use cases (marketing team sees performance but not profit margins; VA sees orders but not customer PII)
- Simple DB schema: 4 boolean columns on `workspace_user` pivot
