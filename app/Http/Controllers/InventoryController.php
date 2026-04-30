<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inventory page — stock health, sales prediction, and reorder signals per SKU.
 *
 * Thin controller: delegates to InventoryDataService once the service layer is
 * connected. Until then, returns mock prop data so the frontend renders without
 * a DB dependency.
 *
 * Prop shape matches Inventory/Index.tsx exactly:
 *   products, kpis, filters, alert_critical_count
 *
 * Forecast logic (mock): blended velocity forecast.
 *   last_30d_avg  = units_sold_30d / 30
 *   ly_30d_avg    = units_sold_ly / 30  (LY same period)
 *   blended       = (last_30d_avg × 0.6) + (ly_30d_avg × 0.4)
 *   predicted_30d = round(blended × 30 × 1.05)   ← 5 % growth nudge (Stocky default)
 *   confidence    = "high" ≥60d history | "medium" ≥30d | "low" <30d
 *
 * Stock-out date: today + days_of_stock when days_of_stock < 60.
 * Reorder qty: max(0, predicted_30d × 1.2 − current_stock).
 *
 * Competitor patterns absorbed:
 *   - Cogsy: blended 60/40 last-period / LY velocity forecast, variant accordion, run-out date
 *   - Stocky: 5 % growth nudge, "low confidence" chip when history < 30d
 *   - Shopify Native: "Days of inventory remaining" column label + formula
 *   - Glew: "Predicted demand" KPI in header strip
 *   - Bloom Analytics: inventory value at cost KPI
 *   - Triple Whale Lighthouse: alert banner when any SKU < 7d
 *
 * Reads: (future) InventoryDataService::kpis(), InventoryDataService::products()
 * Writes: nothing
 * Called by: GET /{workspace:slug}/inventory → index()
 *
 * @see docs/pages/inventory.md
 * @see docs/competitors/_research_inventory_prediction.md
 * @see docs/planning/backend.md#InventoryDataService
 */
