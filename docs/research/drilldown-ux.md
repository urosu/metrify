# Drill-Down UX Patterns Research

Research conducted 2026-04-30. How to display additional/detail data when a user clicks on a row or element.

---

## Recommendation for Nexstage

**Use a right-side overlay drawer (480-560px)** for row detail. No layout shift, no content pushing. Slide in at 200ms, dim background. Close on Escape/scrim/X. Update URL. Reserve modals for actions only. Reserve full-page nav for complex detail workspaces (e.g., full customer profile).

---

## 1. Side Panel / Drawer (RECOMMENDED)

| Tool | Width | Behavior | Close |
|------|-------|----------|-------|
| Linear | ~50-60% | Pushes content left | Esc, click-outside, X |
| Stripe | ~400-480px fixed | Overlays with scrim | Esc, scrim click |
| Shopify Admin | ~480px | Overlays, dims beneath | Esc, X |
| GA4 | Rarely uses drawers | Prefers full-page drill | N/A |

**Best for:** Row detail, order inspection, product drill-down, creative preview.
**Key rules:**
- 400-600px width is standard
- Overlay (no push) is better for analytics because tables are width-sensitive
- Animation: 150-250ms ease-out slide
- URL updates so it's bookmarkable/shareable

## 2. Modal / Dialog

**Use when:** Action is required (confirm delete), content is self-contained and short, user must focus (sharing settings, alert config).
**Never use for:** Detail views that users want to compare with the table — modals block the parent entirely.

## 3. Inline Expansion (Accordion)

**Works when:** Detail is 1-3 rows of supplementary data (sub-metrics, variant breakdown, SKU expansion). Expanded height < 200px.
**Fails when:** Expanded content is tall — pushes rows below out of view, causes disorientation. Also fails with virtualized/paginated tables.
**Good for:** Product → SKU/variant expansion (2-level indent, same columns).

## 4. Full-Page Navigation

**Use when:** Detail view is complex enough to be its own workspace (full customer profile with order history, full product analytics page).
**Always:** Preserve back-navigation and breadcrumbs. Update URL.

## 5. Anti-Patterns to AVOID

- **Content jumping / layout shift** — Never insert content between existing rows if it pushes things down unpredictably. Users lose scroll position and spatial memory.
- **Spawning elements near click point** — Popovers that appear between rows and push content. The element the user clicked moves away from their cursor. Worst pattern.
- **No close affordance** — Every opened panel must close via Escape, explicit button, AND click-outside.
- **No URL update** — If detail is significant, back button should work. Use `pushState`.
- **Blocking scroll on parent** — Side panels should allow parent to remain scrollable (not modals).

## 6. Accessibility Requirements

- **Focus management:** On open → move focus to panel heading or first interactive element. On close → return focus to trigger element.
- **Keyboard:** Escape closes. Tab traps inside modals (not drawers). Arrow keys for row navigation in tables.
- **ARIA:** Drawers use `role="complementary"` or `role="dialog"` with `aria-label`. Modals use `role="dialog"` + `aria-modal="true"`.
- **Screen readers:** Announce panel opening via `aria-live` region or focus move. Visually hidden close button labels.

## 7. Font Size Standards

| Context | Size | Source |
|---------|------|--------|
| WCAG AA minimum | No explicit px minimum, but 4.5:1 contrast for normal text, 3:1 for "large" (18px+ or 14px bold) | W3C |
| Stripe body | 13-14px | Observed |
| Shopify Polaris caption | 13px minimum | Design system docs |
| GA4 dense tables | 12-14px | Observed |
| Industry standard | 16px base for body text (browser default, Material Design) | Various |
| **Nexstage rule** | **14px absolute floor, 15-16px body** | Per user feedback |

Readability degrades below ~14px for data-heavy UIs (multiple studies, Baymard Institute). Use font-weight and color contrast rather than shrinking size to create hierarchy.

---

## Sources

- Linear design system (linear.app)
- Stripe Dashboard UX (stripe.com/docs)
- Shopify Polaris (polaris.shopify.com)
- Nielsen Norman Group font size research
- W3C WCAG 2.1 SC 1.4.3, 1.4.4
