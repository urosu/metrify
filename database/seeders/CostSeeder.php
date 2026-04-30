<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds cost configuration tables so the Profit page renders cost/margin data.
 *
 * Tables populated:
 *   store_cost_settings  — shipping mode + completeness score per store
 *   shipping_rules       — weight-tiered shipping costs (DE/AT/CH only)
 *   transaction_fee_rules — Stripe 2.9%+€0.30, PayPal 3.49%+€0.49
 *   platform_fee_rules   — WooCommerce hosting + plugin subscriptions
 *   tax_rules            — EU VAT + US sales tax seeded rows
 *   opex_allocations     — team salary, tooling, office (monthly)
 *
 * These are "seed — verify against your contract" values; the Seeded chip
 * in Settings → Costs reminds users to confirm numbers.
 *
 * @see docs/planning/schema.md §1.6 cost tables
 */
class CostSeeder extends Seeder
{
    // EU/UK VAT rates (standard + reduced) in basis points. 100 bps = 1%.
    private const TAX_RULES = [
        ['country' => 'DE', 'std' => 1900, 'reduced' => 700,  'digital' => 1900, 'in_price' => true],
        ['country' => 'AT', 'std' => 2000, 'reduced' => 1000, 'digital' => 2000, 'in_price' => true],
        ['country' => 'CH', 'std' => 810,  'reduced' => 260,  'digital' => 810,  'in_price' => true],
        ['country' => 'PL', 'std' => 2300, 'reduced' => 800,  'digital' => 2300, 'in_price' => true],
        ['country' => 'NL', 'std' => 2100, 'reduced' => 900,  'digital' => 2100, 'in_price' => true],
        ['country' => 'FR', 'std' => 2000, 'reduced' => 550,  'digital' => 2000, 'in_price' => true],
        ['country' => 'GB', 'std' => 2000, 'reduced' => 500,  'digital' => 2000, 'in_price' => true],
        ['country' => 'IT', 'std' => 2200, 'reduced' => 1000, 'digital' => 2200, 'in_price' => true],
        ['country' => 'US', 'std' => 800,  'reduced' => 0,    'digital' => 800,  'in_price' => false],
    ];

    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        $stores    = Store::where('workspace_id', $workspace->id)->get();
        $now       = now()->toDateTimeString();

