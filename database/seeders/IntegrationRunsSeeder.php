<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdAccount;
use App\Models\Ga4Property;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds integration_runs with 30 days × ~13 integrations of realistic historical runs.
 *
 * Distribution (matches task spec):
 *   70% completed with rows_processed > 0
 *   20% completed with rows_processed = 0 (no new data)
 *    8% failed (rate limit / 401 / network timeout)
 *    2% running (in-progress at seed time — last 5 min)
 *
 * Writes: integration_runs
 *
 * Reads: workspaces, stores, ad_accounts, ga4_properties, search_console_properties
 * Called by: DatabaseSeeder (dev/staging only)
 *
 * @see docs/planning/schema.md §1.10 integration_runs
 */
class IntegrationRunsSeeder extends Seeder
{
    // Sample error messages for failed runs — realistic API failure modes.
    private const ERROR_MESSAGES = [
        'Rate limit exceeded: too many requests in a short period. Retry after 60 seconds.',
        'HTTP 401 Unauthorized: access token expired or revoked. Re-authentication required.',
        'HTTP 401 Unauthorized: invalid OAuth credentials.',
        'Network timeout after 120 seconds — upstream API did not respond.',
        'cURL error 28: Connection timed out after 30001 milliseconds.',
        'HTTP 429 Too Many Requests: daily quota exhausted.',
        'HTTP 500 Internal Server Error: upstream API returned unexpected response.',
        'SSL handshake failed: unable to verify peer certificate.',
    ];

    // Job-type strings that appear in each integration_run row.
    private const JOB_TYPES = [
        'store'  => 'App\\Jobs\\SyncStoreOrdersJob',
        'shopify' => 'App\\Jobs\\SyncShopifyOrdersJob',
        'ads'    => 'App\\Jobs\\SyncAdInsightsJob',
        'ga4'    => 'App\\Jobs\\SyncGA4SessionsJob',
        'gsc'    => 'App\\Jobs\\SyncSearchConsoleJob',
    ];

    // Queue names per integration type.
    private const QUEUES = [
        'store'   => 'sync-store',
        'shopify' => 'sync-store',
        'ads_fb'  => 'sync-facebook',
        'ads_goo' => 'sync-google-ads',
        'ga4'     => 'sync-google-analytics',
        'gsc'     => 'sync-google-search',
    ];

    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        if (! $workspace) {
            return;
        }

        $now  = now();
        $rows = [];

        // ── Stores (WooCommerce) ─────────────────────────────────────────────────
        $stores = Store::where('workspace_id', $workspace->id)->get();
        foreach ($stores as $store) {
            for ($d = 29; $d >= 0; $d--) {
                $rows[] = $this->buildRow(
                    workspaceId: $workspace->id,
                    type:        Store::class,
                    typeId:      $store->id,
                    jobType:     self::JOB_TYPES['store'],
                    queue:       self::QUEUES['store'],
                    daysAgo:     $d,
                    baseRows:    [80, 220],
                );
            }
        }

        // ── Ad Accounts (Facebook + Google) ──────────────────────────────────────
        $adAccounts = AdAccount::where('workspace_id', $workspace->id)->get();
        foreach ($adAccounts as $account) {
            $queue = $account->platform === 'google'
                ? self::QUEUES['ads_goo']
                : self::QUEUES['ads_fb'];

            for ($d = 29; $d >= 0; $d--) {
                $rows[] = $this->buildRow(
                    workspaceId: $workspace->id,
                    type:        AdAccount::class,
                    typeId:      $account->id,
                    jobType:     self::JOB_TYPES['ads'],
                    queue:       $queue,
                    daysAgo:     $d,
                    baseRows:    [200, 1200],
                );
            }
        }

        // ── GA4 Properties ────────────────────────────────────────────────────────
        $ga4Properties = Ga4Property::where('workspace_id', $workspace->id)->get();
        foreach ($ga4Properties as $property) {
            for ($d = 29; $d >= 0; $d--) {
                $rows[] = $this->buildRow(
                    workspaceId: $workspace->id,
                    type:        Ga4Property::class,
                    typeId:      $property->id,
                    jobType:     self::JOB_TYPES['ga4'],
                    queue:       self::QUEUES['ga4'],
                    daysAgo:     $d,
                    baseRows:    [50, 300],
                );
            }
        }

        // ── Search Console Properties ─────────────────────────────────────────────
        $gscProperties = SearchConsoleProperty::where('workspace_id', $workspace->id)->get();
        foreach ($gscProperties as $property) {
            for ($d = 29; $d >= 0; $d--) {
                $rows[] = $this->buildRow(
                    workspaceId: $workspace->id,
                    type:        SearchConsoleProperty::class,
                    typeId:      $property->id,
                    jobType:     self::JOB_TYPES['gsc'],
                    queue:       self::QUEUES['gsc'],
                    daysAgo:     $d,
                    baseRows:    [100, 800],
                );
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('integration_runs')->insert($chunk);
        }
    }

