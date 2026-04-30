<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreUrl;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Per-store settings page (General, Costs, Performance tabs).
 *
 * Reads:  stores, store_cost_settings, shipping_rules, transaction_fee_rules, store_urls.
 * Writes: stores (name, primary_country_code, settings JSONB), store_cost_settings (upsert/delete).
 * Called by: /settings/stores/{slug} GET + PATCH + DELETE /costs.
 *
 * Override principle: store-level settings supersede workspace-level settings.
 * The UI surfaces workspace values as "Inherited" when no store override exists.
 *
 * @see docs/planning/backend.md §6
 * @see docs/pages/profit.md
 */
class StoreSettingsController extends Controller
{
    /**
     * List all stores in the workspace so users can navigate to per-store settings.
     *
     * Reads:  stores (workspace-scoped)
     * Writes: nothing
     * Called by: GET /settings/stores
     *
     * @see docs/planning/backend.md §6
     */
    public function index(): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $stores = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->orderBy('created_at')
            ->get(['id', 'slug', 'name', 'platform', 'currency', 'primary_country_code', 'status', 'last_synced_at'])
            ->map(fn ($s) => [
                'id'                   => $s->id,
                'slug'                 => $s->slug,
                'name'                 => $s->name,
                'platform'             => $s->platform,
                'currency'             => $s->currency,
                'primary_country_code' => $s->primary_country_code,
                'status'               => $s->status,
                'last_synced_at'       => $s->last_synced_at?->toDateTimeString(),
            ])
            ->all();

