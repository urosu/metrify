<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Global reference table — NOT tenant-scoped (no workspace_id, no WorkspaceScope).
// One row per country per holiday per year. No per-workspace duplication.
//
// Populated by: RefreshHolidaysJob using azuyalabs/yasumi.
// Refreshed: January 1st each year for all countries with active workspaces.
// Also triggered: on workspace creation if workspace.country has no holidays for current year.
//
// Consumed by: DetectAnomaliesJob (skip detection on holiday dates),
//              ComputeMetricBaselinesJob (exclude holiday dates from baseline window),
//              chart event overlay on time-series charts (Phase 1).
// See: PLANNING.md "holidays"
class Holiday extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'country_code',
        'date',
        'name',
        'year',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
