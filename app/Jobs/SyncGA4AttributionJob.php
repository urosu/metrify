<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\GA4PropertyNotFoundException;
use App\Exceptions\GA4QuotaExceededException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Models\Ga4Property;
use App\Models\IntegrationCredential;
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
 * Nightly pull of GA4 daily attribution breakdowns into ga4_daily_attribution.
 *
 * Syncs a rolling 3-day window by default (provisional data may be revised).
 * When $backfillDays > 0, syncs that many extra days for initial backfill.
 *
 * Row identity is tracked via `row_signature` — a SHA-256 hash of the 11 composite
 * dimension columns. This avoids a very wide unique index; the hash is deterministic
 * so re-syncing the same window is always an upsert, never a duplicate insert.
 *
 * Queue:    sync-google-analytics
 * Schedule: nightly 04:45 per property (staggered 15 min after SyncGA4SessionsJob)
 * Timeout:  1800 s (backfills iterate over CHUNK_DAYS=90 windows sequentially)
 * Tries:    3
 * Unique:   yes — one run per property
 *
 * Dispatched by: schedule (routes/console.php → per active ga4_property)
 *
 * @see docs/planning/backend.md §4 (connector spec)
 * @see app/Services/Integrations/Google/GA4Client.php::fetchDailyAttribution()
 */
