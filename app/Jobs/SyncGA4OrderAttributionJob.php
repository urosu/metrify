<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\GA4PropertyNotFoundException;
use App\Exceptions\GA4QuotaExceededException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Models\Ga4Property;
use App\Models\IntegrationCredential;
use App\Services\Attribution\ChannelClassifierService;
use App\Services\Integrations\Google\GA4Client;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nightly pull of per-order GA4 attribution into ga4_order_attribution, then
 * enriches orders.attribution_first_touch / attribution_last_touch for orders
 * that currently have null or ReferrerHeuristic-only attribution.
 *
 * Attribution priority logic (applied per order after the standard parser):
 *   - If attribution_first_touch IS NULL or attribution_source = 'referrer':
 *       write GA4's firstUserSource/Medium into attribution_first_touch.
 *   - Same logic for last_touch using GA4 sessionSource/sessionMedium.
 *   - For WC native single-touch orders (first_touch === last_touch because
 *     WC only stores one touch): GA4 can supply distinct first vs last.
 *
 * GA4 joins via orders.external_id = ga4_order_attribution.transaction_id.
 * Only enriches orders in the same workspace — no cross-tenant leakage possible
 * because ga4_order_attribution.workspace_id is also checked.
 *
 * Queue:    sync-google-analytics
 * Schedule: nightly 05:00 per property (15 min after SyncGA4AttributionJob)
 * Timeout:  600 s
 * Tries:    3
 * Unique:   yes — one run per property
 *
 * Dispatched by: schedule (routes/console.php → per active ga4_property)
 *
 * @see docs/planning/backend.md §4 (connector spec)
 * @see app/Services/Attribution/Sources/GA4Source.php
 */
