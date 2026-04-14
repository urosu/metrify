<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([WorkspaceScope::class])]
class AdInsight extends Model
{
    use HasFactory;
    protected $fillable = [
        'workspace_id',
        'ad_account_id',
        'level',
        'campaign_id',
        'adset_id',
        'ad_id',
        'date',
        'hour',
        'spend',
        'spend_in_reporting_currency',
        'impressions',
        'clicks',
        'reach',
        'frequency',
        'platform_conversions',
        'platform_conversions_value',
        'search_impression_share',
        'platform_roas',
        'currency',
        'raw_insights',
        'raw_insights_api_version',
    ];

    // Why ctr/cpc are not stored: computed on the fly with NULLIF to avoid stale cached values.
    // CTR = clicks / NULLIF(impressions, 0), CPC = spend / NULLIF(clicks, 0)
    // See: PLANNING.md "ad_insights — computed columns"

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'spend' => 'decimal:4',
            'spend_in_reporting_currency' => 'decimal:4',
            'frequency' => 'decimal:2',
            'platform_conversions' => 'decimal:2',
            'platform_conversions_value' => 'decimal:4',
            'search_impression_share' => 'decimal:4',
            'platform_roas' => 'decimal:4',
            'raw_insights' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function adset(): BelongsTo
    {
        return $this->belongsTo(Adset::class);
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
