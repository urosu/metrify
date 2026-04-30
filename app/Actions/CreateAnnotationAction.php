<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspace\AnnotationService;

/**
 * User-authored chart annotation (note, promotion, expected spike/drop).
 *
 * Delegates to AnnotationService. Idempotent in the sense that each call
 * always produces a new annotation row (annotations are time-stamped events,
 * not configuration — no upsert key).
 *
 * Input:  Workspace, User, payload array
 * Output: int  annotation id
 * Writes: annotations
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see app/Services/Workspace/AnnotationService.php
 */
class CreateAnnotationAction
{
    public function __construct(private readonly AnnotationService $annotations) {}

    /**
     * @param  array{title: string, body?: string, annotation_type: string, scope_type: string, scope_id?: int|null, starts_at: string, ends_at?: string|null, suppress_anomalies?: bool}  $payload
     */
    public function handle(Workspace $workspace, User $user, array $payload): int
    {
        return $this->annotations->create(
            workspaceId: $workspace->id,
            userId: $user->id,
            data: $payload,
        );
    }
}