    /**
     * Build a single integration_runs row with randomised status distribution.
     *
     * Status probabilities:
     *   0–69  → completed (rows_processed > 0)    — 70%
     *   70–89 → completed (rows_processed = 0)    — 20%
     *   90–97 → failed                             —  8%
     *   98–99 → running (only for day 0 / today)   —  2%
     *
     * @param  array{0: int, 1: int}  $baseRows  [min, max] rows for a busy run
     */
    private function buildRow(
        int    $workspaceId,
        string $type,
        int    $typeId,
        string $jobType,
        string $queue,
        int    $daysAgo,
        array  $baseRows,
    ): array {
        $dice = mt_rand(0, 99);

        // 'running' only plausible for runs that started within the last 5 min (today).
        if ($daysAgo === 0 && $dice >= 98) {
            $startedAt = now()->subMinutes(mt_rand(1, 5));
            return [
                'workspace_id'         => $workspaceId,
                'integrationable_type' => $type,
                'integrationable_id'   => $typeId,
                'job_type'             => $jobType,
                'status'               => 'running',
                'records_processed'    => 0,
                'error_message'        => null,
                'started_at'           => $startedAt,
                'completed_at'         => null,
                'scheduled_at'         => $startedAt->copy()->subSeconds(mt_rand(2, 10)),
                'queue'                => $queue,
                'attempt'              => 1,
                'timeout_seconds'      => 300,
                'duration_seconds'     => null,
                'created_at'           => $startedAt,
                'updated_at'           => $startedAt,
            ];
        }

        // Anything else in day 0 should be completed, not running.
        $dice = $dice >= 98 ? mt_rand(0, 97) : $dice;

        if ($dice >= 90) {
            // 8% failed
            return $this->failedRow($workspaceId, $type, $typeId, $jobType, $queue, $daysAgo);
        }

        if ($dice >= 70) {
            // 20% completed, 0 rows
            return $this->completedRow($workspaceId, $type, $typeId, $jobType, $queue, $daysAgo, 0, 0);
        }

        // 70% completed with real rows
        $records  = mt_rand($baseRows[0], $baseRows[1]);
        $duration = mt_rand(3, 45);
        return $this->completedRow($workspaceId, $type, $typeId, $jobType, $queue, $daysAgo, $records, $duration);
    }

    private function completedRow(
        int    $workspaceId,
        string $type,
        int    $typeId,
        string $jobType,
        string $queue,
        int    $daysAgo,
        int    $records,
        int    $durationSeconds,
    ): array {
        $startedAt   = now()->subDays($daysAgo)->setTime(mt_rand(3, 5), mt_rand(0, 59), mt_rand(0, 59));
        $completedAt = $startedAt->copy()->addSeconds(max($durationSeconds, 1));

        return [
            'workspace_id'         => $workspaceId,
            'integrationable_type' => $type,
            'integrationable_id'   => $typeId,
            'job_type'             => $jobType,
            'status'               => 'completed',
            'records_processed'    => $records,
            'error_message'        => null,
            'started_at'           => $startedAt,
            'completed_at'         => $completedAt,
            'scheduled_at'         => $startedAt->copy()->subSeconds(mt_rand(2, 10)),
            'queue'                => $queue,
            'attempt'              => 1,
            'timeout_seconds'      => 300,
            'duration_seconds'     => $durationSeconds > 0 ? $durationSeconds : null,
            'created_at'           => $startedAt,
            'updated_at'           => $completedAt,
        ];
    }

    private function failedRow(
        int    $workspaceId,
        string $type,
        int    $typeId,
        string $jobType,
        string $queue,
        int    $daysAgo,
    ): array {
        $startedAt   = now()->subDays($daysAgo)->setTime(mt_rand(3, 5), mt_rand(0, 59), mt_rand(0, 59));
        $durationSeconds = mt_rand(8, 125);
        $completedAt = $startedAt->copy()->addSeconds($durationSeconds);
        $error       = self::ERROR_MESSAGES[array_rand(self::ERROR_MESSAGES)];

        return [
            'workspace_id'         => $workspaceId,
            'integrationable_type' => $type,
            'integrationable_id'   => $typeId,
            'job_type'             => $jobType,
            'status'               => 'failed',
            'records_processed'    => 0,
            'error_message'        => $error,
            'started_at'           => $startedAt,
            'completed_at'         => $completedAt,
            'scheduled_at'         => $startedAt->copy()->subSeconds(mt_rand(2, 10)),
            'queue'                => $queue,
            'attempt'              => mt_rand(1, 3),
            'timeout_seconds'      => 300,
            'duration_seconds'     => $durationSeconds,
            'created_at'           => $startedAt,
            'updated_at'           => $completedAt,
        ];
    }
}
