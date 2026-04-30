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
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Full historical backfill for a GA4 property.
 *
 * Iterates the from_date → to_date range in 90-day chunks (oldest-first), writing:
 *   - ga4_daily_attribution   (fetchDailyAttribution)
 *   - ga4_order_attribution   (fetchOrderAttribution)
 *   - ga4_daily_sessions      (fetchDailySessions)
 *
 * Progress and checkpoint are stored in historical_import_jobs so the import
 * can be resumed after a failure without re-fetching already-processed chunks.
 *
 * Nightly sync jobs (SyncGA4AttributionJob, SyncGA4OrderAttributionJob,
 * SyncGA4SessionsJob) remain responsible for the rolling 3-day window once
 * the backfill completes; this job only runs once per connect/reimport event.
 *
 * Queue:   imports-ga4
 * Timeout: 7200 s (large window — 3-year backfill at 90-day chunks = 12 API rounds)
 * Tries:   5
 *
 * Dispatched by: HistoricalImportOrchestrator::start() (GoogleOAuthController on connect,
 *                IntegrationSyncController::reimportGa4 on manual re-trigger).
 *
 * @see docs/planning/backend.md §4 (GA4 connector spec)
 * @see app/Services/Integrations/HistoricalImportOrchestrator.php
 * @see app/Services/Integrations/Google/GA4Client.php
 */
