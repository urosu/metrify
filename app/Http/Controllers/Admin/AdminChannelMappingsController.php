<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin global channel mappings — list, create, update, delete.
 *
 * Purpose: Manages global (workspace_id = NULL) channel mappings and surfaces
 *          unrecognized UTM sources across all workspaces (last 90 days, top 20).
 *          Workspace-scoped mappings are handled by Integrations\ChannelMappingsController.
 *
 * Reads:  channel_mappings (global rows), orders.attribution_last_touch (unrecognized query).
 * Writes: channel_mappings (store, update, destroy).
 * Callers: routes/web.php admin group (/admin/channel-mappings and sub-routes).
 *
 * @see docs/planning/backend.md#6
 */
class AdminChannelMappingsController extends Controller
{
    /**
     * List global channel mappings and surface unrecognized UTM sources.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = ChannelMapping::whereNull('workspace_id')
            ->orderBy('channel_type')
            ->orderBy('utm_source_pattern')
            ->orderBy('utm_medium_pattern');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('utm_source_pattern', 'ilike', "%{$search}%")
                  ->orWhere('channel_name', 'ilike', "%{$search}%")
                  ->orWhere('channel_type', 'ilike', "%{$search}%");
            });
        }

        $mappings = $query->paginate(50)->through(fn (ChannelMapping $m) => [
            'id'                  => $m->id,
            'utm_source_pattern'  => $m->utm_source_pattern,
            'utm_medium_pattern'  => $m->utm_medium_pattern,
            'channel_name'        => $m->channel_name,
            'channel_type'        => $m->channel_type,
            'is_global'           => $m->is_global,
            'created_at'          => $m->created_at->toISOString(),
        ]);

        // Unrecognized sources across all workspaces (last 90 days, top 20)
        $unrecognized = DB::select(<<<'SQL'
            SELECT
                LOWER(attribution_last_touch->>'source')  AS source,
                LOWER(attribution_last_touch->>'medium')  AS medium,
                COUNT(*)                                   AS order_count,
                COUNT(DISTINCT workspace_id)               AS workspace_count
            FROM orders
            WHERE status IN ('completed', 'processing')
              AND attribution_source IN ('pys', 'wc_native')
              AND attribution_last_touch IS NOT NULL
              AND attribution_last_touch->>'source' IS NOT NULL
              AND (attribution_last_touch->>'channel' IS NULL OR attribution_last_touch->>'channel' = '')
              AND occurred_at >= NOW() - INTERVAL '90 days'
            GROUP BY 1, 2
            ORDER BY order_count DESC
            LIMIT 20
        SQL);

        return Inertia::render('Admin/ChannelMappings', [
            'mappings'     => $mappings,
            'unrecognized' => $unrecognized,
            'filters'      => ['search' => $search],
        ]);
    }

    /**
     * Create a new global channel mapping.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'utm_source_pattern' => ['required', 'string', 'max:255'],
            'utm_medium_pattern' => ['nullable', 'string', 'max:255'],
            'channel_name'       => ['required', 'string', 'max:120'],
            'channel_type'       => ['required', 'string', 'in:email,paid_social,paid_search,organic_search,organic_social,direct,referral,affiliate,sms,other'],
        ]);

        $source = strtolower(trim($validated['utm_source_pattern']));
        $medium = isset($validated['utm_medium_pattern']) && $validated['utm_medium_pattern'] !== ''
            ? strtolower(trim($validated['utm_medium_pattern']))
            : null;

        try {
            ChannelMapping::create([
                'workspace_id'       => null,
                'utm_source_pattern' => $source,
                'utm_medium_pattern' => $medium,
                'channel_name'       => $validated['channel_name'],
                'channel_type'       => $validated['channel_type'],
                'is_global'          => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '23505')) {
                return back()->withErrors([
                    'utm_source_pattern' => 'A global mapping for this source/medium combination already exists.',
                ]);
            }
            throw $e;
        }

        return back()->with('success', "Mapping created: {$source} → {$validated['channel_name']}");
    }

    /**
     * Update an existing global channel mapping.
     */
    public function update(Request $request, ChannelMapping $channelMapping): RedirectResponse
    {
        abort_unless($channelMapping->workspace_id === null, 404);

        $validated = $request->validate([
            'utm_source_pattern' => ['required', 'string', 'max:255'],
            'utm_medium_pattern' => ['nullable', 'string', 'max:255'],
            'channel_name'       => ['required', 'string', 'max:120'],
            'channel_type'       => ['required', 'string', 'in:email,paid_social,paid_search,organic_search,organic_social,direct,referral,affiliate,sms,other'],
        ]);

        $source = strtolower(trim($validated['utm_source_pattern']));
        $medium = isset($validated['utm_medium_pattern']) && $validated['utm_medium_pattern'] !== ''
            ? strtolower(trim($validated['utm_medium_pattern']))
            : null;

        try {
            $channelMapping->update([
                'utm_source_pattern' => $source,
                'utm_medium_pattern' => $medium,
                'channel_name'       => $validated['channel_name'],
                'channel_type'       => $validated['channel_type'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '23505')) {
                return back()->withErrors([
                    'utm_source_pattern' => 'A global mapping for this source/medium combination already exists.',
                ]);
            }
            throw $e;
        }

        return back()->with('success', "Mapping updated: {$source} → {$validated['channel_name']}");
    }

    /**
     * Delete a global channel mapping.
     */
    public function destroy(ChannelMapping $channelMapping): RedirectResponse
    {
        abort_unless($channelMapping->workspace_id === null, 404);

        $name = $channelMapping->channel_name;
        $channelMapping->delete();

        return back()->with('success', "Mapping deleted: {$name}");
    }
}