        // ── store_cost_settings ────────────────────────────────────────────────
        foreach ($stores as $store) {
            DB::table('store_cost_settings')->insertOrIgnore([
                'workspace_id'              => $workspace->id,
                'store_id'                  => $store->id,
                'shipping_mode'             => 'flat_rate',
                'shipping_flat_rate_native' => match ($store->currency) {
                    'EUR' => 6.90,
                    'GBP' => 5.99,
                    'USD' => 7.99,
                    default => 6.90,
                },
                'shipping_per_order_native' => null,
                'default_currency'          => $store->currency,
                'completeness_score'        => 78,
                'last_recalculated_at'      => now()->subHours(2)->toDateTimeString(),
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
        }

        // Workspace-wide default cost settings (store_id = NULL)
        DB::table('store_cost_settings')->insertOrIgnore([
            'workspace_id'              => $workspace->id,
            'store_id'                  => null,
            'shipping_mode'             => 'flat_rate',
            'shipping_flat_rate_native' => 6.90,
            'shipping_per_order_native' => null,
            'default_currency'          => 'EUR',
            'completeness_score'        => 72,
            'last_recalculated_at'      => now()->subHours(3)->toDateTimeString(),
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);

        // ── shipping_rules (DE store weight-tiered) ────────────────────────────
        $deStore = $stores->firstWhere('currency', 'EUR');
        if ($deStore) {
            $shippingRules = [
                ['min' => 0,     'max' => 500,   'country' => 'DE',   'cost' => 4.90],
                ['min' => 0,     'max' => 500,   'country' => 'AT',   'cost' => 6.90],
                ['min' => 0,     'max' => 500,   'country' => null,   'cost' => 8.90],
                ['min' => 501,   'max' => 2000,  'country' => 'DE',   'cost' => 5.90],
                ['min' => 501,   'max' => 2000,  'country' => 'AT',   'cost' => 8.90],
                ['min' => 501,   'max' => 2000,  'country' => null,   'cost' => 10.90],
                ['min' => 2001,  'max' => 5000,  'country' => 'DE',   'cost' => 7.90],
                ['min' => 2001,  'max' => 5000,  'country' => null,   'cost' => 13.90],
                ['min' => 5001,  'max' => 20000, 'country' => 'DE',   'cost' => 11.90],
                ['min' => 5001,  'max' => 20000, 'country' => null,   'cost' => 19.90],
            ];
            foreach ($shippingRules as $r) {
                DB::table('shipping_rules')->insertOrIgnore([
                    'workspace_id'       => $workspace->id,
                    'store_id'           => $deStore->id,
                    'min_weight_grams'   => $r['min'],
                    'max_weight_grams'   => $r['max'],
                    'destination_country' => $r['country'],
                    'cost_native'        => $r['cost'],
                    'currency'           => 'EUR',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        // ── transaction_fee_rules ──────────────────────────────────────────────
        foreach ($stores as $store) {
            // Stripe: 2.9% + 0.30 native currency (marked as seeded — users should verify)
            DB::table('transaction_fee_rules')->insertOrIgnore([
                'workspace_id'                 => $workspace->id,
                'store_id'                     => $store->id,
                'processor'                    => 'stripe',
                'percentage_bps'               => 290,  // 2.90%
                'fixed_fee_native'             => match ($store->currency) {
                    'EUR' => 0.30, 'GBP' => 0.25, 'USD' => 0.30, default => 0.30,
                },
                'currency'                     => $store->currency,
                'applies_to_payment_method'    => 'stripe',
                'is_seeded'                    => true,
                'created_at'                   => $now,
                'updated_at'                   => $now,
            ]);

            // PayPal: 3.49% + 0.49 EUR
            DB::table('transaction_fee_rules')->insertOrIgnore([
                'workspace_id'                 => $workspace->id,
                'store_id'                     => $store->id,
                'processor'                    => 'paypal',
                'percentage_bps'               => 349,  // 3.49%
                'fixed_fee_native'             => match ($store->currency) {
                    'EUR' => 0.49, 'GBP' => 0.39, 'USD' => 0.49, default => 0.49,
                },
                'currency'                     => $store->currency,
                'applies_to_payment_method'    => 'paypal',
                'is_seeded'                    => true,
                'created_at'                   => $now,
                'updated_at'                   => $now,
            ]);
        }

        // ── platform_fee_rules ─────────────────────────────────────────────────
        // WooCommerce subscriptions + hosting (workspace-level, not per-store)
        $platformFees = [
            ['label' => 'WooCommerce Hosting (Cloudways)',  'cost' => 80.00,  'mode' => 'per_day'],
            ['label' => 'WooCommerce Extensions Bundle',    'cost' => 49.00,  'mode' => 'per_day'],
            ['label' => 'Klaviyo Email Platform',           'cost' => 150.00, 'mode' => 'per_day'],
            ['label' => 'Loox Reviews App',                 'cost' => 29.00,  'mode' => 'per_day'],
        ];

        $effectiveFrom = now()->subMonths(6)->startOfMonth()->toDateString();
        foreach ($platformFees as $fee) {
            DB::table('platform_fee_rules')->insertOrIgnore([
                'workspace_id'      => $workspace->id,
                'store_id'          => null,
                'item_label'        => $fee['label'],
                'monthly_cost_native' => $fee['cost'],
                'currency'          => 'EUR',
                'allocation_mode'   => $fee['mode'],
                'effective_from'    => $effectiveFrom,
                'effective_to'      => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }

        // ── tax_rules ──────────────────────────────────────────────────────────
        // Global seed rows (workspace_id = NULL)
        foreach (self::TAX_RULES as $rule) {
            DB::table('tax_rules')->insertOrIgnore([
                'workspace_id'                => null,
                'country_code'                => $rule['country'],
                'standard_rate_bps'           => $rule['std'],
                'reduced_rate_bps'            => $rule['reduced'] ?: null,
                'is_included_in_price'        => $rule['in_price'],
                'digital_goods_override_bps'  => $rule['digital'] ?: null,
                'is_seeded'                   => true,
                'created_at'                  => $now,
                'updated_at'                  => $now,
            ]);
        }

        // ── opex_allocations ───────────────────────────────────────────────────
        $opexItems = [
            ['category' => 'Team salaries',           'cost' => 8500.00, 'mode' => 'per_day'],
            ['category' => 'Office & coworking',      'cost' => 1200.00, 'mode' => 'per_day'],
            ['category' => 'SaaS tools (non-ecomm)',  'cost' => 350.00,  'mode' => 'per_day'],
            ['category' => 'Logistics & fulfilment',  'cost' => 2200.00, 'mode' => 'per_order'],
            ['category' => 'Marketing headcount',     'cost' => 3000.00, 'mode' => 'per_day'],
        ];

        foreach ($opexItems as $item) {
            DB::table('opex_allocations')->insertOrIgnore([
                'workspace_id'         => $workspace->id,
                'category'             => $item['category'],
                'monthly_cost_native'  => $item['cost'],
                'currency'             => 'EUR',
                'allocation_mode'      => $item['mode'],
                'effective_from'       => now()->subYear()->startOfMonth()->toDateString(),
                'effective_to'         => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }
}
