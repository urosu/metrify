# Winners / Losers Research — Where the Framing Adds Value

## Research queries
- Winners and losers product report Lifetimely
- Top movers product analytics
- Top vs bottom performers ad accounts
- Where this framing is overused vs where it adds value

---

## Lifetimely — Winners and losers product report

Lifetimely surfaces a dedicated "Winners / Losers" view in their Products module. Key characteristics:
- Compares products by margin (contribution margin after COGS + variable costs) over the selected period vs. prior period
- "Winners" = products whose margin improved; "Losers" = products whose margin declined
- Requires COGS to be configured — the view is locked behind cost data (shows an empty-state nudge otherwise)
- Used as a quick executive summary of *which products changed* — not a default view but an optional lens

Verdict: **Winner/Loser framing is valid for Products when COGS is known**, because it answers a specific question ("what changed in profitability?") rather than just ranking by revenue.

## Top movers — product analytics (Triple Whale, Northbeam)

Both tools have a "Top Movers" section that shows the largest absolute or percentage change in revenue, ROAS, or spend vs. the prior period. They deliberately avoid "Winners / Losers" language and instead use:
- "Top movers" / "Biggest changes"
- "Trending up" / "Trending down"
- "Scaling" / "Declining"

The language is movement-neutral — it doesn't editorialize whether a change is good or bad because context matters (scaling a ROAS-positive campaign = good; a product declining in CAC because it's mature = also good).

Key pattern: the framing is **relative change** within a specific dimension (revenue, ROAS, ad spend) — not an absolute "this product is good / bad".

## Top vs bottom performers — ad accounts (Atria, Wicked Reports)

Atria explicitly uses three columns: **Winners / Iteration Potential / Candidates** for ads within a campaign. This is well-defined because:
- Winners = ROAS above target threshold (configurable)
- Iteration Potential = running but below threshold; worth testing variants
- Candidates = paused or low-spend; potential to activate

This three-bucket model is more informative than a binary Winners/Losers for ads because it explains *what action to take*.

Wicked Reports uses "Top performers / Bottom performers" for ad accounts — percentage terminology, not winner/loser binary.

## Where the framing is overused

### Dashboard
Inappropriate — the dashboard is a health summary across all metrics. Labeling some channels as "winners" and others as "losers" implies a zero-sum competition that doesn't match the analytics task. Better: "Top channels by revenue" or "Highest ROAS channels".

### SEO / queries
Inappropriate for the position-movers section — "Biggest losers" for position drop is editorially negative. A query that dropped from position 3 to 4 on a high-volume keyword is not a "loser"; it's a mover that needs attention. Better: "Biggest gainers" / "Biggest drops" or "Position improved" / "Position declined".

### Customers
Inappropriate — customers are not "winners" or "losers". Better: "Top customers by LTV", "At-risk customers" (churn risk), "High-value segment".

### Stores (multi-store view)
Borderline — comparing stores by marketing efficiency (marketing_pct) as Winners/Losers is marginal. A store that spends more on ads is not a "loser" if it's in growth phase vs. a mature store. Better: "High efficiency" / "Scaling" or simply filter by metric value.

## Recommended standard across Nexstage

| Surface           | Keep W/L framing?  | Replacement if dropped        |
|-------------------|--------------------|-------------------------------|
| Products (/store) | YES — COGS-gated   | Keep as-is (requires COGS)    |
| Ads (/ads)        | YES — ROAS-gated   | Keep; three buckets is clearer|
| SEO movers        | NO                 | "Biggest gains" / "Biggest drops" |
| Dashboard         | NO                 | No ranking section on dashboard |
| Stores list       | NO                 | Remove W/L chip filter; keep sorting |
| Customers         | N/A — not used     | "Top by LTV", "At-risk"       |
