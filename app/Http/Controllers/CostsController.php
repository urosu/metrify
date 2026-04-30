<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProductCostImportAction;
use App\Actions\UpdateCostConfigAction;
use App\Jobs\BuildDailySnapshotJob;
use App\Jobs\RecomputeAttributionJob;
use App\Models\OpexAllocation;
use App\Models\PlatformFeeRule;
use App\Models\ProductVariant;
use App\Models\ShippingRule;
use App\Models\Store;
use App\Models\TaxRule;
use App\Models\TransactionFeeRule;
use App\Models\Workspace;
use App\Services\JobLockChecker;
use App\Services\WorkspaceContext;
use App\ValueObjects\CostConfigDiff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Settings/Costs — shipping rules, transaction fees, tax rules, opex,
 * platform fees, and per-SKU COGS (manual + CSV).
 *
 * Absorbs the product-costs CRUD previously owned by ManageController.
 * Cost-table mutations route through `UpdateCostConfigAction`. COGS lives on
 * `product_variants` (cogs_amount / cogs_currency / cogs_source) — the old
 * `product_costs` table was removed in the L2 rebuild.
 *
 * Reads: store_cost_settings, shipping_rules, transaction_fee_rules, tax_rules,
 *        opex_allocations, platform_fee_rules, product_variants.
 * Writes: above tables via UpdateCostConfigAction + ProductCostImportAction (CSV).
 * Called by: /settings/costs/* routes.
 *
 * @see docs/pages/profit.md
 * @see docs/planning/backend.md §6
 */
class CostsController extends Controller
{
    /**
     * Render the Settings/Costs page with every cost-table slice the frontend
     * needs. Sections: shipping · fees · tax · opex · platform · product-COGS.
     */
    public function show(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::findOrFail($workspaceId);

        $shippingRules = ShippingRule::where('workspace_id', $workspaceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'         => (int) $r->id,
                'min_order'  => $r->min_weight_grams !== null ? (float) $r->min_weight_grams : null,
                'max_order'  => $r->max_weight_grams !== null ? (float) $r->max_weight_grams : null,
                'rate'       => (float) $r->cost_native,
                'carrier'    => null,
                'country'    => $r->destination_country,
            ])
            ->all();

