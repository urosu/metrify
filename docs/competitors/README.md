# Competitor Research

Reference material about how other ecommerce analytics tools work. Read these only when building a specific feature and you need to understand how competitors approach it.

## Quick navigation

| Need | Where to look |
|------|--------------|
| UI patterns to use/avoid | `patterns.md` — 189 named patterns with adopt/avoid recommendations |
| Metric naming conventions | `crosscuts/metric-dictionary.md` — 85 metrics across 18 tools |
| UX copy guidelines | `crosscuts/ux-copy.md` — 60 UX copy contexts |
| Master competitor list | `index.md` — 41 competitors with tier/positioning/pricing |
| Feature comparison | `feature-index.md` — 33 features mapped across competitors |
| Specific competitor | `profiles/<name>.md` — 41 detailed profiles |
| Screen-by-screen analysis | `teardowns/<name>.md` — Triple Whale, Northbeam, Polar, Peel, Fairing |
| Cross-competitor topics | `crosscuts/<topic>.md` — pricing, onboarding, mobile, multistore, export, UX copy |
| Non-ecommerce UX refs | `inspiration/<name>.md` — Stripe, Linear, Vercel, Plausible, GA4 |

## Directory structure

```
competitors/
├── index.md            — master list of 41 competitors
├── feature-index.md    — 33 features mapped to competitors
├── patterns.md         — 189 UI patterns (adopt/avoid)
├── profiles/           — per-competitor deep dives (41 files)
├── features/           — per-feature analysis across competitors (33 files)
├── teardowns/          — screen-by-screen UI analysis (5 files)
├── crosscuts/          — cross-competitor topic analysis (7 files)
└── inspiration/        — non-ecommerce UX references (5 files)
```

## When to read what

- **Designing a page?** Start with `patterns.md` for UI patterns, then check `features/<relevant-feature>.md` for how competitors handle that specific feature.
- **Naming a metric?** Check `crosscuts/metric-dictionary.md` for industry-standard naming.
- **Writing UX copy?** Check `crosscuts/ux-copy.md` for tone and phrasing conventions.
- **Need deep competitor context?** Read the specific `profiles/<name>.md` file.
- **Need pixel-level UI detail?** Check `teardowns/<name>.md` for screen-by-screen breakdowns.
