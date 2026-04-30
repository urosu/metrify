<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\PixelEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for POST /pixel/{workspace}/event
 *
 * Covers:
 *  - 204 on valid payload
 *  - 401 on missing/wrong token
 *  - 422 on invalid event_type
 *  - Dedup: second POST with same event_id returns 204 but inserts only one row
 *  - CORS preflight OPTIONS returns 204 with correct headers
 */
class PixelEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;
    private string    $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->token = 'test_pixel_token_abc123';

        $this->workspace = Workspace::factory()->create([
            'owner_id'             => $user->id,
            'pixel_tracking_token' => $this->token,
            'has_ads'              => true,
        ]);

        WorkspaceUser::factory()->owner()->create([
            'user_id'      => $user->id,
            'workspace_id' => $this->workspace->id,
        ]);
    }

    // ── Valid payload ─────────────────────────────────────────────────────────

    public function test_valid_page_view_returns_204(): void
    {
        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $this->validPayload()
        );

        $response->assertStatus(204);

        $this->assertDatabaseHas('pixel_events', [
            'workspace_id' => $this->workspace->id,
            'event_id'     => 'uuid-test-001',
            'event_type'   => 'page_view',
        ]);
    }

    public function test_all_event_types_are_accepted(): void
    {
        $types = ['page_view', 'add_to_cart', 'begin_checkout', 'purchase', 'custom'];

        foreach ($types as $i => $type) {
            $response = $this->postJson(
                "/pixel/{$this->workspace->slug}/event?token={$this->token}",
                $this->validPayload(['event_id' => "uuid-{$i}", 'event_type' => $type])
            );
            $response->assertStatus(204);
        }

        $this->assertDatabaseCount('pixel_events', count($types));
    }

    // ── Auth failures ─────────────────────────────────────────────────────────

    public function test_missing_token_returns_401(): void
    {
        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event",
            $this->validPayload()
        );

        $response->assertStatus(401);
        $this->assertDatabaseCount('pixel_events', 0);
    }

    public function test_wrong_token_returns_401(): void
    {
        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token=wrong_token",
            $this->validPayload()
        );

        $response->assertStatus(401);
        $this->assertDatabaseCount('pixel_events', 0);
    }

    // ── Validation failures ───────────────────────────────────────────────────

    public function test_invalid_event_type_returns_422(): void
    {
        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $this->validPayload(['event_type' => 'invalid_type'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_type']);
        $this->assertDatabaseCount('pixel_events', 0);
    }

    public function test_missing_url_returns_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['url']);

        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $payload
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);
    }

    public function test_missing_event_id_returns_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['event_id']);

        $response = $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $payload
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_id']);
    }

    // ── Deduplication ─────────────────────────────────────────────────────────

    public function test_duplicate_event_id_does_not_create_second_row(): void
    {
        $payload = $this->validPayload(['event_id' => 'uuid-dedup-test']);

        // First POST — should insert.
        $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $payload
        )->assertStatus(204);

        // Second POST with same event_id — should silently no-op.
        $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $payload
        )->assertStatus(204);

        $this->assertDatabaseCount('pixel_events', 1);
    }

    public function test_same_event_id_different_workspaces_creates_two_rows(): void
    {
        $user2      = User::factory()->create();
        $token2     = 'another_workspace_token_xyz';
        $workspace2 = Workspace::factory()->create([
            'owner_id'             => $user2->id,
            'pixel_tracking_token' => $token2,
        ]);

        $sharedEventId = 'uuid-cross-workspace';

        $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $this->validPayload(['event_id' => $sharedEventId])
        )->assertStatus(204);

        $this->postJson(
            "/pixel/{$workspace2->slug}/event?token={$token2}",
            $this->validPayload(['event_id' => $sharedEventId])
        )->assertStatus(204);

        // Unique constraint is (workspace_id, event_id) so both rows should exist.
        $this->assertDatabaseCount('pixel_events', 2);
    }

    // ── Optional fields ───────────────────────────────────────────────────────

    public function test_utm_and_payload_fields_are_persisted(): void
    {
        $this->postJson(
            "/pixel/{$this->workspace->slug}/event?token={$this->token}",
            $this->validPayload([
                'utm_source'   => 'facebook',
                'utm_medium'   => 'cpc',
                'utm_campaign' => 'summer_sale',
                'session_id'   => 'sess_abc',
                'payload'      => ['value' => 49.99, 'currency' => 'EUR'],
            ])
        )->assertStatus(204);

        $row = DB::table('pixel_events')
            ->where('workspace_id', $this->workspace->id)
            ->where('event_id', 'uuid-test-001')
            ->first();

        $this->assertSame('facebook', $row->utm_source);
        $this->assertSame('cpc', $row->utm_medium);
        $this->assertSame('summer_sale', $row->utm_campaign);
        $this->assertSame('sess_abc', $row->session_id);
    }

    // ── CORS preflight ────────────────────────────────────────────────────────

    public function test_cors_preflight_options_returns_204(): void
    {
        $response = $this->call('OPTIONS', "/pixel/{$this->workspace->slug}/event");

        $response->assertStatus(204);
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'event_id'   => 'uuid-test-001',
            'event_type' => 'page_view',
            'url'        => 'https://mystore.example.com/products/cool-thing',
        ], $overrides);
    }
}
