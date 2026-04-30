<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WorkspaceContext;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * JournalController — Monthly daily-grain KPI log with activity annotations.
 *
 * Serves a full-month table (one row per day) with ad spend, revenue, orders,
 * items, IPO, % in marketing, AOV, ROAS, and user-authored activity notes.
 * Notes are overlaid as vertical annotation lines on the metric chart above
 * the table (via ChartAnnotationLayer).
 *
 * Reads:  WorkspaceContext (tenant), ?month=YYYY-MM query param
 * Writes: POST /journal/notes  (mock — returns 204; real DB write in L3)
 * Called by:
 *   GET  /{workspace:slug}/journal
 *   POST /{workspace:slug}/journal/notes
 *
 * @see docs/competitors/_research_daily_journal.md
 * @see docs/UX.md §5.6 ChartAnnotationLayer
 * @see docs/planning/backend.md
 */
class JournalController extends Controller
{
    /** Render the daily journal page for the requested month. */
    public function index(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::withoutGlobalScopes()->findOrFail($workspaceId);

        // Parse requested month; default to current month (April 2026 in demo).
        $monthParam = $request->query('month');
        try {
            $month = $monthParam
                ? Carbon::createFromFormat('Y-m', (string) $monthParam)->startOfMonth()
                : Carbon::create(2026, 4, 1)->startOfMonth();
        } catch (\Throwable) {
            $month = Carbon::create(2026, 4, 1)->startOfMonth();
        }

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return Inertia::render('Journal/Index', [
            'month'      => $month->format('Y-m'),
            'prev_month' => $prevMonth,
            'next_month' => $nextMonth,
            'days'       => self::mockDays($month),
            'notes'      => self::mockNotes(),
            'journal_stores' => [
                ['id' => 1, 'name' => 'Main Store', 'slug' => $workspace->slug],
            ],
        ]);
    }