class SyncGA4OrderAttributionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 600;
    public int $tries     = 3;
    public int $uniqueFor = 3600;

    private const ROLLING_DAYS = 3;

    public function __construct(
        public readonly int $ga4PropertyId,
        public readonly int $workspaceId,
        public readonly int $backfillDays = 0,
    ) {
        $this->onQueue('sync-google-analytics');
    }

    public function uniqueId(): string
    {
        return (string) $this->ga4PropertyId;
    }

    public function handle(WorkspaceContext $context, ChannelClassifierService $classifier): void
    {
        $context->set($this->workspaceId);

        $property = Ga4Property::withoutGlobalScopes()->find($this->ga4PropertyId);

        if ($property === null || $property->status === 'disconnected') {
            return;
        }

        // Skip real API calls for seeded/demo credentials — data already populated by seeders.
        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->first();
        if ($credential?->is_seeded) {
            Log::info('SyncGA4OrderAttributionJob: skipping seeded credential', ['ga4_property_id' => $this->ga4PropertyId]);
            return;
        }

        $to        = Carbon::yesterday();
        $totalDays = self::ROLLING_DAYS + $this->backfillDays;
        $from      = $to->copy()->subDays($totalDays - 1);

        try {
            $client = GA4Client::forProperty($property);
            $rows   = $client->fetchOrderAttribution($from->toDateString(), $to->toDateString());
        } catch (GA4QuotaExceededException $e) {
            Log::warning('SyncGA4OrderAttributionJob: quota exceeded', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            $this->fail($e);
            return;
        } catch (GA4PropertyNotFoundException $e) {
            Log::warning('SyncGA4OrderAttributionJob: property not found', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            return;
        } catch (GoogleTokenExpiredException $e) {
            Log::warning('SyncGA4OrderAttributionJob: token expired or revoked', [
                'property_id' => $this->ga4PropertyId,
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            $this->fail($e);
            return;
        }

        if (empty($rows)) {
            return;
        }

        $now     = now()->toDateTimeString();
        $indexed = [];

        foreach ($rows as $r) {
            // GA4 can return the same transaction_id across multiple dates in a rolling window.
            // Keep the row with the highest conversion_value; the upsert key is (ga4_property_id, transaction_id).
            $key = $r->transaction_id;
            if (isset($indexed[$key]) && $r->conversion_value <= $indexed[$key]['conversion_value']) {
                continue;
            }
            $indexed[$key] = [
                'workspace_id'                  => $this->workspaceId,
                'ga4_property_id'               => $this->ga4PropertyId,
                'transaction_id'                => $r->transaction_id,
                'date'                          => $r->date,
                'session_source'                => $r->session_source,
                'session_medium'                => $r->session_medium,
                'session_campaign'              => $r->session_campaign,
                'session_default_channel_group' => $r->session_default_channel_group,
                'first_user_source'             => $r->first_user_source,
                'first_user_medium'             => $r->first_user_medium,
                'first_user_campaign'           => $r->first_user_campaign,
                'landing_page'                  => $r->landing_page,
                'conversion_value'              => $r->conversion_value,
                'synced_at'                     => $now,
            ];
        }

        $upsertRows = array_values($indexed);

        foreach (array_chunk($upsertRows, 500) as $batch) {
            DB::table('ga4_order_attribution')->upsert(
                $batch,
                ['ga4_property_id', 'transaction_id'],
                [
                    'date', 'session_source', 'session_medium', 'session_campaign',
                    'session_default_channel_group', 'first_user_source', 'first_user_medium',
                    'first_user_campaign', 'landing_page', 'conversion_value', 'synced_at',
                ],
            );
        }

        // Enrich orders whose attribution is null or was only set by the
        // ReferrerHeuristicSource (source_type = 'referrer') — the weakest tier.
        $this->enrichOrders($rows, $classifier);

        $property->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
            'last_synced_at'            => now(),
        ]);

        // Rebuild daily snapshots so enriched order attribution appears in dashboards.
        $affectedDates = collect($upsertRows)->pluck('date')->unique()->values();
        $stores = DB::table('stores')
            ->where('workspace_id', $this->workspaceId)
            ->where('status', 'active')
            ->pluck('id');

        foreach ($stores as $storeId) {
            foreach ($affectedDates as $date) {
                BuildDailySnapshotJob::dispatch($storeId, $this->workspaceId, Carbon::parse($date));
            }
        }

        Log::info('SyncGA4OrderAttributionJob: completed', [
            'property_id' => $this->ga4PropertyId,
            'rows'        => count($upsertRows),
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
        ]);
    }

    /**
     * Enrich orders that have no attribution or only a heuristic-level source.
     *
     * Joins ga4_order_attribution → orders on (workspace_id, external_id = transaction_id).
     * Only updates rows where attribution_source IS NULL or = 'referrer', meaning GA4
     * can supply stronger data. Does not touch orders already attributed by PYS,
     * Shopify journey, WC native, or other higher-priority sources.
     *
     * For WC single-touch orders (where first_touch = last_touch because WC native only
     * stores one session), GA4Source provides distinct first vs last when available,
     * so both columns are written independently.
     *
     * @param array<int, object> $rows
     */
    private function enrichOrders(array $rows, ChannelClassifierService $classifier): void
    {
        $transactionIds = array_column($rows, 'transaction_id');

        if (empty($transactionIds)) {
            return;
        }

        // Fetch orders that are candidates for enrichment.
        // We use DB:: directly to stay outside the global workspace scope
        // (the scope would double-filter but we want to be explicit here).
        $orders = DB::table('orders')
            ->whereIn('external_id', $transactionIds)
            ->where('workspace_id', $this->workspaceId)
            ->where(function ($q): void {
                $q->whereNull('attribution_source')
                  ->orWhere('attribution_source', 'referrer');
            })
            ->get(['id', 'external_id', 'attribution_source', 'attribution_first_touch', 'attribution_last_touch']);

        if ($orders->isEmpty()) {
            return;
        }

        // Index GA4 rows by transaction_id for O(1) lookup.
        $ga4ByTxn = [];
        foreach ($rows as $r) {
            $ga4ByTxn[$r->transaction_id] = $r;
        }

        $enrichedCount = 0;

        foreach ($orders as $order) {
            $ga4 = $ga4ByTxn[$order->external_id] ?? null;

            if ($ga4 === null) {
                continue;
            }

            $updates = [];

            // Build first-touch from GA4 firstUser* dimensions.
            $ga4FirstSource = $ga4->first_user_source ?? null;
            if ($ga4FirstSource !== null) {
                $firstTouch = ['source' => $ga4FirstSource];

                if (! empty($ga4->first_user_medium)) {
                    $firstTouch['medium'] = $ga4->first_user_medium;
                }

                if (! empty($ga4->first_user_campaign)) {
                    $firstTouch['campaign'] = $ga4->first_user_campaign;
                }

                if (! empty($ga4->landing_page)) {
                    $firstTouch['landing_page'] = $ga4->landing_page;
                }

                // Classify channel for the first-touch.
                $channelResult                 = $classifier->classify(
                    $ga4FirstSource,
                    $ga4->first_user_medium ?? null,
                    $this->workspaceId,
                );
                $firstTouch['channel']      = $channelResult['channel_name'];
                $firstTouch['channel_type'] = $channelResult['channel_type'];

                $updates['attribution_first_touch'] = json_encode($firstTouch);
            }

            // Build last-touch from GA4 session* dimensions.
            $ga4LastSource = $ga4->session_source ?? null;
            if ($ga4LastSource !== null) {
                $lastTouch = ['source' => $ga4LastSource];

                if (! empty($ga4->session_medium)) {
                    $lastTouch['medium'] = $ga4->session_medium;
                }

                if (! empty($ga4->session_campaign)) {
                    $lastTouch['campaign'] = $ga4->session_campaign;
                }

                if (! empty($ga4->landing_page)) {
                    $lastTouch['landing_page'] = $ga4->landing_page;
                }

                // Classify channel for the last-touch.
                $channelResult               = $classifier->classify(
                    $ga4LastSource,
                    $ga4->session_medium ?? null,
                    $this->workspaceId,
                );
                $lastTouch['channel']      = $channelResult['channel_name'];
                $lastTouch['channel_type'] = $channelResult['channel_type'];

                $updates['attribution_last_touch'] = json_encode($lastTouch);

                // Set attribution_source from the last-touch channel type — consistent
                // with how UpsertShopifyOrderAction and UpsertWooCommerceOrderAction write it.
                $updates['attribution_source'] = $channelResult['channel_type'] ?? $ga4LastSource;
            }

            if (empty($updates)) {
                continue;
            }

            DB::table('orders')
                ->where('id', $order->id)
                ->update($updates);

            $enrichedCount++;
        }

        if ($enrichedCount > 0) {
            Log::info('SyncGA4OrderAttributionJob: enriched orders', [
                'property_id'   => $this->ga4PropertyId,
                'enriched'      => $enrichedCount,
            ]);
        }
    }
}
