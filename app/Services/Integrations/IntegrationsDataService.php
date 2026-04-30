<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\AdAccount;
use App\Models\Ga4Property;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\StoreUrl;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Support\Facades\DB;

/**
 * Assembles all data for the Integrations/Index page.
 *
 * Reads: stores, ad_accounts, search_console_properties, integration_runs,
 *        integration_events, historical_import_jobs, channel_mappings, workspaces.
 * Writes: nothing — read-only assembly.
 * Called by: IntegrationsController::show().
 *
 * @see docs/pages/integrations.md
 */
class IntegrationsDataService
{
    /**
     * Phased-unlock milestone spec.
     * Day 0 = immediately; Day 7 = after first week; Day 30 = after first month; Day 90 = after 3 months.
     */
    private const MILESTONES = [
        ['day' => 0,  'feature' => 'Core sync & dashboards'],
        ['day' => 7,  'feature' => 'Weekly trend analysis'],
        ['day' => 30, 'feature' => 'Cohort & LTV analysis'],
        ['day' => 90, 'feature' => 'Predictive forecasting'],
    ];

    public function build(Workspace $workspace, string $activeTab): array
    {
        $workspaceId = $workspace->id;

        // ── integration cards ────────────────────────────────────────────────────

        // Latest integration_run per (integrationable_type, integrationable_id) for status.
        $latestRuns = DB::table('integration_runs')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('integrationable_type')
            ->whereNotNull('integrationable_id')
            ->select(
                DB::raw('DISTINCT ON (integrationable_type, integrationable_id) integrationable_type'),
                'integrationable_id',
                'status',
                'completed_at',
                'scheduled_at',
                'error_message',
            )
            ->orderByRaw('integrationable_type, integrationable_id, completed_at DESC')
            ->get()
            ->keyBy(fn ($r) => $r->integrationable_type . ':' . $r->integrationable_id);

        // Owner's name for "connected_by".
        $ownerName = WorkspaceUser::where('workspace_id', $workspaceId)
            ->where('role', 'owner')
            ->with('user:id,name')
            ->first()
            ?->user
            ?->name;

        $integrations = collect();

        // Stores
        $stores = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'slug', 'name', 'platform', 'status', 'last_synced_at', 'consecutive_sync_failures'])
            ->orderBy('created_at')
            ->get();

        // Load all active monitored URLs for this workspace in one query, keyed by store_id.
        $urlsByStore = StoreUrl::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->orderBy('is_homepage', 'desc')
            ->orderBy('created_at')
            ->get(['id', 'store_id', 'url', 'label', 'is_homepage'])
            ->groupBy('store_id');

        foreach ($stores as $s) {
            $run = $latestRuns->get(Store::class . ':' . $s->id);
            $integrations->push([
                'id'             => $s->id,
                'slug'           => $s->slug,
                'type'           => 'store',
                'name'           => $s->name,
                'status'         => $this->deriveStatus($s->status, $s->consecutive_sync_failures, $run),
                'last_sync_at'   => $s->last_synced_at?->toISOString() ?? $run?->completed_at,
                'next_sync_at'   => $s->last_synced_at?->addHour()->toISOString(),
                'connected_by'   => $ownerName,
                'account_label'  => $s->platform,
                'error_message'  => $run?->error_message,
                'settings_url'   => '/' . $workspace->slug . '/settings/stores/' . $s->slug,
                'monitored_urls' => ($urlsByStore[$s->id] ?? collect())->map(fn ($u) => [
                    'id'          => $u->id,
                    'url'         => $u->url,
                    'label'       => $u->label,
                    'is_homepage' => (bool) $u->is_homepage,
                ])->values()->all(),
            ]);
        }

        // Ad accounts
        $adAccounts = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'name', 'platform', 'status', 'last_synced_at', 'consecutive_sync_failures'])
            ->orderBy('created_at')
            ->get();

        foreach ($adAccounts as $a) {
            $run  = $latestRuns->get(AdAccount::class . ':' . $a->id);
            $type = match ($a->platform) {
                'facebook' => 'facebook',
                'google'   => 'google_ads',
                default    => 'google_ads',
            };
            $integrations->push([
                'id'            => $a->id,
                'type'          => $type,
                'name'          => $a->name,
                'status'        => $this->deriveStatus($a->status, $a->consecutive_sync_failures, $run),
                'last_sync_at'  => $a->last_synced_at?->toISOString() ?? $run?->completed_at,
                'next_sync_at'  => $a->last_synced_at?->addHour()->toISOString(),
                'connected_by'  => $ownerName,
                'account_label' => strtoupper($a->platform),
                'error_message' => $run?->error_message,
                'settings_url'  => null,
            ]);
        }

        // GSC properties
        $gscProperties = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'property_url', 'status', 'last_synced_at', 'consecutive_sync_failures'])
            ->orderBy('created_at')
            ->get();

        foreach ($gscProperties as $p) {
            $run = $latestRuns->get(SearchConsoleProperty::class . ':' . $p->id);
            $integrations->push([
                'id'            => $p->id,
                'type'          => 'gsc',
                'name'          => $p->property_url,
                'status'        => $this->deriveStatus($p->status, $p->consecutive_sync_failures, $run),
                'last_sync_at'  => $p->last_synced_at?->toISOString() ?? $run?->completed_at,
                'next_sync_at'  => $p->last_synced_at?->addHour()->toISOString(),
                'connected_by'  => $ownerName,
                'account_label' => 'Search Console',
                'error_message' => $run?->error_message,
                'settings_url'  => null,
            ]);
        }

        // GA4 properties
        $ga4Properties = Ga4Property::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'property_name', 'property_id', 'status', 'last_synced_at', 'consecutive_sync_failures'])
            ->orderBy('created_at')
            ->get();

        foreach ($ga4Properties as $p) {
            $run = $latestRuns->get(Ga4Property::class . ':' . $p->id);
            $integrations->push([
                'id'            => $p->id,
                'type'          => 'ga4',
                'name'          => $p->property_name,
                'status'        => $this->deriveStatus($p->status, $p->consecutive_sync_failures, $run),
                'last_sync_at'  => $p->last_synced_at?->toISOString() ?? $run?->completed_at,
                'next_sync_at'  => $p->last_synced_at?->addHour()->toISOString(),
                'connected_by'  => $ownerName,
                'account_label' => $p->property_id,
                'error_message' => $run?->error_message,
                'settings_url'  => null,
            ]);
        }

        $integrationsArr = $integrations->values()->all();
        $connectedCount  = count(array_filter($integrationsArr, fn ($i) => $i['status'] !== 'not_connected'));
        $totalCount      = count($integrationsArr);

        // next sync across all integrations
        $nextSyncAt = collect($integrationsArr)
            ->pluck('next_sync_at')
            ->filter()
            ->sort()
            ->first();

        // ── summary ─────────────────────────────────────────────────────────────

        // events_24h: integration_events in the last 24 hours for this workspace.
        $events24h = DB::table('integration_events')
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $summary = [
            'connected_count'  => $connectedCount,
            'total_count'      => $totalCount,
            'events_24h'       => $events24h,
            'overall_accuracy' => null, // computed by IntegrationHealthService when available
            'next_sync_at'     => $nextSyncAt,
        ];

        // ── accuracy ────────────────────────────────────────────────────────────

        // Accuracy is the ratio of consecutive_sync_failures === 0 within each platform group.
        // Zero failures = healthy; otherwise null (no meaningful ratio yet without CAPI data).
        $accuracy = [
            'facebook' => $this->platformAccuracy($adAccounts->where('platform', 'facebook')),
            'google'   => $this->platformAccuracy($adAccounts->where('platform', 'google')),
            'gsc'      => $this->platformAccuracy($gscProperties),
        ];

        // ── error codes ─────────────────────────────────────────────────────────

        // Aggregate from integration_events grouped by (error_code, destination_platform, event_type).
        $errorCodesRaw = DB::table('integration_events')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('error_code')
            ->selectRaw(
                'error_code AS code,
                 destination_platform AS destination,
                 event_type AS event,
                 MIN(created_at) AS first_seen,
                 MAX(created_at) AS last_seen,
                 COUNT(*) AS count'
            )
            ->groupBy('error_code', 'destination_platform', 'event_type')
            ->orderByDesc('count')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'code'        => $r->code ?? 'UNKNOWN',
                'destination' => $r->destination ?? 'store',
                'event'       => $r->event ?? '—',
                'first_seen'  => $r->first_seen,
                'last_seen'   => $r->last_seen,
                'count'       => (int) $r->count,
                'explanation' => $this->errorExplanation((string) ($r->code ?? '')),
            ])
            ->all();

        // ── import jobs ──────────────────────────────────────────────────────────

        $importJobs = DB::table('historical_import_jobs')
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('started_at')
            ->limit(50)
            ->get()
            ->map(fn ($j) => [
                'id'               => $j->id,
                'connector'        => $this->connectorFromType((string) ($j->integrationable_type ?? '')),
                'phase'            => $j->job_type ?? 'import',
                'progress'         => (int) ($j->total_rows_imported ?? 0),
                'total'            => (int) ($j->total_rows_imported ?? 0),
                'status'           => $j->status,
                'started_at'       => $j->started_at,
                'duration_seconds' => isset($j->duration_seconds) ? (int) $j->duration_seconds : null,
            ])
            ->all();

        // ── channel mappings ─────────────────────────────────────────────────────

        // Global mappings (is_global=true, workspace_id=null) + workspace overrides.
        $channelMappings = DB::table('channel_mappings')
            ->where(fn ($q) => $q->whereNull('workspace_id')->where('is_global', true)
                ->orWhere('workspace_id', $workspaceId))
            ->orderBy('priority')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn ($m) => [
                'id'            => $m->id,
                'priority'      => (int) $m->priority,
                'utm_source'    => $m->utm_source_pattern,
                'utm_medium'    => $m->utm_medium_pattern,
                'channel'       => $m->channel_name,
                'source_of_truth' => $m->workspace_id !== null ? 'workspace' : 'seeded',
            ])
            ->all();

        // ── phased unlock ────────────────────────────────────────────────────────

        // Days since the oldest order in this workspace, falling back to workspace creation.
        // Using the oldest order date means the milestone clock starts when real data
        // arrives, not when the workspace was provisioned (which may be well before any
        // store is connected or orders are imported).
        $oldestOrderRow = DB::selectOne(
            'SELECT MIN(occurred_at::date) AS oldest FROM orders WHERE workspace_id = ?',
            [$workspaceId],
        );
        $anchor     = ($oldestOrderRow?->oldest !== null)
            ? \Illuminate\Support\Carbon::parse($oldestOrderRow->oldest)
            : $workspace->created_at;
        $currentDay = (int) $anchor->diffInDays(now());
        $currentDay = max(0, $currentDay);

        $phasedUnlock = [
            'current_day' => $currentDay,
            'unlocks'     => array_map(fn ($m) => [
                'day'      => $m['day'],
                'feature'  => $m['feature'],
                'unlocked' => $currentDay >= $m['day'],
            ], self::MILESTONES),
        ];

        // ── events per day (last 30 days) ────────────────────────────────────────

        $eventsPerDay = $this->eventsPerDay($workspaceId, 30);

        return [
            'integrations'   => $integrationsArr,
            'summary'        => $summary,
            'accuracy'       => $accuracy,
            'error_codes'    => $errorCodesRaw,
            'import_jobs'    => $importJobs,
            'channel_mappings' => $channelMappings,
            'phased_unlock'  => $phasedUnlock,
            'active_tab'     => $activeTab,
            'events_per_day' => $eventsPerDay,
        ];
    }

    /**
     * Count integration_events rows grouped by date + destination_platform for the
     * last $days days. Used by the Tracking Health tab "Events / day" chart.
     *
     * Returns rows of shape:
     *   { date: 'YYYY-MM-DD', connector: string, count: int }
     *
     * Returns an empty array when no events exist for this workspace (new install).
     * Uses destination_platform as the connector identifier (matches SourceBadge keys).
     *
     * @return array<int, array{date: string, connector: string, count: int}>
     */
    public function eventsPerDay(int $workspaceId, int $days = 30): array
    {
        return DB::table('integration_events')
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw(
                "DATE(created_at) AS date,
                 destination_platform AS connector,
                 COUNT(*) AS count"
            )
            ->groupByRaw('DATE(created_at), destination_platform')
            ->orderByRaw('DATE(created_at) ASC, destination_platform ASC')
            ->get()
            ->map(fn ($r) => [
                'date'      => $r->date,
                'connector' => $r->connector ?? 'unknown',
                'count'     => (int) $r->count,
            ])
            ->all();
    }

    /**
     * Map store/ad account status + consecutive_sync_failures → IntegrationCard status.
     *
     * syncing   = a run is currently in progress (started_at set, completed_at null)
     * healthy   = active and no recent failures
     * warning   = 1–2 consecutive failures or status != active
     * failed    = 3+ consecutive failures or status = error/failed
     * not_connected = status = pending/inactive/disconnected
     */
    private function deriveStatus(string $modelStatus, int $failures, ?object $run): string
    {
        if (in_array($modelStatus, ['pending', 'inactive', 'disconnected'], true)) {
            return 'not_connected';
        }

        // In-flight run takes precedence — let the user know sync is happening.
        if ($run !== null && $run->status === 'running') {
            return 'syncing';
        }

        // If the most recent run failed: treat as failed regardless of model status.
        if ($run !== null && $run->status === 'failed') {
            return $failures >= 3 ? 'failed' : 'warning';
        }

        if ($failures >= 3) {
            return 'failed';
        }

        if ($failures >= 1 || $modelStatus !== 'active') {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Compute a rough accuracy percentage for a group of connectable models.
     * Returns null when there are no models in the group.
     *
     * Formula: (count with 0 failures / total) × 100
     * Null means "not enough data" per the trust thesis.
     */
    private function platformAccuracy(\Illuminate\Support\Collection $models): ?float
    {
        if ($models->isEmpty()) {
            return null;
        }

        $healthy = $models->filter(fn ($m) => $m->consecutive_sync_failures === 0)->count();

        return round(($healthy / $models->count()) * 100, 1);
    }

    /**
     * Derive a connector slug from the integrationable_type FQCN.
     */
    private function connectorFromType(string $type): string
    {
        if (str_contains($type, 'Store')) {
            return 'store';
        }
        if (str_contains($type, 'AdAccount')) {
            return 'facebook'; // platform-level not stored on job row; default to facebook
        }
        if (str_contains($type, 'SearchConsole')) {
            return 'gsc';
        }
        if (str_contains($type, 'Ga4')) {
            return 'ga4';
        }

        return 'store';
    }

    /**
     * Human-readable explanation for common integration error codes.
     * Extend as new error codes are introduced.
     */
    private function errorExplanation(string $code): string
    {
        return match ($code) {
            'AUTH_EXPIRED'       => 'OAuth token expired — reconnect the integration.',
            'RATE_LIMITED'       => 'API rate limit hit — will retry automatically.',
            'PERMISSION_DENIED'  => 'Insufficient API permissions — review the OAuth scope.',
            'INVALID_PAYLOAD'    => 'Unexpected response structure from the platform API.',
            'TIMEOUT'            => 'Request timed out — transient network issue.',
            'WEBHOOK_SIGNATURE'  => 'Webhook signature mismatch — check the secret key.',
            default              => 'Unexpected error — check integration logs for details.',
        };
    }
}
