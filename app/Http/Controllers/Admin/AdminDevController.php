<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin developer tools — snippets and debug views.
 *
 * Purpose: Non-production only pages for debugging workspace context, store
 *          status, ad accounts, GSC properties, and rendering dev snippets.
 *
 * Reads:  WorkspaceContext (session), stores, ad_accounts, search_console_properties,
 *         historical_import_jobs.
 * Writes: nothing.
 * Callers: routes/web.php admin group (non-production only):
 *          GET /admin/dev/snippets, GET /admin/dev/debug.
 *
 * @see docs/planning/backend.md#6
 */
class AdminDevController extends Controller
{
    public function snippets(): Response
    {
        return Inertia::render('Admin/Dev/Snippets');
    }

    public function debug(): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = $workspaceId ? Workspace::withoutGlobalScopes()->find($workspaceId) : null;

        // historical_import_status was dropped from stores in the L2 schema rebuild.
        $stores = $workspace
            ? (function () use ($workspace): array {
                $storesData = Store::withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->select(['id', 'name', 'slug', 'status', 'consecutive_sync_failures', 'last_synced_at'])
                    ->get();
                $importStatuses = DB::table('historical_import_jobs')
                    ->whereIn('integrationable_id', $storesData->pluck('id'))
                    ->where('integrationable_type', Store::class)
                    ->orderByDesc('created_at')
                    ->get(['integrationable_id', 'status'])
                    ->groupBy('integrationable_id')
                    ->map(fn ($rows) => $rows->first()->status);
                return $storesData->map(fn ($s) => [
                    'id'                        => $s->id,
                    'name'                      => $s->name,
                    'slug'                      => $s->slug,
                    'status'                    => $s->status,
                    'consecutive_sync_failures' => $s->consecutive_sync_failures,
                    'last_synced_at'            => $s->last_synced_at?->toISOString(),
                    'historical_import_status'  => $importStatuses[$s->id] ?? null,
                ])->all();
            })()
            : [];

        $adAccounts = $workspace
            ? AdAccount::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->select(['id', 'platform', 'external_id', 'name', 'currency', 'status', 'consecutive_sync_failures', 'last_synced_at'])
                ->get()
                ->map(fn ($a) => [
                    'id'                       => $a->id,
                    'platform'                 => $a->platform,
                    'external_id'              => $a->external_id,
                    'name'                     => $a->name,
                    'currency'                 => $a->currency,
                    'status'                   => $a->status,
                    'consecutive_sync_failures' => $a->consecutive_sync_failures,
                    'last_synced_at'           => $a->last_synced_at?->toISOString(),
                ])
            : [];

        $gscProperties = $workspace
            ? SearchConsoleProperty::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->select(['id', 'property_url', 'status', 'consecutive_sync_failures', 'last_synced_at'])
                ->get()
                ->map(fn ($p) => [
                    'id'                       => $p->id,
                    'property_url'             => $p->property_url,
                    'status'                   => $p->status,
                    'consecutive_sync_failures' => $p->consecutive_sync_failures,
                    'last_synced_at'           => $p->last_synced_at?->toISOString(),
                ])
            : [];

        return Inertia::render('Admin/Dev/Debug', [
            'context' => [
                'workspace_id'   => $workspaceId,
                'workspace'      => $workspace ? $workspace->only([
                    'id', 'name', 'slug', 'billing_plan', 'trial_ends_at',
                    'reporting_currency', 'reporting_timezone', 'is_orphaned', 'deleted_at', 'created_at',
                ]) : null,
                'stores'         => $stores,
                'ad_accounts'    => $adAccounts,
                'gsc_properties' => $gscProperties,
                'impersonating'  => session()->has('impersonating_admin_id'),
            ],
        ]);
    }
}