class SyncGA4AttributionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 1800;
    public int $tries     = 3;
    public int $uniqueFor = 3600;

    /** Rolling window — re-syncs last N days on every run (data_state=provisional). */
    private const ROLLING_DAYS = 3;

    /** Max days per batchRunReports call — keeps PHP memory bounded during backfills. */
    private const CHUNK_DAYS = 90;

    public function __construct(
        public readonly int $ga4PropertyId,
        public readonly int $workspaceId,
        /** Extra days to back-fill beyond the rolling window. 0 = normal nightly run. */
        public readonly int $backfillDays = 0,
    ) {
        $this->onQueue('sync-google-analytics');
    }

    public function uniqueId(): string
    {
        return (string) $this->ga4PropertyId;
    }

    public function handle(WorkspaceContext $context): void
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
            Log::info('SyncGA4AttributionJob: skipping seeded credential', ['ga4_property_id' => $this->ga4PropertyId]);
            return;
        }

        $to        = Carbon::yesterday();
        $totalDays = self::ROLLING_DAYS + $this->backfillDays;
        $from      = $to->copy()->subDays($totalDays - 1);

        $client        = GA4Client::forProperty($property);
        $totalUpserted = 0;
        // Track affected dates for snapshot rebuild — keyed to deduplicate.
        $affectedDates = [];
        // Only rebuild snapshots for the last 90 days; backfills covering years of
        // history would otherwise dispatch thousands of BuildDailySnapshotJob jobs.
        $snapshotCutoff = Carbon::today()->subDays(90)->toDateString();

        // Iterate backwards in CHUNK_DAYS windows so recent data is written first.
        $chunkEnd = $to->copy();
        while ($chunkEnd->gte($from)) {
            $chunkStart = $chunkEnd->copy()->subDays(self::CHUNK_DAYS - 1);
            if ($chunkStart->lt($from)) {
                $chunkStart = $from->copy();
            }

            try {
                $rows = $client->fetchDailyAttribution($chunkStart->toDateString(), $chunkEnd->toDateString());
            } catch (GA4QuotaExceededException $e) {
                Log::warning('SyncGA4AttributionJob: quota exceeded', [
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
                Log::warning('SyncGA4AttributionJob: property not found', [
                    'property_id' => $this->ga4PropertyId,
                    'error'       => $e->getMessage(),
                ]);
                $property->update([
                    'status'                    => 'error',
                    'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
                ]);
                return;
            } catch (GoogleTokenExpiredException $e) {
                Log::warning('SyncGA4AttributionJob: token expired or revoked', [
                    'property_id' => $this->ga4PropertyId,
                ]);
                $property->update([
                    'status'                    => 'error',
                    'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
                ]);
                $this->fail($e);
                return;
            } catch (\RuntimeException $e) {
                Log::error('SyncGA4AttributionJob: API error', [
                    'property_id' => $this->ga4PropertyId,
                    'error'       => $e->getMessage(),
                ]);
                $property->update([
                    'status'                    => 'error',
                    'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
                ]);
                $this->fail($e);
                return;
            }

            if (! empty($rows)) {
                $now        = now()->toDateTimeString();
                $upsertRows = [];

                foreach ($rows as $r) {
                    $signature = $this->rowSignature(
                        $r->session_source,
                        $r->session_medium,
                        $r->session_campaign,
                        $r->first_user_source,
                        $r->first_user_medium,
                        $r->first_user_campaign,
                        $r->landing_page,
                        $r->device_category,
                        $r->country_code,
                        $r->session_default_channel_group,
                        $r->date,
                    );

                    $upsertRows[] = [
                        'workspace_id'                  => $this->workspaceId,
                        'ga4_property_id'               => $this->ga4PropertyId,
                        'date'                          => $r->date,
                        'session_source'                => $r->session_source,
                        'session_medium'                => $r->session_medium,
                        'session_campaign'              => $r->session_campaign,
                        'session_default_channel_group' => $r->session_default_channel_group,
                        'first_user_source'             => $r->first_user_source,
                        'first_user_medium'             => $r->first_user_medium,
                        'first_user_campaign'           => $r->first_user_campaign,
                        'landing_page'                  => $r->landing_page,
                        'device_category'               => $r->device_category,
                        'country_code'                  => $r->country_code,
                        'sessions'                      => $r->sessions,
                        'active_users'                  => $r->active_users,
                        'engaged_sessions'              => $r->engaged_sessions,
                        'conversions'                   => $r->conversions,
                        'total_revenue'                 => $r->total_revenue,
                        'row_signature'                 => $signature,
                        'data_state'                    => $r->data_state,
                        'synced_at'                     => $now,
                        'created_at'                    => $now,
                    ];

                    if ($r->date >= $snapshotCutoff) {
                        $affectedDates[$r->date] = true;
                    }
                }

                // GA4 occasionally returns duplicate dimension combinations in the same
                // response page; deduplicate by signature before upserting.
                $deduped = [];
                foreach ($upsertRows as $row) {
                    $deduped[$row['row_signature']] = $row;
                }
                $upsertRows = array_values($deduped);

                // Chunk into 500-row batches to stay within Postgres parameter limits.
                foreach (array_chunk($upsertRows, 500) as $batch) {
                    DB::table('ga4_daily_attribution')->upsert(
                        $batch,
                        ['ga4_property_id', 'date', 'row_signature'],
                        [
                            'sessions', 'active_users', 'engaged_sessions',
                            'conversions', 'total_revenue', 'data_state', 'synced_at',
                        ],
                    );
                }

                $totalUpserted += count($upsertRows);
            }

            $chunkEnd = $chunkStart->copy()->subDay();
        }

        $property->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
            'last_synced_at'            => now(),
        ]);

        // Rebuild daily snapshots for affected dates in the last 90 days.
        if (! empty($affectedDates)) {
            $stores = DB::table('stores')
                ->where('workspace_id', $this->workspaceId)
                ->where('status', 'active')
                ->pluck('id');

            foreach ($stores as $storeId) {
                foreach (array_keys($affectedDates) as $date) {
                    BuildDailySnapshotJob::dispatch($storeId, $this->workspaceId, Carbon::parse($date));
                }
            }
        }

        Log::info('SyncGA4AttributionJob: completed', [
            'property_id' => $this->ga4PropertyId,
            'rows'        => $totalUpserted,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
        ]);
    }

    /**
     * Build a deterministic 64-char SHA-256 signature for the composite dimension key.
     *
     * Includes `date` so rows from different dates with the same dimensions are distinct
     * (they share the same row_signature slot within a date, which is what the unique
     * index enforces: (ga4_property_id, date, row_signature)).
     *
     * The empty-string sentinel '∅' is used for null values so
     * (null, 'google') and ('', 'google') produce different signatures.
     */
    private function rowSignature(
        ?string $sessionSource,
        ?string $sessionMedium,
        ?string $sessionCampaign,
        ?string $firstUserSource,
        ?string $firstUserMedium,
        ?string $firstUserCampaign,
        ?string $landingPage,
        ?string $deviceCategory,
        ?string $countryCode,
        ?string $channelGroup,
        string  $date,
    ): string {
        $key = implode("\x00", [
            $date,
            $sessionSource       ?? '∅',
            $sessionMedium       ?? '∅',
            $sessionCampaign     ?? '∅',
            $firstUserSource     ?? '∅',
            $firstUserMedium     ?? '∅',
            $firstUserCampaign   ?? '∅',
            $landingPage         ?? '∅',
            $deviceCategory      ?? '∅',
            $countryCode         ?? '∅',
            $channelGroup        ?? '∅',
        ]);

        return hash('sha256', $key);
    }
}