    /**
     * Mock note save — echoes back the note with a generated ID.
     * Real implementation writes to a `journal_notes` table (L3).
     */
    public function storeNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'     => 'required|date_format:Y-m-d',
            'text'     => 'required|string|max:500',
            'category' => 'required|in:sale,promo,site_change,external,other',
        ]);

        // Mock: return the note as if persisted.
        return response()->json([
            'id'       => 'note-' . uniqid(),
            'date'     => $validated['date'],
            'text'     => $validated['text'],
            'category' => $validated['category'],
            'author'   => auth()->check() ? auth()->user()->name : 'You',
        ], 201);
    }

    /**
     * Mock delete — real implementation soft-deletes the journal_notes row.
     */
    public function destroyNote(string $noteId): JsonResponse
    {
        return response()->json(['deleted' => $noteId]);
    }

    // ── Mock data ──────────────────────────────────────────────────────────────

    /**
     * 30 daily rows for April 2026 with realistic ecommerce KPIs.
     * Ad spend: $400–$1,500/day. Revenue: $2,000–$8,000/day. Orders: 30–80/day.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function mockDays(Carbon $month): array
    {
        // Seeded data for April 2026 so the numbers correlate with the notes.
        $seed = [
            // [ad_spend, revenue, orders, items]
            [620,  2800,  38, 54],   // Apr 1
            [540,  2400,  33, 47],   // Apr 2
            [490,  2200,  30, 42],   // Apr 3
            [510,  2350,  31, 44],   // Apr 4
            [580,  2650,  36, 52],   // Apr 5
            [430,  1950,  28, 38],   // Apr 6 — FB Pixel issue (low)
            [470,  2100,  30, 41],   // Apr 7
            [600,  2900,  39, 57],   // Apr 8
            [650,  3200,  42, 62],   // Apr 9
            [700,  3500,  46, 68],   // Apr 10
            [750,  3800,  50, 74],   // Apr 11
            [1400, 7200,  72, 108],  // Apr 12 — Spring Sale starts (spike)
            [1500, 8100,  80, 124],  // Apr 13 — Sale continues (peak)
            [1380, 7400,  74, 112],  // Apr 14 — Sale day 3
            [1100, 5800,  62, 90],   // Apr 15 — Sale tapering
            [900,  4500,  55, 80],   // Apr 16
            [820,  4100,  52, 75],   // Apr 17
            [750,  3800,  48, 69],   // Apr 18
            [680,  3400,  44, 63],   // Apr 19
            [510,  2300,  32, 44],   // Apr 20 — Site redesign deployed (dip)
            [490,  2100,  29, 40],   // Apr 21 — Post-redesign dip
            [540,  2500,  34, 49],   // Apr 22 — Recovery starts
            [1050, 5200,  61, 89],   // Apr 23 — External press mention (surge)
            [1100, 5600,  64, 94],   // Apr 24 — Press effect
            [950,  4700,  57, 83],   // Apr 25
            [870,  4200,  52, 76],   // Apr 26
            [780,  3900,  49, 72],   // Apr 27
            [720,  3600,  46, 67],   // Apr 28
            [660,  3200,  43, 62],   // Apr 29
            [600,  2900,  39, 57],   // Apr 30
        ];

        $days = [];
        $daysInMonth = $month->daysInMonth;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date   = $month->copy()->day($d);
            $row    = $seed[$d - 1] ?? [600, 3000, 40, 58];
            [$spend, $revenue, $orders, $items] = $row;

            // Add small jitter so it looks natural, but keep seeded values stable.
            $ipo    = $orders > 0 ? round($items / $orders, 2) : 0;
            $pct_marketing = $revenue > 0 ? round(($spend / $revenue) * 100, 1) : 0;
            $aov    = $orders > 0 ? round($revenue / $orders, 2) : 0;
            $roas   = $spend > 0 ? round($revenue / $spend, 2) : 0;

            $days[] = [
                'date'          => $date->format('Y-m-d'),
                'day_of_week'   => $date->format('D'),  // Mon, Tue, etc.
                'day_num'       => $d,
                'ad_spend'      => $spend,
                'revenue'       => $revenue,
                'orders'        => $orders,
                'items'         => $items,
                'ipo'           => $ipo,
                'pct_marketing' => $pct_marketing,
                'aov'           => $aov,
                'roas'          => $roas,
            ];
        }

        return $days;
    }

    /**
     * 7 activity notes for April 2026 that correlate with visible chart movements.
     * See _research_daily_journal.md §6 for category color mapping.
     *
     * @return array<int, array<string, string>>
     */
    private static function mockNotes(): array
    {
        return [
            [
                'id'       => 'note-001',
                'date'     => '2026-04-06',
                'text'     => 'FB Pixel issue discovered — tracking loss ~40%. Fixed late evening.',
                'category' => 'site_change',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-002',
                'date'     => '2026-04-12',
                'text'     => 'Spring Sale starts — 20% sitewide discount. Email sent to 18k subscribers.',
                'category' => 'sale',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-003',
                'date'     => '2026-04-13',
                'text'     => 'Sale day 2 — peak traffic. Flash promo added on checkout (extra 5%).',
                'category' => 'promo',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-004',
                'date'     => '2026-04-15',
                'text'     => 'Spring Sale ends. Extended 24h due to strong demand.',
                'category' => 'sale',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-005',
                'date'     => '2026-04-20',
                'text'     => 'Site redesign deployed — new homepage, new PDP layout. CR expected to dip short-term.',
                'category' => 'site_change',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-006',
                'date'     => '2026-04-23',
                'text'     => 'Featured in TechCrunch "Top Eco Brands" list. Significant organic traffic surge.',
                'category' => 'external',
                'author'   => 'Demo User',
            ],
            [
                'id'       => 'note-007',
                'date'     => '2026-04-28',
                'text'     => 'Q2 ad budget approved — increasing Facebook daily budget from $700 → $1,100.',
                'category' => 'other',
                'author'   => 'Demo User',
            ],
        ];
    }
}
