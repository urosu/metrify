# Shipping Cost per Country & Returns Analysis Research

Research conducted 2026-04-30.

---

## How competitors handle shipping-by-country

| Competitor | Shipping by country? | Returns by country? | COD tracking? | Free shipping analysis? |
|-----------|---------------------|--------------------|--------------|-----------------------|
| BeProfit | Yes — "Compare Countries" view with profit per country, shipping/taxes/fees as separate columns | No | No | No |
| TrueProfit | Yes — auto-syncs from ShipStation/ShipBob. Country drill-down in profit dashboard | No | No | No |
| Conjura | Yes — "true landed cost" at product level via ShipStation connector | No | No | No |
| Bloom Analytics | No specific feature found | No | No | No |

### Key gaps (Nexstage opportunities)

1. **No competitor shows return rates per country alongside shipping costs.** This is critical for EU markets where return rates vary wildly (e.g., Germany ~30% vs Spain ~10%).
2. **COD (Cash on Delivery) cost tracking** — zero competitors surface COD penetration %, COD failure rate, or COD cost per order. This is a genuine gap for southern/eastern EU markets:
   - Italy: ~4-11% of orders paid via COD (CORRECTED — previously said 40-55% which confused "stores offering COD" with actual usage)
   - Greece: ~25-63% COD depending on sector (wide range, uncertain)
   - Romania: ~60-65% COD (confirmed by ARMO)
   - Turkey: 55-70% COD (unverified, likely high but exact range uncertain)
3. **Free shipping threshold analysis** — no tool offers a what-if simulator for modeling "free shipping at $X" profitability impact. Shopify lets merchants set thresholds but shows zero P&L impact.

---

## Recommended Nexstage implementation

### Shipping & Country Analysis table

Sortable rows per country with columns:
- Orders
- Revenue
- Avg shipping charged to customer
- Avg actual carrier cost
- Shipping delta (charged vs cost — highlighted when negative/losing money)
- Return rate %
- COD % (where applicable)
- COD failure rate %
- Contribution margin after shipping
- Status chip: Profitable / Marginal / Loss

### What-if simulator panel (below table)

4 sliders that recompute the contribution margin column live:
- Free shipping threshold ($0 / $30 / $50 / $75 / $100 / custom)
- Free returns toggle (on/off)
- COD surcharge adjustment
- Carrier cost adjustment %

Shows projected impact: "Setting free shipping at $50 would increase orders by ~X% but reduce margin by ~Y%" (based on historical AOV distribution).

---

## Sources

- BeProfit country comparison (beprofit.co)
- TrueProfit shipping sync (trueprofit.io/solutions/profit-dashboard)
- Conjura landed cost (conjura.com/product-table-dashboard)
- EU COD penetration rates (Statista, eCommerce Europe reports)
