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
 * Nightly pull of last 3 days of sessions from the GA4 Data API.
 *
 * Upserts rows to `ga4_daily_sessions` and triggers a snapshot session backfill
 * for each affected date (dispatches BuildDailySnapshotJob).
 *
 * GA4 data younger than 3 days may be revised, so a rolling 3-day window
 * ensures eventual consistency.
 *
 * Queue:     sync-google-analytics (NEW — see config/horizon.php)
 * Schedule:  nightly 04:30 per property
 * Timeout:   300 s
 * Tries:     3
 * Unique:    yes — (propertyId) per run
 *
 * Dispatched by: schedule (Kernel → per active ga4_property)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see app/Services/Integrations/Google/GA4Client.php
 */
class SyncGA4SessionsJob implements ShouldQueue, ShouldBeUnique
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

    private function sanitizeUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        // Strip bytes that are invalid in UTF-8 so Postgres doesn't reject the row.
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8') ?: null;
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
            Log::info('SyncGA4SessionsJob: skipping seeded credential', ['ga4_property_id' => $this->ga4PropertyId]);
            return;
        }

        $to        = Carbon::yesterday();
        $totalDays = self::ROLLING_DAYS + $this->backfillDays;
        $from      = $to->copy()->subDays($totalDays - 1);

        try {
            $client = GA4Client::forProperty($property);
            $rows   = $client->fetchDailySessions($from, $to);
        } catch (GA4QuotaExceededException $e) {
            Log::warning('SyncGA4SessionsJob: quota exceeded', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update(['status' => 'error', 'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1')]);
            $this->fail($e);
            return;
        } catch (GA4PropertyNotFoundException $e) {
            Log::warning('SyncGA4SessionsJob: property not found', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update(['status' => 'error', 'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1')]);
            return;
        } catch (GoogleTokenExpiredException $e) {
            Log::warning('SyncGA4SessionsJob: token expired or revoked', [
                'property_id' => $this->ga4PropertyId,
            ]);
            $property->update(['status' => 'error', 'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1')]);
            $this->fail($e);
            return;
        }

        if ($rows->isEmpty()) {
            return;
        }

        $now = now()->toDateTimeString();

        // Deduplicate on the upsert key before batching. After UTF-8 sanitization
        // multiple GA4 rows can collapse to the same (date, country_code, device_category)
        // combination, causing Postgres to reject the batch with a cardinality violation.
        // Merge collisions by summing session/user counts.
        $indexed = [];
        foreach ($rows as $r) {
            $countryCode    = $this->sanitizeUtf8($r->country_code);
            $deviceCategory = $this->sanitizeUtf8($r->device_category);
            $key = $r->date . '|' . ($countryCode ?? '') . '|' . ($deviceCategory ?? '');

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

        $upsertRows = array_values($indexed);

        foreach (array_chunk($upsertRows, 500) as $batch) {
            DB::table('ga4_daily_sessions')->upsert(
                $batch,
                ['ga4_property_id', 'date', 'country_code', 'device_category'],
                ['sessions', 'users', 'data_state', 'synced_at'],
            );
        }

        $property->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
            'last_synced_at'            => now(),
        ]);

        // Trigger snapshot session backfill for affected stores in this workspace.
        $affectedDates = collect($upsertRows)->pluck('date')->unique()->values();
        $stores = DB::table('stores')
            ->where('workspace_id', $this->workspaceId)
            ->where('status', 'active')
            ->pluck('id');

        foreach ($stores as $storeId) {
            foreach ($affectedDates as $date) {
                BuildDailySnapshotJob::dispatch(
                    $storeId,
                    $this->workspaceId,
                    Carbon::parse($date),
                );
            }
        }
    }
}
