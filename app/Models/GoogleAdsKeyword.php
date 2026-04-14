<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Phase 3: keyword cannibalization detection — GSC top-10 keywords also in Google Ads.
// Table created in Phase 0 per data-capture strategy. Feature not yet built.
// Related: PLANNING.md "google_ads_keywords" + "Cross-Channel Page Enhancements — Campaigns page"
#[ScopedBy([WorkspaceScope::class])]
class GoogleAdsKeyword extends Model
{
    protected $fillable = [
        'workspace_id',
        'ad_account_id',
        'ad_group_id',
        'keyword_text',
        'match_type',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
}
