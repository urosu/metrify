<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PixelEvents;

use App\Models\Store;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\PixelEvents\EventOrderCorrelator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for EventOrderCorrelator.
 *
 * Covers:
 *  - Session ID match (Pass 1) — order with pixel_session_id metafield matches pixel event
 *  - Temporal proximity match (Pass 2) — order matched to closest checkout event within ±30 min
 *  - Pass 2 does not re-match orders already correlated in Pass 1
 *  - Returns 0 when no matching events exist
 *  - Temporal match outside ±30-minute window is ignored
 */
class EventOrderCorrelatorTest extends TestCase
{
    use RefreshDatabase;

    private EventOrderCorrelator $correlator;
    private Workspace            $workspace;
    private Store                $store;
    private int                  $workspaceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->correlator = app(EventOrderCorrelator::class);

        $user = User::factory()->create();

        $this->workspace   = Workspace::factory()->create(['owner_id' => $user->id]);
        $this->workspaceId = $this->workspace->id;

        $this->store = Store::factory()->create(['workspace_id' => $this->workspaceId]);

        WorkspaceUser::factory()->owner()->create([
            'user_id'      => $user->id,
            'workspace_id' => $this->workspaceId,
        ]);
    }

    // ── Pass 1: session_id match ──────────────────────────────────────────────

    public function test_session_id_match_writes_correlation(): void
    {
        $sessionId = 'sess_abc_123';
        $now       = Carbon::now();

        // Insert a pixel event with a known session_id.
        $pixelEventId = DB::table('pixel_events')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'event_id'     => 'evt-uuid-001',
            'event_type'   => 'begin_checkout',
            'session_id'   => $sessionId,
            'occurred_at'  => $now->copy()->subMinutes(5)->toDateTimeString(),
            'url'          => 'https://mystore.com/checkout',
            'payload'      => '{}',
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        // Insert an order with a pixel_session_id metafield referencing that session.
        $orderId = DB::table('orders')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'store_id'     => $this->store->id,
            'external_id'  => 'ext-001',
            'status'       => 'completed',
            'currency'     => 'EUR',
            'total'        => 99.00,
            'occurred_at'  => now()->toDateTimeString(),
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        DB::table('order_metafields')->insert([
            'workspace_id' => $this->workspaceId,
            'order_id'     => $orderId,
            'key'          => 'pixel_session_id',
            'value'        => $sessionId,
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        $written = $this->correlator->correlate(
            $this->workspaceId,
            $now->copy()->subHour(),
            $now->copy()->addHour()
        );

        $this->assertSame(1, $written);

        $this->assertDatabaseHas('pixel_order_correlations', [
            'workspace_id'   => $this->workspaceId,
            'order_id'       => $orderId,
            'pixel_event_id' => $pixelEventId,
            'session_id'     => $sessionId,
            'method'         => 'session_id',
        ]);
    }

    // ── Pass 2: temporal proximity match ─────────────────────────────────────

    public function test_temporal_proximity_match_writes_correlation(): void
    {
        $now = Carbon::now();

        // Pixel event 10 minutes before the order — within ±30 min window.
        $pixelEventId = DB::table('pixel_events')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'event_id'     => 'evt-uuid-002',
            'event_type'   => 'begin_checkout',
            'session_id'   => 'sess-proximity-001',
            'occurred_at'  => $now->copy()->subMinutes(10)->toDateTimeString(),
            'url'          => 'https://mystore.com/checkout',
            'payload'      => '{}',
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'store_id'     => $this->store->id,
            'external_id'  => 'ext-002',
            'status'       => 'completed',
            'currency'     => 'EUR',
            'total'        => 49.00,
            'occurred_at'  => $now->toDateTimeString(),
            'created_at'   => $now->toDateTimeString(),
            'updated_at'   => $now->toDateTimeString(),
        ]);

        $written = $this->correlator->correlate(
            $this->workspaceId,
            $now->copy()->subHour(),
            $now->copy()->addHour()
        );

        $this->assertSame(1, $written);

        $this->assertDatabaseHas('pixel_order_correlations', [
            'workspace_id'   => $this->workspaceId,
            'order_id'       => $orderId,
            'pixel_event_id' => $pixelEventId,
            'method'         => 'ip_proximity',
        ]);
    }

    // ── Pass 1 match prevents Pass 2 double-match ─────────────────────────────

    public function test_session_matched_order_is_not_re_matched_by_proximity(): void
    {
        $sessionId = 'sess-double-match';
        $now       = Carbon::now();

        $pixelEventId = DB::table('pixel_events')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'event_id'     => 'evt-uuid-003',
            'event_type'   => 'begin_checkout',
            'session_id'   => $sessionId,
            'occurred_at'  => $now->copy()->subMinutes(5)->toDateTimeString(),
            'url'          => 'https://mystore.com/checkout',
            'payload'      => '{}',
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'store_id'     => $this->store->id,
            'external_id'  => 'ext-003',
            'status'       => 'completed',
            'currency'     => 'EUR',
            'total'        => 75.00,
            'occurred_at'  => $now->toDateTimeString(),
            'created_at'   => $now->toDateTimeString(),
            'updated_at'   => $now->toDateTimeString(),
        ]);

        DB::table('order_metafields')->insert([
            'workspace_id' => $this->workspaceId,
            'order_id'     => $orderId,
            'key'          => 'pixel_session_id',
            'value'        => $sessionId,
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        $written = $this->correlator->correlate(
            $this->workspaceId,
            $now->copy()->subHour(),
            $now->copy()->addHour()
        );

        // Should be exactly 1 (Pass 1), not 2.
        $this->assertSame(1, $written);
        $this->assertDatabaseCount('pixel_order_correlations', 1);
    }

    // ── No match ──────────────────────────────────────────────────────────────

    public function test_no_match_returns_zero(): void
    {
        $written = $this->correlator->correlate(
            $this->workspaceId,
            Carbon::now()->subDay(),
            Carbon::now()
        );

        $this->assertSame(0, $written);
        $this->assertDatabaseCount('pixel_order_correlations', 0);
    }

    // ── Temporal match outside ±30-minute window ──────────────────────────────

    public function test_temporal_match_outside_30_min_window_is_ignored(): void
    {
        $now = Carbon::now();

        // Pixel event 45 minutes before the order — outside the ±30 min window.
        DB::table('pixel_events')->insert([
            'workspace_id' => $this->workspaceId,
            'event_id'     => 'evt-uuid-004',
            'event_type'   => 'begin_checkout',
            'session_id'   => null,
            'occurred_at'  => $now->copy()->subMinutes(45)->toDateTimeString(),
            'url'          => 'https://mystore.com/checkout',
            'payload'      => '{}',
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);

        DB::table('orders')->insert([
            'workspace_id' => $this->workspaceId,
            'store_id'     => $this->store->id,
            'external_id'  => 'ext-004',
            'status'       => 'completed',
            'currency'     => 'EUR',
            'total'        => 30.00,
            'occurred_at'  => $now->toDateTimeString(),
            'created_at'   => $now->toDateTimeString(),
            'updated_at'   => $now->toDateTimeString(),
        ]);

        $written = $this->correlator->correlate(
            $this->workspaceId,
            $now->copy()->subHours(2),
            $now->copy()->addHour()
        );

        $this->assertSame(0, $written);
        $this->assertDatabaseCount('pixel_order_correlations', 0);
    }
}
