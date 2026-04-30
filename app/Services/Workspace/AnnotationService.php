<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CRUD for workspace annotations (user-authored and system-authored).
 *
 * Annotations are time-stamped flags displayed on every time-series chart via
 * ChartAnnotationLayer. Two author types exist:
 *   user   — created via UI; editable + deletable by workspace members.
 *   system — appended by service hooks (OAuth disconnect, COGS update, etc.);
 *            cannot be deleted but individual users can hide them via
 *            `is_hidden_per_user` (a JSONB array of user_ids).
 *
 * Reads:  annotations
 * Writes: annotations
 * Called by: AnnotationController (new), system event hooks (OAuth reconnect,
 *            cost update, algorithm update ingest)
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.8 (annotations table)
 * @see docs/UX.md §5.6.1 (ChartAnnotationLayer)
 */
class AnnotationService
{
    public const USER_TYPES = [
        'user_note',
        'promotion',
        'expected_spike',
        'expected_drop',
    ];

    public const SYSTEM_TYPES = [
        'integration_disconnect',
        'integration_reconnect',
        'attribution_model_change',
        'cogs_update',
        'algorithm_update',
        'migration',
    ];

    /**
     * Create a user-authored annotation.
     *
     * @param  array{title: string, body?: string, annotation_type: string, scope_type: string, scope_id?: int|null, starts_at: string, ends_at?: string|null, suppress_anomalies?: bool}  $data
     */
    public function create(int $workspaceId, int $userId, array $data): int
    {
        return DB::table('annotations')->insertGetId([
            'workspace_id'       => $workspaceId,
            'author_type'        => 'user',
            'author_id'          => $userId,
            'title'              => $data['title'],
            'body'               => $data['body'] ?? null,
            'annotation_type'    => $data['annotation_type'],
            'scope_type'         => $data['scope_type'] ?? 'workspace',
            'scope_id'           => $data['scope_id'] ?? null,
            'starts_at'          => $data['starts_at'],
            'ends_at'            => $data['ends_at'] ?? null,
            'is_hidden_per_user' => '[]',
            'suppress_anomalies' => $data['suppress_anomalies'] ?? false,
            'created_by'         => $userId,
            'updated_by'         => $userId,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /**
     * Update a user-authored annotation.
     *
     * System annotations cannot be updated through this method.
     * Caller must verify the annotation belongs to the workspace and is user-authored.
     *
     * @param  array{title?: string, body?: string, starts_at?: string, ends_at?: string|null, suppress_anomalies?: bool}  $data
     */
    public function update(int $id, int $updatedBy, array $data): void
    {
        $fields = ['updated_at' => now(), 'updated_by' => $updatedBy];

        foreach (['title', 'body', 'starts_at', 'ends_at', 'suppress_anomalies'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = $data[$key];
            }
        }

        DB::table('annotations')
            ->where('id', $id)
            ->where('author_type', 'user')
            ->update($fields);
    }

    /**
     * Delete a user-authored annotation.
     *
     * System annotations must not be deleted — use `hideForUser` instead.
     * Caller must verify workspace ownership.
     */
    public function delete(int $id): void
    {
        DB::table('annotations')
            ->where('id', $id)
            ->where('author_type', 'user')
            ->delete();
    }

    /**
     * Hide a system annotation for a specific user.
     *
     * Adds `userId` to the `is_hidden_per_user` JSONB array.
     * No-op if already hidden for that user.
     */
    public function hideForUser(int $id, int $userId): void
    {
        DB::statement(
            "UPDATE annotations
             SET is_hidden_per_user = (
                 SELECT jsonb_agg(DISTINCT v)
                 FROM jsonb_array_elements(
                     COALESCE(is_hidden_per_user, '[]'::jsonb) || jsonb_build_array(?::bigint)
                 ) AS v
             ),
             updated_at = NOW()
             WHERE id = ? AND author_type = 'system'",
            [$userId, $id]
        );
    }

    /**
     * Return annotations visible on a chart for the given scope and date range.
     *
     * System annotations hidden by the requesting user are excluded.
     *
     * @param  string       $scopeType  Scope type ('workspace', 'store', 'campaign', …)
     * @param  int|null     $scopeId    Scope entity id (null for workspace scope)
     * @param  Carbon       $from       Start of date range
     * @param  Carbon       $to         End of date range
     * @param  int          $userId     Requesting user (used to filter hidden system annotations)
     * @return Collection<int, object>
     */
    public function forChart(
        int $workspaceId,
        string $scopeType,
        ?int $scopeId,
        Carbon $from,
        Carbon $to,
        int $userId,
    ): Collection {
        return DB::table('annotations')
            ->where('workspace_id', $workspaceId)
            ->where('scope_type', $scopeType)
            ->where(fn ($q) => $scopeId !== null
                ? $q->where('scope_id', $scopeId)->orWhereNull('scope_id')
                : $q->whereNull('scope_id')
            )
            ->where('starts_at', '<=', $to)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $from))
            ->whereRaw(
                "NOT (author_type = 'system' AND is_hidden_per_user @> ?::jsonb)",
                [json_encode([$userId])]
            )
            ->orderBy('starts_at')
            ->get([
                'id', 'author_type', 'author_id', 'title', 'body',
                'annotation_type', 'scope_type', 'scope_id',
                'starts_at', 'ends_at', 'suppress_anomalies',
            ]);
    }

    /**
     * Append a system-authored annotation (called by service hooks, not the user).
     *
     * @param  array{title: string, body?: string, scope_type?: string, scope_id?: int|null, starts_at?: string, ends_at?: string|null, suppress_anomalies?: bool}  $payload
     */
    public function appendSystem(int $workspaceId, string $type, array $payload): int
    {
        return DB::table('annotations')->insertGetId([
            'workspace_id'       => $workspaceId,
            'author_type'        => 'system',
            'author_id'          => null,
            'title'              => $payload['title'],
            'body'               => $payload['body'] ?? null,
            'annotation_type'    => $type,
            'scope_type'         => $payload['scope_type'] ?? 'workspace',
            'scope_id'           => $payload['scope_id'] ?? null,
            'starts_at'          => $payload['starts_at'] ?? now()->toDateTimeString(),
            'ends_at'            => $payload['ends_at'] ?? null,
            'is_hidden_per_user' => '[]',
            'suppress_anomalies' => $payload['suppress_anomalies'] ?? false,
            'created_by'         => null,
            'updated_by'         => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }
}
