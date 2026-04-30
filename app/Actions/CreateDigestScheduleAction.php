<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Create or replace a workspace's email digest schedule.
 *
 * One digest schedule per workspace (upsert on workspace_id). Setting
 * `frequency = 'off'` disables delivery without deleting the row — the
 * existing config is preserved for when the user re-enables.
 *
 * Input:  Workspace, schedule data
 * Output: int  digest_schedules.id
 * Writes: digest_schedules
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see docs/planning/schema.md §1.8 (digest_schedules table)
 */
class CreateDigestScheduleAction
{
    /**
     * @param  array{frequency: string, day_of_week?: int|null, day_of_month?: int|null, send_at_hour: int, recipients: string[], content_pages: string[]}  $data
     */
    public function handle(Workspace $workspace, array $data): int
    {
        DB::table('digest_schedules')->upsert(
            [[
                'workspace_id'     => $workspace->id,
                'frequency'        => $data['frequency'],
                'day_of_week'      => $data['day_of_week'] ?? null,
                'day_of_month'     => $data['day_of_month'] ?? null,
                'send_at_hour'     => $data['send_at_hour'],
                'recipients'       => json_encode($data['recipients'] ?? []),
                'content_pages'    => json_encode($data['content_pages'] ?? []),
                'last_sent_at'     => null,
                'last_sent_status' => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]],
            ['workspace_id'],
            ['frequency', 'day_of_week', 'day_of_month', 'send_at_hour', 'recipients', 'content_pages', 'updated_at'],
        );

        return (int) DB::table('digest_schedules')
            ->where('workspace_id', $workspace->id)
            ->value('id');
    }
}
