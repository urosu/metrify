<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Server-side pixel event.
 *
 * Captured via POST /pixel/{workspace}/event.
 * Deduplicated on (workspace_id, event_id) — the same UUID should not produce
 * two rows whether it arrives from the browser pixel or a server-side relay.
 *
 * @property int         $id
 * @property int         $workspace_id
 * @property int|null    $store_id
 * @property string      $event_id        Client-generated UUID
 * @property string      $event_type      page_view|add_to_cart|begin_checkout|purchase|custom
 * @property \Carbon\Carbon $occurred_at
 * @property string|null $session_id
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property string      $url
 * @property string|null $referrer
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $utm_term
 * @property array       $payload
 */
#[ScopedBy([WorkspaceScope::class])]
class PixelEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'event_id',
        'event_type',
        'occurred_at',
        'session_id',
        'user_agent',
        'ip_address',
        'url',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload'     => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
