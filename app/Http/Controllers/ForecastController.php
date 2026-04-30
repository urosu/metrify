<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Workspace;
use App\Services\Forecasting\RevenueForecastService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoint for the Dashboard's forecast chart.
 *
 * GET /{workspace}/api/forecast?horizon=30|90
 *
 * Response shape:
 * {
 *   "forecast": {
 *     "points":       [ { "date": "2026-05-01", "point": 1234.56, "lower": 900.00, "upper": 1500.00 }, … ],
 *     "total_30d":    37000.00,
 *     "total_90d":    112000.00,
 *     "history_days": 365
 *   },
 *   "holidays": [
 *     { "id": 42, "name": "Christmas Day", "date": "2026-12-25" }, …
 *   ]
 * }
 *
 * Holidays are the upcoming ones (today … last forecast date) for the
 * workspace's primary_country_code, type 'public' only, with overlay=true
 * semantics (any holiday that is in the DB counts — the overlay toggle is
 * handled on the frontend via the existing Holidays page).
 *
 * @see RevenueForecastService
 * @see resources/js/Pages/Dashboard.tsx  — consumes this endpoint
 */
class ForecastController extends Controller
{
    public function __construct(
        private readonly RevenueForecastService $forecaster,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::withoutGlobalScopes()->findOrFail($workspaceId);

        $horizon = (int) $request->input('horizon', 90);
        $horizon = in_array($horizon, [30, 90], true) ? $horizon : 90;

        $result = $this->forecaster->forecast($workspaceId, $horizon);

        // Upcoming holidays in the forecast window for the workspace's country.
        $holidays = $this->upcomingHolidays(
            $workspace->primary_country_code ?? 'US',
            now()->addDays(1)->toDateString(),
            now()->addDays($horizon)->toDateString(),
        );

        return response()->json([
            'forecast' => $result->toArray(),
            'holidays' => $holidays,
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string, date: string}>
     */
    private function upcomingHolidays(string $countryCode, string $from, string $to): array
    {
        return Holiday::where('country_code', $countryCode)
            ->whereBetween('date', [$from, $to])
            ->where('type', 'public')
            ->orderBy('date')
            ->get(['id', 'name', 'date'])
            ->map(fn (Holiday $h) => [
                'id'   => $h->id,
                'name' => $h->name,
                'date' => $h->date->toDateString(),
            ])
            ->values()
            ->all();
    }
}
