<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Peer-cohort benchmark snapshot.
 *
 * Rows are written by ComputeBenchmarksJob (daily). Each row represents the
 * P25/P50/P75 distribution of a given metric within a vertical for a given period.
 *
 * Privacy rule: rows with sample_size < 5 are never written.
 *
 * @property int    $id
 * @property string $vertical
 * @property string $metric
 * @property string $period
 * @property float|null $p25
 * @property float|null $p50
 * @property float|null $p75
 * @property int    $sample_size
 * @property \Illuminate\Support\Carbon $computed_at
 */
class BenchmarkSnapshot extends Model
{
    protected $fillable = [
        'vertical',
        'metric',
        'period',
        'p25',
        'p50',
        'p75',
        'sample_size',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'p25'         => 'decimal:4',
            'p50'         => 'decimal:4',
            'p75'         => 'decimal:4',
            'sample_size' => 'integer',
            'computed_at' => 'datetime',
        ];
    }
}