        return Inertia::render('Settings/Stores', [
            'stores' => $stores,
        ]);
    }

    /**
     * Render the Settings/Store page with General, Costs, and Performance data.
     */
    public function show(Request $request, string $storeSlug): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        // Workspace-level cost settings (store_id IS NULL)
        $workspaceCosts = DB::table('store_cost_settings')
            ->where('workspace_id', $workspaceId)
            ->whereNull('store_id')
            ->first();

        // Store-specific cost override (store_id = this store)
        $storeCosts = DB::table('store_cost_settings')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $store->id)
            ->first();

        // Workspace-level shipping rules (store_id IS NULL)
        $workspaceShipping = DB::table('shipping_rules')
            ->where('workspace_id', $workspaceId)
            ->whereNull('store_id')
            ->orderBy('id')
            ->get(['id', 'min_weight_grams', 'max_weight_grams', 'destination_country', 'cost_native', 'currency']);

        // Store-level shipping rule overrides
        $storeShipping = DB::table('shipping_rules')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->get(['id', 'min_weight_grams', 'max_weight_grams', 'destination_country', 'cost_native', 'currency']);

        // Workspace-level transaction fees (store_id IS NULL)
        $workspaceFees = DB::table('transaction_fee_rules')
            ->where('workspace_id', $workspaceId)
            ->whereNull('store_id')
            ->where('is_seeded', false)
            ->orderBy('id')
            ->get(['id', 'processor', 'percentage_bps', 'fixed_fee_native', 'currency']);

        // Store-level transaction fee overrides
        $storeFees = DB::table('transaction_fee_rules')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->get(['id', 'processor', 'percentage_bps', 'fixed_fee_native', 'currency']);

        // Monitored URLs for this store
        $monitoredUrls = StoreUrl::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_homepage')
            ->orderBy('created_at')
            ->get(['id', 'url', 'label', 'is_homepage']);

        $settings = $store->settings ?? [];

        return Inertia::render('Settings/Store', [
            'store' => [
                'id'                   => $store->id,
                'slug'                 => $store->slug,
                'name'                 => $store->name,
                'domain'               => $store->domain,
                'platform'             => $store->platform,
                'currency'             => $store->currency,
                'timezone'             => $store->timezone,
                'primary_country_code' => $store->primary_country_code,
                'status'               => $store->status,
                'settings'             => [
                    'prices_include_vat' => $settings['prices_include_vat'] ?? null,
                ],
            ],
            'workspace_costs' => $workspaceCosts ? [
                'shipping_mode'             => $workspaceCosts->shipping_mode,
                'shipping_flat_rate_native' => $workspaceCosts->shipping_flat_rate_native,
                'shipping_per_order_native' => $workspaceCosts->shipping_per_order_native,
                'default_currency'          => $workspaceCosts->default_currency,
            ] : null,
            'store_costs' => $storeCosts ? [
                'shipping_mode'             => $storeCosts->shipping_mode,
                'shipping_flat_rate_native' => $storeCosts->shipping_flat_rate_native,
                'shipping_per_order_native' => $storeCosts->shipping_per_order_native,
                'default_currency'          => $storeCosts->default_currency,
            ] : null,
            'workspace_shipping' => $workspaceShipping->map(fn ($r) => [
                'id'         => $r->id,
                'min_weight' => $r->min_weight_grams,
                'max_weight' => $r->max_weight_grams,
                'country'    => $r->destination_country,
                'cost'       => (float) $r->cost_native,
                'currency'   => $r->currency,
            ])->all(),
            'store_shipping' => $storeShipping->map(fn ($r) => [
                'id'         => $r->id,
                'min_weight' => $r->min_weight_grams,
                'max_weight' => $r->max_weight_grams,
                'country'    => $r->destination_country,
                'cost'       => (float) $r->cost_native,
                'currency'   => $r->currency,
            ])->all(),
            'workspace_fees' => $workspaceFees->map(fn ($r) => [
                'id'        => $r->id,
                'processor' => $r->processor,
                'rate_pct'  => round($r->percentage_bps / 100, 2),
                'fixed'     => $r->fixed_fee_native !== null ? (float) $r->fixed_fee_native : null,
                'currency'  => $r->currency,
            ])->all(),
            'store_fees' => $storeFees->map(fn ($r) => [
                'id'        => $r->id,
                'processor' => $r->processor,
                'rate_pct'  => round($r->percentage_bps / 100, 2),
                'fixed'     => $r->fixed_fee_native !== null ? (float) $r->fixed_fee_native : null,
                'currency'  => $r->currency,
            ])->all(),
            'monitored_urls' => $monitoredUrls->map(fn ($u) => [
                'id'          => $u->id,
                'url'         => $u->url,
                'label'       => $u->label,
                'is_homepage' => (bool) $u->is_homepage,
            ])->all(),
        ]);
    }

    /**
     * Update general or cost settings for a single store.
     *
     * Accepts `section` = 'general' | 'costs' to distinguish which sub-form
     * submitted, avoiding partial-update collisions when both tabs are open.
     */
    public function update(Request $request, string $storeSlug): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'section'                   => ['required', 'in:general,costs'],
            // General
            'name'                      => ['sometimes', 'required', 'string', 'max:255'],
            'primary_country_code'      => ['sometimes', 'nullable', 'string', 'size:2'],
            'prices_include_vat'        => ['sometimes', 'nullable', 'boolean'],
            // Costs
            'shipping_mode'             => ['sometimes', 'required', 'in:flat_rate,per_order,none'],
            'shipping_flat_rate_native' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipping_per_order_native' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($validated['section'] === 'general') {
            $storeData = [];
            if (isset($validated['name'])) {
                $storeData['name'] = $validated['name'];
            }
            if (array_key_exists('primary_country_code', $validated)) {
                $storeData['primary_country_code'] = $validated['primary_country_code'];
            }

            if (!empty($storeData)) {
                $store->update($storeData);
            }

            if (array_key_exists('prices_include_vat', $validated)) {
                $current = $store->settings ?? [];
                $current['prices_include_vat'] = $validated['prices_include_vat'];
                $store->update(['settings' => $current]);
            }
        }

        if ($validated['section'] === 'costs') {
            DB::table('store_cost_settings')->updateOrInsert(
                ['workspace_id' => $workspaceId, 'store_id' => $store->id],
                [
                    'shipping_mode'             => $validated['shipping_mode'] ?? 'none',
                    'shipping_flat_rate_native'  => $validated['shipping_flat_rate_native'] ?? null,
                    'shipping_per_order_native'  => $validated['shipping_per_order_native'] ?? null,
                    'default_currency'           => $store->currency,
                    'updated_at'                 => now(),
                ]
            );
        }

        return redirect()->back()->with('success', 'Settings saved.');
    }

    /**
     * Remove the store-specific cost override, reverting to workspace defaults.
     */
    public function destroyCosts(string $storeSlug): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        DB::table('store_cost_settings')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $store->id)
            ->delete();

        return redirect()->back()->with('success', 'Store cost override removed — workspace defaults will apply.');
    }
}