        $transactionFeeRules = TransactionFeeRule::where('workspace_id', $workspaceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'           => (int) $r->id,
                'provider'     => (string) $r->processor,
                'rate_pct'     => (float) $r->percentage_bps / 100,
                'fixed_amount' => $r->fixed_fee_native !== null ? (float) $r->fixed_fee_native : null,
            ])
            ->all();

        $taxRules = TaxRule::where('workspace_id', $workspaceId)
            ->orderBy('country_code')
            ->get()
            ->map(fn ($r) => [
                'id'         => (int) $r->id,
                'country'    => (string) $r->country_code,
                'rate_pct'   => (float) $r->standard_rate_bps / 100,
                'applies_to' => $r->is_included_in_price ? 'inclusive' : 'exclusive',
            ])
            ->all();

        $platformFeeRules = PlatformFeeRule::where('workspace_id', $workspaceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'       => (int) $r->id,
                'platform' => (string) $r->item_label,
                'rate_pct' => (float) $r->monthly_cost_native,
            ])
            ->all();

        $opexAllocations = OpexAllocation::where('workspace_id', $workspaceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'         => (int) $r->id,
                'category'   => (string) $r->category,
                'amount'     => (float) $r->monthly_cost_native,
                'frequency'  => (string) $r->allocation_mode,
                'start_date' => $r->effective_from?->toDateString() ?? '',
            ])
            ->all();

        $totalSkus = ProductVariant::where('workspace_id', $workspaceId)->count();
        $cogsConfiguredCount = ProductVariant::where('workspace_id', $workspaceId)
            ->whereNotNull('cogs_amount')
            ->count();

        $paymentMethods = \Illuminate\Support\Facades\DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('payment_method_title')
            ->where('payment_method_title', '!=', '')
            ->distinct()
            ->orderBy('payment_method_title')
            ->pluck('payment_method_title')
            ->values()
            ->all();

        return Inertia::render('Settings/Costs', [
            'shipping_rules'         => $shippingRules,
            'transaction_fee_rules'  => $transactionFeeRules,
            'tax_rules'              => $taxRules,
            'platform_fee_rules'     => $platformFeeRules,
            'opex_allocations'       => $opexAllocations,
            'cogs_configured_count'  => $cogsConfiguredCount,
            'total_skus'             => $totalSkus,
            'payment_methods'        => $paymentMethods,
            'default_cogs_pct'       => $workspace->workspace_settings->defaultCogsPct,
            'is_recomputing'         => app(JobLockChecker::class)->isLocked(RecomputeAttributionJob::class, $workspaceId),
        ]);
    }


    public function updateShipping(Request $request, UpdateCostConfigAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $diff = new CostConfigDiff(shipping: $request->all());
        $action->handle($workspace, $diff, $request->user()->id);

        return back()->with('success', 'Shipping rules updated.');
    }

    public function updateFees(Request $request, UpdateCostConfigAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $diff = new CostConfigDiff(transactionFees: $request->all());
        $action->handle($workspace, $diff, $request->user()->id);

        return back()->with('success', 'Transaction fees updated.');
    }

    public function updateTax(Request $request, UpdateCostConfigAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $diff = new CostConfigDiff(tax: $request->all());
        $action->handle($workspace, $diff, $request->user()->id);

        return back()->with('success', 'Tax rules updated.');
    }

    public function updateOpex(Request $request, UpdateCostConfigAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $diff = new CostConfigDiff(opex: $request->all());
        $action->handle($workspace, $diff, $request->user()->id);

        return back()->with('success', 'Opex updated.');
    }

    public function updatePlatformFees(Request $request, UpdateCostConfigAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $diff = new CostConfigDiff(platformFees: $request->all());
        $action->handle($workspace, $diff, $request->user()->id);

        return back()->with('success', 'Platform fees updated.');
    }

    /**
     * Save the workspace-level COGS fallback percentage.
     *
     * Stored in `workspaces.workspace_settings->cogs->default_pct`.
     * null clears the fallback (disables percentage-based COGS).
     */
    public function updateDefaultCogsPct(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::findOrFail($workspaceId);

        $validated = $request->validate([
            'default_cogs_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings                  = $workspace->workspace_settings;
        $settings->defaultCogsPct  = $validated['default_cogs_pct'] !== null
            ? (float) $validated['default_cogs_pct']
            : null;

        $workspace->workspace_settings = $settings;
        $workspace->save();

        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Default COGS percentage updated.');
    }

    // ─── Per-row CRUD: Shipping Rules ────────────────────────────────────────────

    /**
     * Create a new shipping rule for the workspace.
     *
     * Frontend field → DB column mapping:
     *   min_order  → min_weight_grams (integer grams)
     *   max_order  → max_weight_grams (integer grams)
     *   rate       → cost_native      (decimal)
     *   carrier    → (no DB column; silently ignored)
     *   country    → destination_country
     */
    public function storeShippingRule(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_order' => ['nullable', 'numeric', 'min:0'],
            'rate'      => ['required', 'numeric', 'min:0'],
            'carrier'   => ['nullable', 'string', 'max:100'],
            'country'   => ['nullable', 'string', 'max:10'],
        ]);

        $rule = ShippingRule::create($this->mapShippingFields($workspaceId, $validated));

        app(WorkspaceContext::class)->id() && $this->auditCostRow('costs', 'shipping_rule', $rule->id, 'created', null, 'row', $request->user()->id);

        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Shipping rule added.');
    }

    /**
     * Patch a single field on an existing shipping rule.
     * Accepts `{field: <frontend_key>, value: <string>}`.
     */
    public function patchShippingRule(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $rule = ShippingRule::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:min_order,max_order,rate,carrier,country'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $mapped = $this->mapShippingField($validated['field'], $validated['value']);
        // carrier maps to [] (no DB column) — skip the update but still return success
        // so the UI doesn't show an error for a cosmetic-only display field.
        $old = count($mapped) > 0 ? $rule->{array_key_first($mapped)} : null;
        if (count($mapped) > 0) {
            $rule->update($mapped);
        }

        $this->auditCostRow('costs', 'shipping_rule', $id, $validated['field'], $old, $validated['value'], $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Shipping rule updated.');
    }

    /** Delete a shipping rule row. */
    public function destroyShippingRule(int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        ShippingRule::where('workspace_id', $workspaceId)->findOrFail($id)->delete();
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Shipping rule deleted.');
    }

    // ─── Per-row CRUD: Transaction Fee Rules ─────────────────────────────────────

    /**
     * Create a new transaction fee rule.
     *
     * Frontend field → DB column mapping:
     *   provider     → processor
     *   rate_pct     → percentage_bps  (× 100, stored as basis points)
     *   fixed_amount → fixed_fee_native
     */
    public function storeTransactionFeeRule(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'provider'     => ['required', 'string', 'max:100'],
            'rate_pct'     => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rule = TransactionFeeRule::create($this->mapTransactionFeeFields($workspaceId, $validated));

        $this->auditCostRow('costs', 'transaction_fee_rule', $rule->id, 'created', null, 'row', $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Transaction fee rule added.');
    }

    /** Patch a single field on a transaction fee rule. */
    public function patchTransactionFeeRule(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $rule = TransactionFeeRule::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:provider,rate_pct,fixed_amount'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $mapped = $this->mapTransactionFeeField($validated['field'], $validated['value']);
        $old    = $rule->{array_key_first($mapped)};
        $rule->update($mapped);

        $this->auditCostRow('costs', 'transaction_fee_rule', $id, $validated['field'], $old, $validated['value'], $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Transaction fee rule updated.');
    }

    /** Delete a transaction fee rule row. */
    public function destroyTransactionFeeRule(int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        TransactionFeeRule::where('workspace_id', $workspaceId)->findOrFail($id)->delete();
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Transaction fee rule deleted.');
    }

    // ─── Per-row CRUD: Tax Rules ──────────────────────────────────────────────────

    /**
     * Create a new tax rule.
     *
     * Frontend field → DB column mapping:
     *   country    → country_code
     *   rate_pct   → standard_rate_bps  (× 100, stored as basis points)
     *   applies_to → is_included_in_price (string 'inclusive' → true, else false)
     *              + no direct 'applies_to' column; is_included_in_price is the closest
     */
    public function storeTaxRule(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'country'    => ['required', 'string', 'max:10'],
            'rate_pct'   => ['required', 'numeric', 'min:0', 'max:100'],
            'applies_to' => ['nullable', 'string', 'max:100'],
        ]);

        $rule = TaxRule::create($this->mapTaxRuleFields($workspaceId, $validated));

        $this->auditCostRow('costs', 'tax_rule', $rule->id, 'created', null, 'row', $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Tax rule added.');
    }

    /** Patch a single field on a tax rule. */
    public function patchTaxRule(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $rule = TaxRule::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:country,rate_pct,applies_to'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $mapped = $this->mapTaxRuleField($validated['field'], $validated['value']);
        $old    = $rule->{array_key_first($mapped)};
        $rule->update($mapped);

        $this->auditCostRow('costs', 'tax_rule', $id, $validated['field'], $old, $validated['value'], $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Tax rule updated.');
    }

    /** Delete a tax rule row. */
    public function destroyTaxRule(int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        TaxRule::where('workspace_id', $workspaceId)->findOrFail($id)->delete();
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Tax rule deleted.');
    }

    // ─── Per-row CRUD: Platform Fee Rules ────────────────────────────────────────

    /**
     * Create a new platform fee rule.
     *
     * Frontend field → DB column mapping:
     *   platform → item_label
     *   rate_pct → monthly_cost_native  (shown as a rate % in the UI for platform fees)
     */
    public function storePlatformFeeRule(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:100'],
            'rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $rule = PlatformFeeRule::create($this->mapPlatformFeeFields($workspaceId, $validated));

        $this->auditCostRow('costs', 'platform_fee_rule', $rule->id, 'created', null, 'row', $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Platform fee rule added.');
    }

    /** Patch a single field on a platform fee rule. */
    public function patchPlatformFeeRule(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $rule = PlatformFeeRule::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:platform,rate_pct'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $mapped = $this->mapPlatformFeeField($validated['field'], $validated['value']);
        $old    = $rule->{array_key_first($mapped)};
        $rule->update($mapped);

        $this->auditCostRow('costs', 'platform_fee_rule', $id, $validated['field'], $old, $validated['value'], $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Platform fee rule updated.');
    }

    /** Delete a platform fee rule row. */
    public function destroyPlatformFeeRule(int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        PlatformFeeRule::where('workspace_id', $workspaceId)->findOrFail($id)->delete();
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Platform fee rule deleted.');
    }

    // ─── Per-row CRUD: Opex Allocations ──────────────────────────────────────────

    /**
     * Create a new opex allocation.
     *
     * Frontend field → DB column mapping:
     *   category   → category
     *   amount     → monthly_cost_native
     *   frequency  → allocation_mode
     *   start_date → effective_from
     */
    public function storeOpexAllocation(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'category'   => ['required', 'string', 'max:100'],
            'amount'     => ['required', 'numeric', 'min:0'],
            'frequency'  => ['nullable', 'string', 'in:monthly,annually'],
            'start_date' => ['nullable', 'date'],
        ]);

        // currency is not user-editable — always taken from the workspace reporting_currency.
        $validated['currency'] = \Illuminate\Support\Facades\DB::table('workspaces')
            ->where('id', $workspaceId)
            ->value('reporting_currency') ?? 'EUR';

        $row = OpexAllocation::create($this->mapOpexFields($workspaceId, $validated));

        $this->auditCostRow('costs', 'opex_allocation', $row->id, 'created', null, 'row', $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Operating expense added.');
    }

    /** Patch a single field on an opex allocation. */
    public function patchOpexAllocation(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $row = OpexAllocation::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:category,amount,frequency,start_date'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $mapped = $this->mapOpexField($validated['field'], $validated['value']);
        $old    = $row->{array_key_first($mapped)};
        $row->update($mapped);

        $this->auditCostRow('costs', 'opex_allocation', $id, $validated['field'], $old, $validated['value'], $request->user()->id);
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Operating expense updated.');
    }

    /** Delete an opex allocation row. */
    public function destroyOpexAllocation(int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        OpexAllocation::where('workspace_id', $workspaceId)->findOrFail($id)->delete();
        $this->dispatchCostRecalc($workspaceId);

        return back()->with('success', 'Operating expense deleted.');
    }

    // ─── Field-mapping helpers ────────────────────────────────────────────────────

    /**
     * Map the full shipping form submission to DB columns.
     * Frontend uses order-value semantics; DB stores weight_grams — both are
     * a numeric threshold so the mapping is semantic-only, not unit-converted.
     *
     * @param  array<string, mixed> $data  Validated frontend data
     * @return array<string, mixed>
     */
    private function mapShippingFields(int $workspaceId, array $data): array
    {
        return [
            'workspace_id'        => $workspaceId,
            'min_weight_grams'    => isset($data['min_order']) && $data['min_order'] !== '' ? (int) $data['min_order'] : null,
            'max_weight_grams'    => isset($data['max_order']) && $data['max_order'] !== '' ? (int) $data['max_order'] : null,
            'cost_native'         => (float) ($data['rate'] ?? 0),
            'destination_country' => $data['country'] ?? null,
            // carrier has no column in shipping_rules — silently dropped.
        ];
    }

    /**
     * Map a single frontend field name to a DB column + coerced value for shipping_rules.
     *
     * @return array<string, mixed>  Single-element array [column => value]
     */
    private function mapShippingField(string $field, ?string $value): array
    {
        return match ($field) {
            'min_order' => ['min_weight_grams'    => $value !== null && $value !== '' ? (int) $value : null],
            'max_order' => ['max_weight_grams'    => $value !== null && $value !== '' ? (int) $value : null],
            'rate'      => ['cost_native'         => (float) ($value ?? 0)],
            'country'   => ['destination_country' => $value ?: null],
            'carrier'   => [],  // no column; no-op
            default     => [],
        };
    }

    /**
     * Map transaction fee form submission to DB columns.
     * rate_pct (e.g. "2.9") → percentage_bps (e.g. 290).
     *
     * @return array<string, mixed>
     */
    private function mapTransactionFeeFields(int $workspaceId, array $data): array
    {
        return [
            'workspace_id'     => $workspaceId,
            'processor'        => $data['provider'],
            'percentage_bps'   => (int) round((float) ($data['rate_pct'] ?? 0) * 100),
            'fixed_fee_native' => isset($data['fixed_amount']) && $data['fixed_amount'] !== '' ? (float) $data['fixed_amount'] : null,
        ];
    }

    /**
     * Map a single frontend field to a DB column for transaction_fee_rules.
     *
     * @return array<string, mixed>
     */
    private function mapTransactionFeeField(string $field, ?string $value): array
    {
        return match ($field) {
            'provider'     => ['processor'        => (string) $value],
            'rate_pct'     => ['percentage_bps'   => (int) round((float) $value * 100)],
            'fixed_amount' => ['fixed_fee_native' => $value !== null && $value !== '' ? (float) $value : null],
            default        => [],
        };
    }

    /**
     * Map tax rule form submission to DB columns.
     * rate_pct (e.g. "20") → standard_rate_bps (e.g. 2000).
     * applies_to 'inclusive' → is_included_in_price = true; anything else → false.
     *
     * @return array<string, mixed>
     */
    private function mapTaxRuleFields(int $workspaceId, array $data): array
    {
        return [
            'workspace_id'         => $workspaceId,
            'country_code'         => strtoupper($data['country']),
            'standard_rate_bps'    => (int) round((float) ($data['rate_pct'] ?? 0) * 100),
            'is_included_in_price' => ($data['applies_to'] ?? '') === 'inclusive',
        ];
    }

    /**
     * Map a single frontend field to a DB column for tax_rules.
     *
     * @return array<string, mixed>
     */
    private function mapTaxRuleField(string $field, ?string $value): array
    {
        return match ($field) {
            'country'    => ['country_code'         => strtoupper((string) $value)],
            'rate_pct'   => ['standard_rate_bps'    => (int) round((float) $value * 100)],
            'applies_to' => ['is_included_in_price' => $value === 'inclusive'],
            default      => [],
        };
    }

    /**
     * Map platform fee form submission to DB columns.
     * The platform_fee_rules table stores a monthly_cost_native; the UI repurposes
     * the rate_pct label for this field for display consistency.
     *
     * @return array<string, mixed>
     */
    private function mapPlatformFeeFields(int $workspaceId, array $data): array
    {
        return [
            'workspace_id'        => $workspaceId,
            'item_label'          => $data['platform'],
            'monthly_cost_native' => (float) ($data['rate_pct'] ?? 0),
        ];
    }

    /**
     * Map a single frontend field to a DB column for platform_fee_rules.
     *
     * @return array<string, mixed>
     */
    private function mapPlatformFeeField(string $field, ?string $value): array
    {
        return match ($field) {
            'platform' => ['item_label'          => (string) $value],
            'rate_pct' => ['monthly_cost_native' => (float) $value],
            default    => [],
        };
    }

    /**
     * Map opex form submission to DB columns.
     *
     * @return array<string, mixed>
     */
    private function mapOpexFields(int $workspaceId, array $data): array
    {
        return [
            'workspace_id'        => $workspaceId,
            'category'            => $data['category'],
            'monthly_cost_native' => (float) ($data['amount'] ?? 0),
            'allocation_mode'     => $data['frequency'] ?? 'monthly',
            'effective_from'      => $data['start_date'] ?? null,
            'currency'            => $data['currency'],
        ];
    }

    /**
     * Map a single frontend field to a DB column for opex_allocations.
     *
     * @return array<string, mixed>
     */
    private function mapOpexField(string $field, ?string $value): array
    {
        return match ($field) {
            'category'   => ['category'            => (string) $value],
            'amount'     => ['monthly_cost_native'  => (float) $value],
            'frequency'  => ['allocation_mode'      => (string) $value],
            'start_date' => ['effective_from'        => $value ?: null],
            default      => [],
        };
    }

    // ─── Shared helpers ───────────────────────────────────────────────────────────

    /**
     * Dispatch snapshot rebuild for every active store in the workspace so that
     * profit figures reflect the updated cost rules.
     *
     * @see docs/planning/backend.md §0 rule 5 (cost config changes → recalc)
     */
    private function dispatchCostRecalc(int $workspaceId): void
    {
        \Illuminate\Support\Facades\DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->select(['store_id', 'date'])
            ->orderBy('store_id')
            ->orderBy('date')
            ->chunk(1000, function (\Illuminate\Support\Collection $chunk) use ($workspaceId): void {
                foreach ($chunk as $row) {
                    BuildDailySnapshotJob::dispatch(
                        (int) $row->store_id,
                        $workspaceId,
                        \Illuminate\Support\Carbon::parse($row->date),
                    );
                }
            });
    }

    /**
     * Write a single audit log entry for a per-row cost mutation.
     */
    private function auditCostRow(
        string $subPage,
        string $entityType,
        int $entityId,
        string $field,
        mixed $from,
        mixed $to,
        ?int $actorUserId,
    ): void {
        $workspaceId = app(WorkspaceContext::class)->id();
        app(\App\Services\Workspace\SettingsAuditService::class)->record(
            $workspaceId,
            $subPage,
            $entityType,
            $entityId,
            $field,
            $from,
            $to,
            false,
            $actorUserId,
        );
    }

    public function editCogs(Request $request, int $id): Response
    {
        return Inertia::render('Settings/Costs', ['editing_variant_id' => $id]);
    }

    public function updateCogs(Request $request, int $id): RedirectResponse
    {
        $variant = ProductVariant::findOrFail($id);
        abort_unless($variant->workspace_id === app(WorkspaceContext::class)->id(), 404);

        $validated = $request->validate([
            'cogs_amount'   => ['required', 'numeric', 'min:0'],
            'cogs_currency' => ['required', 'string', 'size:3'],
        ]);

        $variant->update([
            'cogs_amount'   => $validated['cogs_amount'],
            'cogs_currency' => strtoupper($validated['cogs_currency']),
            'cogs_source'   => 'manual',
        ]);

        return back()->with('success', 'COGS updated.');
    }

    // ─── Product costs (absorbed from ManageController) ──────────────────────

    /**
     * Create manual product cost rows — one per selected product_external_id.
     * Accepts an array so the UI can apply the same cost to multiple SKUs
     * in a single submission.
     */
    public function storeProductCost(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'store_id'               => ['required', 'integer'],
            'product_external_ids'   => ['required', 'array', 'min:1'],
            'product_external_ids.*' => ['required', 'string', 'max:255'],
            'unit_cost'              => ['required', 'numeric', 'min:0'],
            'currency'               => ['required', 'string', 'size:3'],
            'effective_from'         => ['nullable', 'date'],
            'effective_to'           => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        abort_unless(
            Store::withoutGlobalScopes()
                ->where('id', $validated['store_id'])
                ->where('workspace_id', $workspaceId)
                ->exists(),
            404,
        );

        // product_costs table removed — update product_variants COGS columns.
        $currency = strtoupper($validated['currency']);
        $updated  = 0;

        foreach ($validated['product_external_ids'] as $externalId) {
            $updated += ProductVariant::withoutGlobalScopes()
                ->whereHas('product', fn ($q) => $q->where('store_id', $validated['store_id'])
                    ->where('external_id', $externalId))
                ->update([
                    'cogs_amount'   => $validated['unit_cost'],
                    'cogs_currency' => $currency,
                    'cogs_source'   => 'manual',
                ]);
        }

        $label = $updated === 1 ? '1 product cost saved.' : "{$updated} product costs saved.";

        return back()->with('success', $label);
    }

    /**
     * Update COGS on a specific product variant (replaces old product_cost row update).
     */
    public function updateProductCost(Request $request, int $variantId): RedirectResponse
    {
        $variant = ProductVariant::withoutGlobalScopes()
            ->where('workspace_id', app(WorkspaceContext::class)->id())
            ->findOrFail($variantId);

        $validated = $request->validate([
            'unit_cost'      => ['required', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'size:3'],
        ]);

        $variant->update([
            'cogs_amount'   => $validated['unit_cost'],
            'cogs_currency' => strtoupper($validated['currency']),
            'cogs_source'   => 'manual',
        ]);

        return back()->with('success', 'Product cost updated.');
    }

    public function destroyProductCost(int $variantId): RedirectResponse
    {
        $variant = ProductVariant::withoutGlobalScopes()
            ->where('workspace_id', app(WorkspaceContext::class)->id())
            ->findOrFail($variantId);

        $variant->update(['cogs_amount' => null, 'cogs_source' => null, 'cogs_currency' => null]);

        return back()->with('success', 'Product cost cleared.');
    }

    /**
     * Bulk clear COGS; scoped to active workspace so cross-tenant IDs are silently ignored.
     */
    public function bulkDestroyProductCosts(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = ProductVariant::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $validated['ids'])
            ->update(['cogs_amount' => null, 'cogs_source' => null, 'cogs_currency' => null]);

        return back()->with('success', "{$deleted} product cost(s) deleted.");
    }

    /**
     * CSV import handler. Delegates to {@see ProductCostImportAction}; the
     * result summary is flashed as `import_result` for the CSV dialog.
     */
    public function importProductCosts(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::findOrFail($workspaceId);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = (new ProductCostImportAction())->execute($request->file('file'), $workspace);

        return back()->with('import_result', $result);
    }

    /** Stream a sample CSV template the user can fill in and re-upload. */
    public function productCostTemplate(): StreamedResponse
    {
        $filename = 'product-costs-template.csv';

        return response()->stream(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['product_external_id', 'sku', 'unit_cost', 'currency', 'effective_from', 'effective_to']);
            fputcsv($out, ['123', '', '9.99', 'USD', date('Y-m-d'), '']);
            fputcsv($out, ['',    'my-product-sku', '14.50', 'USD', date('Y') . '-01-01', '']);
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

}