class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $params   = $this->validateParams($request);
        $products = $this->mockProducts();
        $kpis     = $this->computeKpis($products);

        $criticalCount = count(array_filter(
            $products,
            fn (array $p) => $p['stock_health'] === 'critical',
        ));

        return Inertia::render('Inventory/Index', [
            'products'             => $products,
            'kpis'                 => $kpis,
            'filters'              => $params,
            'alert_critical_count' => $criticalCount,
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function validateParams(Request $request): array
    {
        $v = $request->validate([
            'category'     => ['sometimes', 'nullable', 'string', 'max:80'],
            'vendor'       => ['sometimes', 'nullable', 'string', 'max:80'],
            'stock_health' => ['sometimes', 'nullable', 'in:healthy,low,critical,out_of_stock'],
            'has_forecast' => ['sometimes', 'nullable', 'boolean'],
        ]);

        return [
            'category'     => $v['category']     ?? null,
            'vendor'       => $v['vendor']        ?? null,
            'stock_health' => $v['stock_health']  ?? null,
            'has_forecast' => isset($v['has_forecast']) ? (bool) $v['has_forecast'] : null,
        ];
    }

    /**
     * Derive KPI strip values from the products array.
     * In production these will come from InventoryDataService::kpis().
     *
     * @param  array<int, array<string, mixed>> $products
     * @return array<string, mixed>
     */
    private function computeKpis(array $products): array
    {
        $totalSkus      = count($products);
        $activeSkus     = count(array_filter($products, fn ($p) => $p['status'] === 'active'));
        $outOfStock     = count(array_filter($products, fn ($p) => $p['current_stock'] === 0));
        $atRisk         = count(array_filter($products, fn ($p) => $p['stock_health'] === 'critical' || $p['stock_health'] === 'low'));
        $predictedUnits = array_sum(array_column($products, 'predicted_next_30d'));
        $invValue       = array_sum(array_map(
            fn ($p) => ($p['current_stock'] ?? 0) * ($p['cogs_per_unit'] ?? 0),
            $products,
        ));

        // Turnover rate: (units_sold_30d × 12) / avg_stock_qty (annualised).
        $totalSold30d  = array_sum(array_column($products, 'sold_last_30d'));
        $avgStock      = $totalSkus > 0 ? array_sum(array_column($products, 'current_stock')) / $totalSkus : 0;
        $turnoverRate  = $avgStock > 0 ? round($totalSold30d * 12 / $avgStock, 2) : null;

        return [
            'total_skus'          => $totalSkus,
            'active_skus'         => $activeSkus,
            'out_of_stock'        => $outOfStock,
            'at_risk'             => $atRisk,
            'predicted_units_30d' => round((float) $predictedUnits, 0),
            'inventory_value'     => round($invValue, 2),
            'turnover_rate'       => $turnoverRate,
        ];
    }

    /**
     * 50 mock products covering the full health spectrum:
     *   35 healthy (≥30d), 8 low (7–29d), 5 critical (<7d), 2 out-of-stock.
     * ~30 % have variants (3–6 each).
     *
     * Prediction formula (blended):
     *   predicted_30d = round((sold_30d × 0.6 + sold_ly × 0.4) / 30 × 30 × 1.05)
     *                 = round((sold_30d × 0.6 + sold_ly × 0.4) × 1.05)
     *
     * @return array<int, array<string, mixed>>
     */
    private function mockProducts(): array
    {
        $today = now()->toDateString();

        // Helper: compute blended forecast
        $forecast = function (int $sold30d, ?int $soldLy): array {
            $last = $sold30d;
            $ly   = $soldLy ?? $sold30d;  // fallback to self when LY absent
            $blended = ($last * 0.6) + ($ly * 0.4);
            $predicted = (int) round($blended * 1.05);
            $confidence = $soldLy !== null ? 'high' : 'medium';
            return ['predicted' => $predicted, 'confidence' => $confidence];
        };

        // Helper: stock health chip
        $health = function (int $stock, ?float $daysOfStock): string {
            if ($stock === 0)                          return 'out_of_stock';
            if ($daysOfStock === null)                 return 'healthy';       // zero velocity → ∞
            if ($daysOfStock < 7)                      return 'critical';
            if ($daysOfStock < 30)                     return 'low';
            if ($daysOfStock > 90)                     return 'overstocked';
            return 'healthy';
        };

        // Helper: stockout date only when days < 60
        $stockoutDate = function (float $days) use ($today): ?string {
            if ($days >= 60) return null;
            return now()->addDays((int) round($days))->toDateString();
        };

        // Helper: reorder qty
        $reorder = function (int $stock, int $predicted): ?int {
            $needed = (int) ceil($predicted * 1.2) - $stock;
            return $needed > 0 ? $needed : null;
        };

        // Helper: variant rows
        $makeVariants = function (string $baseSku, int $stockTotal, int $sold30d, int $soldLy, int $count) use ($forecast, $health, $stockoutDate, $reorder): array {
            $sizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $colors = ['Navy', 'Black', 'Olive', 'Slate', 'Cream'];
            $variants = [];
            $perStock = (int) round($stockTotal / $count);
            $perSold  = (int) round($sold30d / $count);
            $perLy    = (int) round($soldLy / $count);

            for ($i = 0; $i < $count; $i++) {
                $s      = $sizes[$i % count($sizes)];
                $c      = $colors[$i % count($colors)];
                $vstock = $perStock + rand(-2, 4);
                $vsold  = max(1, $perSold + rand(-3, 3));
                $vly    = max(1, $perLy + rand(-2, 2));
                $f      = $forecast($vsold, $vly);
                $days   = $vstock > 0 && $vsold > 0 ? round($vstock / ($vsold / 30), 1) : null;

                $variants[] = [
                    'id'                  => "{$baseSku}-{$s}",
                    'sku'                 => "{$baseSku}-{$s}-{$c[0]}",
                    'label'               => "{$s} / {$c}",
                    'current_stock'       => $vstock,
                    'days_of_stock'       => $days,
                    'stock_health'        => $health($vstock, $days),
                    'sold_last_30d'       => $vsold,
                    'sold_ly'             => $vly,
                    'predicted_next_30d'  => $f['predicted'],
                    'confidence'          => $f['confidence'],
                    'predicted_stockout'  => $days !== null ? $stockoutDate($days) : null,
                    'reorder_qty'         => $reorder($vstock, $f['predicted']),
                ];
            }
            return $variants;
        };

        // ── Product definitions ─────────────────────────────────────────────
        // Format: [name, sku, category, vendor, stock, sold30d, soldLy, cogs, price, status, variantCount(0=none)]
        $defs = [
            // ── Critical (<7d) ─────────────────────────────────────────────
            ['Merino Wool Crewneck',        'SHIRT-MW-CRW-NVY', 'Tops',       'WoolCo',    38,  312, 298, 22.50, 78.00, 'active', 4],
            ['Trail Running Shorts',        'BOT-TR-SHR-BLK',   'Bottoms',    'ActiveGear',  9,  155, 141, 14.00, 55.00, 'active', 3],
            ['Recycled Puffer Vest',        'OUT-RPV-OLV',      'Outerwear',  'EcoWear',    12,  189, 165, 31.00, 99.00, 'active', 0],
            ['Compression Running Tights',  'BOT-CRT-SLT',      'Bottoms',    'ActiveGear', 15,  210, 195, 18.00, 68.00, 'active', 3],
            ['Alpine Softshell Jacket',     'OUT-ASJ-GRY',      'Outerwear',  'MountainCo',  8,  130, 120, 58.00, 189.00,'active', 0],

            // ── Low (7–29d) ─────────────────────────────────────────────────
            ['Organic Cotton Henley',       'SHIRT-OC-HEN-WHT', 'Tops',       'OrganicCo', 95,  189, 172, 16.00, 64.00, 'active', 4],
            ['Bamboo Crew Socks 3-Pack',    'ACC-BCS-3PK',      'Accessories','BambooLife', 62,   98, 89,   4.50, 24.00, 'active', 0],
            ['Sherpa Lined Hoodie',         'SHIRT-SLH-CHR',    'Tops',       'CozyBrand', 110,  234, 210, 24.00, 85.00, 'active', 5],
            ['Waterproof Cycling Jacket',   'OUT-WCJ-RED',      'Outerwear',  'CyclePro',   75,  145, 132, 45.00, 145.00,'active', 3],
            ['Yoga Block Set',              'ACC-YBS-PRP',      'Accessories','YogaGear',   48,   72, 66,   6.00, 28.00, 'active', 0],
            ['Slim Chino Trousers',         'BOT-SCT-KHK',      'Bottoms',    'UrbanFit',   88,  178, 160, 19.00, 72.00, 'active', 5],
            ['Performance Polo Shirt',      'SHIRT-PPS-WHT',    'Tops',       'ActiveGear', 60,  112, 100, 17.00, 59.00, 'active', 3],

            // ── Healthy (≥30d) ────────────────────────────────────────────
            ['Fleece Quarter Zip',          'SHIRT-FQZ-NVY',    'Tops',       'CozyBrand', 350,  180, 162, 20.00, 69.00, 'active', 4],
            ['Cargo Jogger Pants',          'BOT-CJP-OLV',      'Bottoms',    'UrbanFit',  280,  120, 110, 21.00, 74.00, 'active', 4],
            ['Merino Base Layer Top',       'SHIRT-MBL-GRY',    'Tops',       'WoolCo',    410,  155, 140, 25.00, 89.00, 'active', 5],
            ['Insulated Water Bottle 32oz', 'ACC-IWB-SLV',      'Accessories','HydraGear', 520,  210, 195, 8.00,  35.00, 'active', 0],
            ['Packable Rain Jacket',        'OUT-PRJ-YLW',      'Outerwear',  'MountainCo',240,   88, 80,  35.00, 119.00,'active', 0],
            ['Running Bib Shorts',          'BOT-RBS-BLK',      'Bottoms',    'CyclePro',  190,   66, 60,  22.00, 79.00, 'active', 3],
            ['Hiking Gaiters',              'ACC-HGA-GRN',      'Accessories','MountainCo',380,   45, 40,  12.00, 42.00, 'active', 0],
            ['Cotton Canvas Tote',          'ACC-CCT-NAT',      'Accessories','EcoWear',   600,  300, 270,  3.50, 22.00, 'active', 0],
            ['Oversized Linen Shirt',       'SHIRT-OLS-SAG',    'Tops',       'OrganicCo', 315,  135, 122, 14.00, 52.00, 'active', 5],
            ['Wide Leg Linen Pants',        'BOT-WLP-WHT',      'Bottoms',    'OrganicCo', 265,   98, 89,  18.00, 68.00, 'active', 4],
            ['Lightweight Down Puffer',     'OUT-LDP-BLK',      'Outerwear',  'MountainCo',180,   72, 65,  48.00, 159.00,'active', 0],
            ['Ribbed Tank Top',             'SHIRT-RTT-WHT',    'Tops',       'ActiveGear',490,  245, 220, 8.00,  29.00, 'active', 5],
            ['Crossbody Waist Pack',        'ACC-CWP-BLK',      'Accessories','UrbanFit',  220,   90, 82,  15.00, 48.00, 'active', 0],
            ['Merino Beanie',               'ACC-MB-CHR',       'Accessories','WoolCo',    340,  140, 126, 10.00, 38.00, 'active', 0],
            ['Windproof Cycling Gloves',    'ACC-WCG-BLK',      'Accessories','CyclePro',  210,   65, 59,  11.00, 39.00, 'active', 0],
            ['Stretch Denim Jeans',         'BOT-SDJ-IND',      'Bottoms',    'UrbanFit',  330,  145, 132, 24.00, 88.00, 'active', 5],
            ['French Terry Sweatshirt',     'SHIRT-FTS-GRY',    'Tops',       'CozyBrand', 275,  118, 108, 17.00, 62.00, 'active', 4],
            ['Padded Cycling Shorts',       'BOT-PCS-BLK',      'Bottoms',    'CyclePro',  155,   58, 52,  26.00, 85.00, 'active', 3],
            ['Waterproof Hiking Boots',     'ACC-WHB-BRN',      'Footwear',   'MountainCo',180,   72, 65,  55.00, 179.00,'active', 0],
            ['Slip-On Canvas Sneaker',      'ACC-SCS-WHT',      'Footwear',   'UrbanFit',  240,  105, 95,  22.00, 75.00, 'active', 5],
            ['Thermal Running Jacket',      'OUT-TRJ-BLU',      'Outerwear',  'ActiveGear',200,   80, 72,  38.00, 129.00,'active', 3],
            ['Cotton Athletic Socks 6-Pack','ACC-CAS-6PK',      'Accessories','ActiveGear',720,  360, 324,  3.00, 18.00, 'active', 0],
            ['Lightweight Hiking Pants',    'BOT-LHP-KHK',      'Bottoms',    'MountainCo',310,  120, 110, 23.00, 82.00, 'active', 4],
            ['Crop Active Top',             'SHIRT-CAT-BLK',    'Tops',       'ActiveGear',380,  185, 168, 9.00,  35.00, 'active', 5],
            ['Long Sleeve UV Shirt',        'SHIRT-LSU-WHT',    'Tops',       'ActiveGear',290,  115, 104, 12.00, 44.00, 'active', 4],
            ['Insulated Ski Jacket',        'OUT-ISJ-RED',      'Outerwear',  'MountainCo',145,   55, 50,  72.00, 229.00,'active', 0],
            ['Running Vest',                'ACC-RV-GRY',       'Accessories','ActiveGear',195,   72, 65,  18.00, 58.00, 'active', 0],
            ['Gym Duffel Bag',              'ACC-GDB-BLK',      'Accessories','ActiveGear',160,   60, 54,  22.00, 72.00, 'active', 0],
            ['Mesh Training Shorts',        'BOT-MTS-BLU',      'Bottoms',    'ActiveGear',320,  140, 126,  9.00, 35.00, 'active', 3],
            ['Seamless Sports Bra',         'SHIRT-SSB-BLK',    'Tops',       'ActiveGear',410,  200, 180, 10.00, 38.00, 'active', 5],
            ['Cycling Bib Tights',          'BOT-CBT-BLK',      'Bottoms',    'CyclePro',  120,   44, 40,  34.00, 109.00,'active', 3],
            ['Wool Cycling Jersey',         'SHIRT-WCJ-BLU',    'Tops',       'CyclePro',  130,   48, 43,  42.00, 135.00,'active', 3],

            // ── Out of stock (2) ─────────────────────────────────────────────
            ['Thermal Base Layer Bottom',   'BOT-TBL-BLK',      'Bottoms',    'WoolCo',      0,  165, 150, 20.00, 64.00, 'active', 4],
            ['Reflective Safety Vest',      'ACC-RSV-ORG',      'Accessories','CyclePro',    0,   52, 47,   7.00, 28.00, 'active', 0],

            // ── Archived (inactive) ─────────────────────────────────────────
            ['V-Neck Bamboo Tee',           'SHIRT-VNB-WHT',    'Tops',       'BambooLife',  0,    0, 0,    9.00, 34.00, 'inactive',0],
        ];

        $products = [];
        foreach ($defs as $i => $def) {
            [
                $name, $sku, $category, $vendor,
                $stock, $sold30d, $soldLy,
                $cogs, $price, $status, $variantCount
            ] = $def;

            $daysOfStock = ($stock > 0 && $sold30d > 0)
                ? round($stock / ($sold30d / 30), 1)
                : ($stock > 0 ? null : 0.0);  // null = ∞ (zero velocity), 0 = OOS

            $f = $forecast($sold30d, $soldLy > 0 ? $soldLy : null);

            $h        = $health($stock, $daysOfStock);
            $soDate   = ($daysOfStock !== null && $daysOfStock > 0) ? $stockoutDate($daysOfStock) : null;
            $reorderQ = ($h === 'critical' || $h === 'low' || $h === 'out_of_stock')
                ? $reorder($stock, $f['predicted'])
                : null;

            $variants = $variantCount > 0
                ? $makeVariants($sku, $stock, $sold30d, $soldLy, $variantCount)
                : [];

            $products[] = [
                'id'                  => $i + 1,
                'name'                => $name,
                'sku'                 => $sku,
                'category'            => $category,
                'vendor'              => $vendor,
                'status'              => $status,
                'thumbnail_url'       => null,
                'variant_count'       => $variantCount,
                'variants'            => $variants,
                'current_stock'       => $stock,
                'days_of_stock'       => $daysOfStock,
                'stock_health'        => $h,
                'sold_last_30d'       => $sold30d,
                'sold_ly'             => $soldLy > 0 ? $soldLy : null,
                'predicted_next_30d'  => $f['predicted'],
                'confidence'          => $f['confidence'],
                'predicted_stockout'  => $soDate,
                'reorder_qty'         => $reorderQ,
                'cogs_per_unit'       => $cogs,
                'price'               => $price,
                'last_synced'         => now()->subMinutes(rand(5, 120))->toISOString(),
            ];
        }

        return $products;
    }
}
