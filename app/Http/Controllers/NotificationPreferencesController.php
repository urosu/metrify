<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages per-user, per-workspace notification delivery preferences.
 *
 * Preferences are seeded from sensible defaults on first load (no rows yet).
 * Defaults per PLANNING.md:
 *   critical: email immediate + in-app immediate
 *   high:     email daily_digest + in-app immediate
 *   medium:   in-app immediate + email disabled (daily_digest when re-enabled)
 *   low:      in-app immediate + email disabled (daily_digest when re-enabled)
 *
 * Quiet hours apply to all channels/severities except critical (which always fires immediately).
 * Default quiet hours: 22:00-08:00 workspace timezone.
 *
 * Related: app/Models/NotificationPreference.php
 * See: PLANNING.md "notification_preferences" + "Alert Notification Strategy"
 */
class NotificationPreferencesController extends Controller
{
    private const CHANNELS  = ['email', 'in_app'];
    private const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    /**
     * Default enabled state and delivery mode per (severity, channel).
     *
     * Why: critical always fires; high email uses digest to reduce noise; medium/low email opt-in.
     * See: PLANNING.md "Sensible defaults (95% of users never change)"
     */
    private const DEFAULTS = [
        ['severity' => 'critical', 'channel' => 'email',  'enabled' => true,  'delivery_mode' => 'immediate'],
        ['severity' => 'critical', 'channel' => 'in_app', 'enabled' => true,  'delivery_mode' => 'immediate'],
        ['severity' => 'high',     'channel' => 'email',  'enabled' => true,  'delivery_mode' => 'daily_digest'],
        ['severity' => 'high',     'channel' => 'in_app', 'enabled' => true,  'delivery_mode' => 'immediate'],
        ['severity' => 'medium',   'channel' => 'email',  'enabled' => false, 'delivery_mode' => 'daily_digest'],
        ['severity' => 'medium',   'channel' => 'in_app', 'enabled' => true,  'delivery_mode' => 'immediate'],
        ['severity' => 'low',      'channel' => 'email',  'enabled' => false, 'delivery_mode' => 'daily_digest'],
        ['severity' => 'low',      'channel' => 'in_app', 'enabled' => true,  'delivery_mode' => 'immediate'],
    ];

    public function show(Request $request): Response
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $user      = $request->user();

        // Load existing rows keyed by "{channel}_{severity}" for fast lookup
        $existing = NotificationPreference::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (NotificationPreference $p) => "{$p->channel}_{$p->severity}");

        // Build full 8-row matrix, filling gaps with defaults
        $preferences = collect(self::DEFAULTS)->map(function (array $default) use ($existing): array {
            $key  = "{$default['channel']}_{$default['severity']}";
            $pref = $existing->get($key);

            return [
                'channel'       => $default['channel'],
                'severity'      => $default['severity'],
                'enabled'       => $pref !== null ? $pref->enabled       : $default['enabled'],
                'delivery_mode' => $pref !== null ? $pref->delivery_mode : $default['delivery_mode'],
            ];
        })->all();

        // Quiet hours are shared across all rows for this user — read from any saved row
        $anyPref = $existing->first();

        return Inertia::render('Settings/Notifications', [
            'preferences'       => $preferences,
            'quiet_hours_start' => $anyPref?->quiet_hours_start,
            'quiet_hours_end'   => $anyPref?->quiet_hours_end,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $user      = $request->user();

        $validated = $request->validate([
            'preferences'              => ['required', 'array', 'size:8'],
            'preferences.*.channel'       => ['required', Rule::in(self::CHANNELS)],
            'preferences.*.severity'      => ['required', Rule::in(self::SEVERITIES)],
            'preferences.*.enabled'       => ['required', 'boolean'],
            'preferences.*.delivery_mode' => ['required', Rule::in(['immediate', 'daily_digest', 'weekly_digest'])],
            'quiet_hours_start'        => ['nullable', 'date_format:H:i'],
            'quiet_hours_end'          => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::withoutGlobalScopes()
                ->updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'user_id'      => $user->id,
                        'channel'      => $pref['channel'],
                        'severity'     => $pref['severity'],
                    ],
                    [
                        'enabled'           => $pref['enabled'],
                        'delivery_mode'     => $pref['delivery_mode'],
                        'quiet_hours_start' => $validated['quiet_hours_start'],
                        'quiet_hours_end'   => $validated['quiet_hours_end'],
                    ]
                );
        }

        return back()->with('success', 'Notification preferences saved.');
    }
}
