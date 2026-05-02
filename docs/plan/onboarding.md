# Nexstage Onboarding Flow

Simple, fast, no fluff. App needs to work first — guides and polish come later.

---

## Principles
1. **Store connection is mandatory** — billing trigger + minimum for useful data
2. **Everything else is optional** — ad accounts, COGS, GA4, GSC can happen later
3. **14-day free trial, no credit card**
4. **Progress screen during import** — works in background, redirect to dashboard when done
5. **No sample data, no demo stores, no guided tours** — just connect and go

---

## Billing
**€39/month OR 0.4% of full store revenue, whichever is higher.**
Revenue detected from connected store. Multi-store: summed. Billing via own website (Stripe). Free connector in Shopify App Store.

## Trial
**14-day trial, no credit card. Import limited to last 1 month of history.**
- All features unlocked — no feature gating
- 1-month history limit prevents abuse (recreating account still gives only 1 month)
- After trial: syncs stop, dashboard accessible with frozen 1-month data, "Upgrade" banner.
- **Subscription cancellation** (was paying): access through billing period end → 30-day read-only grace period with full historical data + "Resubscribe" banner → after 30 days: complete lockout (login shows resubscribe + export page) → after 90 days: data deletion with advance email warnings.
- Upgrade (trial or cancelled): full history backfill + syncing resumes.
- Zero cost for inactive subscriptions (no API calls, no jobs).
- Implementation: `syncs_paused_at` set on expiry/cancel. Trial: `DateRange` enforces `end <= syncs_paused_at`. Cancelled subscriber: full data access during 30-day grace, then lockout. See non-obvious-issues.md #100.

---

## Flow
### Step 1: Signup
- Email + password (or Google OAuth)
- Create workspace: name + reporting currency + timezone

### Step 2: Connect Store (mandatory)
- Two buttons: "Connect Shopify" / "Connect WooCommerce"
- **Shopify:** OAuth app install → redirects to Shopify → approve → back to Nexstage
- **WooCommerce (v1):** Enter store URL → paste consumer key + secret (manual entry). WP plugin option in v2.

### Step 3: Import Progress Screen
- Full-screen progress view while import runs in background
- Live counter: "Importing orders: 18,420 of 24,100 (76%)"
- Separate progress bars for: Orders, Products, Customers
- Runs in background — user can close browser and come back
- **When complete: auto-redirect to Dashboard with real data**

### Step 4: Dashboard with Getting Started Checklist
User lands on their real dashboard. Persistent checklist panel shows optional next steps:

```
Getting Started (2 of 8 complete)
──────────────────────────────
✅ Create workspace
✅ Connect store
☐ Connect Facebook Ads
☐ Connect Google Ads
☐ Connect Google Analytics (GA4)
☐ Connect Google Search Console
☐ Connect Klaviyo (email marketing)
☐ Set up product costs (COGS)
```

Each item: one-click OAuth or one link to settings page. All optional, any order, any time. Checklist dismissible after completion.

---

## Empty States

Every empty section has ONE sentence + ONE button. No illustrations, no paragraphs. Full list → coding-spec.md section 20.

---

## What NOT to Do
- No guided tours or tooltips at MVP
- No screenshot walkthroughs
- No mandatory team invites
- No auto-upgrade without explicit confirmation

---

## Post-Onboarding Retention

1. **COGS prompt at day 3** — if not set: inline alert "You have €12,430 in orders but can't show profit yet. [Add costs →]"
2. **Weekly recap email** — automated: "Your week: €X revenue (+Y% vs last week)"
3. **Integration disconnect alerts** — if OAuth token expires: alert immediately

---

## Technical Notes

Shopify OAuth scopes, webhooks, bulk import, and rate limits → see `integrations.md` section 1.
WooCommerce connection steps → see `integrations.md` section 2.
Import progress API → see `coding-spec.md` section 44 (API contracts).
**Test store available** (owner's Shopify store connected via OAuth).