class Ga4HistoricalImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 5;

    /** Days per batchRunReports call — keeps memory bounded and matches SyncGA4AttributionJob. */
    private const CHUNK_DAYS = 90;

    public function __construct(
        private readonly int $ga4PropertyId,
        private readonly int $workspaceId,
    ) {
        $this->onQueue('imports-ga4');
    }

    public function handle(WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);

        $property = Ga4Property::withoutGlobalScopes()->find($this->ga4PropertyId);

        if ($property === null || $property->status === 'disconnected') {
            Log::warning('Ga4HistoricalImportJob: property not found or disconnected', [
                'ga4_property_id' => $this->ga4PropertyId,
            ]);
            return;
        }

        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->first();

        if ($credential?->is_seeded) {
            Log::info('Ga4HistoricalImportJob: skipping seeded credential', [
                'ga4_property_id' => $this->ga4PropertyId,
            ]);
            $this->markCompleted(0);
            return;
        }

        $importJob = $this->fetchImportJobRow();

        if ($importJob === null) {
            Log::error('Ga4HistoricalImportJob: no historical_import_jobs row found', [
                'ga4_property_id' => $this->ga4PropertyId,
                'workspace_id'    => $this->workspaceId,
            ]);
            return;
        }

        $this->updateJobRow(['status' => 'running', 'started_at' => $importJob->started_at ?? now()]);

        try {
            $client        = GA4Client::forProperty($property);
            $totalImported = $this->runImport($client, $importJob);

            $property->update(['last_synced_at' => now(), 'status' => 'active', 'consecutive_sync_failures' => 0]);
            $this->markCompleted($totalImported);

            Log::info('Ga4HistoricalImportJob: completed', [
                'ga4_property_id' => $this->ga4PropertyId,
                'total_imported'  => $totalImported,
            ]);
        } catch (Throwable $e) {
            $this->updateJobRow([
                'status'        => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 500),
                'completed_at'  => now(),
            ]);

            Log::error('Ga4HistoricalImportJob: failed', [
                'ga4_property_id' => $this->ga4PropertyId,
                'error'           => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Iterate from_date → to_date in 90-day chunks, fetching all 3 data types per chunk.
     *
     * Chunk direction: oldest → newest so the import job can be cancelled mid-way and
     * historical data is present further back. Checkpoint records the last processed
     * chunk_end so retries skip already-imported chunks.
     *
     * @return int Total rows upserted across all tables and chunks.
     */
    private function runImport(GA4Client $client, object $importJob): int
    {
        $importFrom    = Carbon::parse($importJob->from_date)->startOfDay();
        $importTo      = Carbon::parse($importJob->to_date ?? now()->yesterday())->startOfDay();
        $totalDays     = max(1, (int) $importFrom->diffInDays($importTo) + 1);
        $totalImported = 0;
        $chunksTotal   = (int) ceil($totalDays / self::CHUNK_DAYS);
        $chunksDone    = 0;

        $checkpoint  = is_string($importJob->checkpoint)
            ? (array) json_decode($importJob->checkpoint, true)
            : [];
        $resumeAfter = isset($checkpoint['chunk_end'])
            ? Carbon::parse($checkpoint['chunk_end'])->startOfDay()
            : null;

        $chunkStart = $importFrom->copy();

        while ($chunkStart->lte($importTo)) {
            $chunkEnd = $chunkStart->copy()->addDays(self::CHUNK_DAYS - 1);
            if ($chunkEnd->gt($importTo)) {
                $chunkEnd = $importTo->copy();
            }

            // Resume from checkpoint — skip chunks already processed.
            if ($resumeAfter !== null && $chunkEnd->lte($resumeAfter)) {
                $chunkStart = $chunkEnd->copy()->addDay();
                $chunksDone++;
                continue;
            }

            $client->refreshIfNeeded();

            $chunkImported  = 0;
            $chunkImported += $this->importDailyAttribution($client, $chunkStart, $chunkEnd);
            $chunkImported += $this->importOrderAttribution($client, $chunkStart, $chunkEnd);
            $chunkImported += $this->importSessions($client, $chunkStart, $chunkEnd);

            $totalImported += $chunkImported;
            $chunksDone++;

            $progress = (int) min(99, round(($chunksDone / $chunksTotal) * 100));

            $this->updateJobRow([
                'progress_pct'        => $progress,
                'total_rows_imported' => $totalImported,
                'checkpoint'          => json_encode(['chunk_end' => $chunkEnd->toDateString()]),
            ]);

            $chunkStart = $chunkEnd->copy()->addDay();
        }

        return $totalImported;
    }

    private function importDailyAttribution(GA4Client $client, Carbon $from, Carbon $to): int
    {
        $rows = $client->fetchDailyAttribution($from->toDateString(), $to->toDateString());

        if (empty($rows)) {
            return 0;
        }

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

            $upsertRows[$signature] = [
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
        }

        $batch = array_values($upsertRows);

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('ga4_daily_attribution')->upsert(
                $chunk,
                ['ga4_property_id', 'date', 'row_signature'],
                ['sessions', 'active_users', 'engaged_sessions', 'conversions', 'total_revenue', 'data_state', 'synced_at'],
            );
        }

        return count($batch);
    }

    private function importOrderAttribution(GA4Client $client, Carbon $from, Carbon $to): int
    {
        $rows = $client->fetchOrderAttribution($from->toDateString(), $to->toDateString());

        if (empty($rows)) {
            return 0;
        }

        $now     = now()->toDateTimeString();
        $indexed = [];

        foreach ($rows as $r) {
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

        $batch = array_values($indexed);

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('ga4_order_attribution')->upsert(
                $chunk,
                ['ga4_property_id', 'transaction_id'],
                [
                    'date', 'session_source', 'session_medium', 'session_campaign',
                    'session_default_channel_group', 'first_user_source', 'first_user_medium',
                    'first_user_campaign', 'landing_page', 'conversion_value', 'synced_at',
                ],
            );
        }

        return count($batch);
    }

    private function importSessions(GA4Client $client, Carbon $from, Carbon $to): int
    {
        $rows = $client->fetchDailySessions($from, $to);

        if ($rows->isEmpty()) {
            return 0;
        }

        $now     = now()->toDateTimeString();
        $indexed = [];

        foreach ($rows as $r) {
            $countryCode    = $this->sanitizeUtf8($r->country_code);
            $deviceCategory = $this->sanitizeUtf8($r->device_category);
            $key            = $r->date . '|' . ($countryCode ?? '') . '|' . ($deviceCategory ?? '');

            if (isset($indexed[$key])) {
                $indexed[$key]['sessions'] += (int) $r->sessions;
                $indexed[$key]['users']    += (int) $r->users;
            } else {
                $indexed[$key] = [
                    'workspace_id'    => $this->workspaceId,
                    'ga4_property_id' => $this->ga4PropertyId,
                    'date'            => $r->date,
                    'sessions'        => (int) $r->sessions,
                    'users'           => (int) $r->users,
                    'country_code'    => $countryCode,
                    'device_category' => $deviceCategory,
                    'data_state'      => $r->data_state,
                    'synced_at'       => $now,
                    'created_at'      => $now,
                ];
            }
        }

        $batch = array_values($indexed);

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('ga4_daily_sessions')->upsert(
                $chunk,
                ['ga4_property_id', 'date', 'country_code', 'device_category'],
                ['sessions', 'users', 'data_state', 'synced_at'],
            );
        }

        return count($batch);
    }

    // -------------------------------------------------------------------------

    private function fetchImportJobRow(): ?object
    {
        return DB::table('historical_import_jobs')
            ->where('workspace_id', $this->workspaceId)
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->orderByDesc('created_at')
            ->first();
    }

    private function updateJobRow(array $fields): void
    {
        DB::table('historical_import_jobs')
            ->where('workspace_id', $this->workspaceId)
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->orderByDesc('created_at')
            ->limit(1)
            ->update(array_merge($fields, ['updated_at' => now()]));
    }

    private function markCompleted(int $totalImported): void
    {
        $startedAt = DB::table('historical_import_jobs')
            ->where('workspace_id', $this->workspaceId)
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->orderByDesc('created_at')
            ->value('started_at');

        $this->updateJobRow([
            'status'              => 'completed',
            'progress_pct'        => 100,
            'total_rows_imported' => $totalImported,
            'checkpoint'          => json_encode([]),
            'completed_at'        => now(),
            'duration_seconds'    => (int) now()->diffInSeconds(
                $startedAt ? Carbon::parse($startedAt) : now()
            ),
        ]);
    }

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
        ?string $sessionDefaultChannelGroup,
        string  $date,
    ): string {
        $sentinel = '∅';
        $key      = implode('|', [
            $sessionSource             ?? $sentinel,
            $sessionMedium             ?? $sentinel,
            $sessionCampaign           ?? $sentinel,
            $firstUserSource           ?? $sentinel,
            $firstUserMedium           ?? $sentinel,
            $firstUserCampaign         ?? $sentinel,
            $landingPage               ?? $sentinel,
            $deviceCategory            ?? $sentinel,
            $countryCode               ?? $sentinel,
            $sessionDefaultChannelGroup ?? $sentinel,
            $date,
        ]);

        return substr(hash('sha256', $key), 0, 64);
    }

    private function sanitizeUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8') ?: null;
    }
}
