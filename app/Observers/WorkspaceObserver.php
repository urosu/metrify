<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RefreshHolidaysJob;
use App\Models\Holiday;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches RefreshHolidaysJob when a workspace's country is set for the first time.
 *
 * Why: holidays are populated globally (one row per country per year), but we only
 * generate them for countries that actually have workspaces. When country is first
 * detected (via ccTLD, IP geolocation, or Stripe billing address), we eagerly
 * populate the current year's holidays so DetectAnomaliesJob and chart overlays
 * have data from day one.
 *
 * We only dispatch when:
 *   - country changes from null to a non-null value (first-time detection only)
 *   - no holidays exist yet for that country + current year
 *
 * The yearly RefreshHolidaysJob (Jan 1st) handles subsequent year refreshes.
 * See: app/Jobs/RefreshHolidaysJob.php
 * See: PLANNING.md "holidays"
 */
class WorkspaceObserver
{
    public function updated(Workspace $workspace): void
    {
        // Only fire when country transitions from null → value.
        // Changing country after initial detection is possible but unusual;
        // the user can re-trigger manually. We don't re-dispatch on every
        // country edit to avoid duplicate jobs.
        if (! $workspace->wasChanged('country')) {
            return;
        }

        $newCountry = $workspace->country;

        if ($newCountry === null) {
            return;
        }

        $previousCountry = $workspace->getOriginal('country');

        if ($previousCountry !== null) {
            // Country was already set — don't re-dispatch. If the admin
            // changes a workspace's country, they can trigger a manual sync.
            return;
        }

        $currentYear = (int) now()->format('Y');

        // Skip if holidays are already populated for this country + year.
        $alreadyPopulated = Holiday::where('country_code', $newCountry)
            ->where('year', $currentYear)
            ->exists();

        if ($alreadyPopulated) {
            Log::info('WorkspaceObserver: holidays already populated, skipping dispatch', [
                'country_code' => $newCountry,
                'year'         => $currentYear,
                'workspace_id' => $workspace->id,
            ]);
            return;
        }

        RefreshHolidaysJob::dispatch($newCountry, $currentYear);

        Log::info('WorkspaceObserver: dispatched RefreshHolidaysJob for new workspace country', [
            'country_code' => $newCountry,
            'year'         => $currentYear,
            'workspace_id' => $workspace->id,
        ]);
    }
}
