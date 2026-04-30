<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdAccount;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds historical_import_jobs with completed backfill records for each connected
 * integration in the demo workspace.
 *
 * StoreSeeder already inserts one completed woocommerce_orders job per Store.
 * This seeder adds completed imports for:
 *   - ad_insights per AdAccount (Facebook + Google)
 *   - gsc per SearchConsoleProperty
 *
 * This ensures the onboarding "Import complete" state is realistic for all
 * integration types, not just WooCommerce stores.
 *
 * Writes: historical_import_jobs
 * Called by: DatabaseSeeder (dev/staging only, after AdSeeder + SearchConsoleSeeder)
 *
 * @see docs/planning/schema.md §1.10 historical_import_jobs
 * @see database/seeders/StoreSeeder.php (seeds woocommerce_orders rows per store)
 */
class HistoricalImportJobsSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        if (! $workspace) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        // ── Ad Accounts (ad_insights import) ─────────────────────────────────────
        // Each Facebook/Google ad account gets a completed historical ad_insights import
        // covering 12 months of data — realistic for a new account connection.
        $adAccounts = AdAccount::where('workspace_id', $workspace->id)->get();

        foreach ($adAccounts as $account) {
            $startedAt   = now()->subHours(mt_rand(6, 48));
            $completedAt = $startedAt->copy()->addMinutes(mt_rand(15, 90));
            $estimated   = mt_rand(8000, 25000);

            $rows[] = [
                'workspace_id'          => $workspace->id,
                'integrationable_type'  => AdAccount::class,
                'integrationable_id'    => $account->id,
                'job_type'              => 'ad_insights',
                'status'                => 'completed',
                'from_date'             => now()->subYear()->toDateString(),
                'to_date'               => now()->toDateString(),
                'total_rows_estimated'  => $estimated,
                'total_rows_imported'   => $estimated,
                'progress_pct'          => 100,
                'checkpoint'            => json_encode(['cursor' => null, 'done' => true]),
                'started_at'            => $startedAt,
                'completed_at'          => $completedAt,
                'duration_seconds'      => (int) $startedAt->diffInSeconds($completedAt),
                'error_message'         => null,
                'created_at'            => $startedAt,
                'updated_at'            => $completedAt,
            ];
        }

        // ── Search Console Properties (gsc import) ────────────────────────────────
        // Each GSC property gets a completed historical gsc import covering 16 months
        // (GSC's maximum historical data window).
        $gscProperties = SearchConsoleProperty::where('workspace_id', $workspace->id)->get();

        foreach ($gscProperties as $property) {
            $startedAt   = now()->subHours(mt_rand(4, 24));
            $completedAt = $startedAt->copy()->addMinutes(mt_rand(5, 30));
            $estimated   = mt_rand(3000, 12000);

            $rows[] = [
                'workspace_id'          => $workspace->id,
                'integrationable_type'  => SearchConsoleProperty::class,
                'integrationable_id'    => $property->id,
                'job_type'              => 'gsc',
                'status'                => 'completed',
                'from_date'             => now()->subMonths(16)->toDateString(),
                'to_date'               => now()->toDateString(),
                'total_rows_estimated'  => $estimated,
                'total_rows_imported'   => $estimated,
                'progress_pct'          => 100,
                'checkpoint'            => json_encode(['cursor' => null, 'done' => true]),
                'started_at'            => $startedAt,
                'completed_at'          => $completedAt,
                'duration_seconds'      => (int) $startedAt->diffInSeconds($completedAt),
                'error_message'         => null,
                'created_at'            => $startedAt,
                'updated_at'            => $completedAt,
            ];
        }

        if (! empty($rows)) {
            DB::table('historical_import_jobs')->insertOrIgnore($rows);
        }
    }
}
