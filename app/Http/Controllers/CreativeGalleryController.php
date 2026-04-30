<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\WorkspaceTarget;
use App\Services\Ads\AdsQueryService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Creative Gallery — serves GET /{workspace:slug}/ads/creatives.
 *
 * Best-creatives deep view: trophy strip (Triple Whale Creative Highlights pattern),
 * gallery / list views, in-page filters (platform / format / grade / status),
 * and Klaviyo top-performers section.
 *
 * Props emitted: has_ad_accounts, ad_accounts, creative_cards,
 *   workspace_target_roas, from, to, platform, campaign_id, adset_id,
 *   limit, sort, status, view, roas_threshold, klaviyo_performers.
 *
 * creative_cards includes: composite_score, triage_bucket, prior_roas,
 *   rank_curr, rank_prev, momentum_dir, platform_cpa, format, tags,
 *   days_running.
 *
 * Reads: ad_accounts, workspace_targets; heavy work in AdsQueryService (real path).
 *        Mock data path used when no ad accounts are connected.
 * Writes: nothing.
 * Called by: GET /{workspace:slug}/ads/creatives
 *
 * @see docs/pages/ads.md §Creative Gallery view
 * @see docs/competitors/_research_best_creatives.md
 * @see docs/competitors/_teardown_triple-whale.md#screen-creative-cockpit
 * @see docs/competitors/_teardown_northbeam.md#screen-creative-analytics
 */
class CreativeGalleryController extends Controller
{
    public function __construct(private readonly AdsQueryService $ads) {}

    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $params      = $this->validateParams($request);

        $adAccounts = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'platform', 'name', 'status'])
            ->get();

        $roasTarget = WorkspaceTarget::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('metric', 'roas')
            ->where('status', 'active')
            ->value('target_value_reporting');
        $workspaceTargetRoas = $roasTarget !== null ? (float) $roasTarget : 2.0;

        $adAccountList = $adAccounts->map(fn ($a) => [
            'id'       => $a->id,
            'platform' => $a->platform,
            'name'     => $a->name,
            'status'   => $a->status,
        ])->values()->all();

        if ($adAccounts->isEmpty()) {
            // No real ad accounts — return rich mock data so the UI is reviewable.
            return Inertia::render('Ads/Creatives', [
                'has_ad_accounts'       => false,
                'ad_accounts'           => [],
                'creative_cards'        => $this->mockCreativeCards($workspaceTargetRoas),
                'workspace_target_roas' => $workspaceTargetRoas,
                'klaviyo_performers'    => $this->mockKlaviyoPerformers(),
                ...$params,
            ]);
        }

        $filteredAccounts = $params['platform'] === 'all'
            ? $adAccounts
            : $adAccounts->where('platform', $params['platform']);
        $adAccountIds = $filteredAccounts->pluck('id')->all();

        $creativeCards = $this->ads->buildCreativeCards(
            $workspaceId,
            $adAccountIds,
            $params['from'],
            $params['to'],
            $params['campaign_id'],
            $params['adset_id'],
            $workspaceTargetRoas,
            $params['sort'],
            $params['status'],
            $params['limit'],
        );

        return Inertia::render('Ads/Creatives', [
            'has_ad_accounts'       => true,
            'ad_accounts'           => $adAccountList,
            'creative_cards'        => $creativeCards,
            'workspace_target_roas' => $workspaceTargetRoas,
            'klaviyo_performers'    => $this->mockKlaviyoPerformers(),
            ...$params,
        ]);
    }

    /** @return array<string, mixed> */
    private function validateParams(Request $request): array
    {
        $v = $request->validate([
            'from'             => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'               => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'platform'         => ['sometimes', 'nullable', 'in:all,facebook,google'],
            'campaign_id'      => ['sometimes', 'nullable', 'integer', 'min:1'],
            'adset_id'         => ['sometimes', 'nullable', 'integer', 'min:1'],
            'limit'            => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'sort'             => ['sometimes', 'nullable', 'in:spend,real_roas,thumbstop_pct,hold_rate_pct,composite_score,ctr,recency'],
            'status'           => ['sometimes', 'nullable', 'in:all,active,paused,archived'],
            'view'             => ['sometimes', 'nullable', 'in:triage,grid,split,table'],
            'roas_threshold'   => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:50'],
            'format'           => ['sometimes', 'nullable', 'in:all,image,video,carousel,email,sms'],
            'grade'            => ['sometimes', 'nullable', 'in:all,top,middling,bottom'],
        ]);

        return [
            'from'           => $v['from']     ?? now()->subDays(29)->toDateString(),
            'to'             => $v['to']       ?? now()->toDateString(),
            'platform'       => $v['platform'] ?? 'all',
            'campaign_id'    => isset($v['campaign_id']) ? (int) $v['campaign_id'] : null,
            'adset_id'       => isset($v['adset_id'])    ? (int) $v['adset_id']    : null,
            'limit'          => isset($v['limit']) ? (int) $v['limit'] : 60,
            'sort'           => $v['sort']           ?? 'composite_score',
            'status'         => $v['status']         ?? 'all',
            'view'           => $v['view']           ?? 'grid',
            'roas_threshold' => isset($v['roas_threshold']) ? (float) $v['roas_threshold'] : null,
            'format'         => $v['format']         ?? 'all',
            'grade'          => $v['grade']          ?? 'all',
        ];
    }

    /**
     * 20-creative mock dataset — 10 FB image, 5 FB video, 3 Google search, 2 Google display.
     * Spend $50–$5000, ROAS 0.4–6.2. Mix of statuses, formats, and tags.
     * Composite score = ROAS 50% + CTR 25% + CPA efficiency 25%, scaled 0–100.
     *
     * Triage buckets: score ≥ 60 = winners, 35–59 = iteration, < 35 = candidates.
     */
    private function mockCreativeCards(float $roasTarget): array
    {
        $raw = [
            // ── FB Image (10) ──────────────────────────────────────────────────
            [
                'ad_id' => 101, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Spring Sale 2026 — Hero Image',
                'campaign_name' => 'Spring Sale 2026 — Prospecting — LAL 1% — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 21,
                'ad_spend' => 4287.00, 'ad_impressions' => 412000, 'ad_clicks' => 8200,
                'ctr' => 1.99, 'cpc' => 0.52, 'real_roas' => 3.82, 'platform_cpa' => 32.10,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => 3.40, 'rank_curr' => 1, 'rank_prev' => 2, 'momentum_dir' => 'up',
                'composite_score' => 74.0, 'triage_bucket' => 'winners',
                'headline' => 'New Spring Styles — Up to 40% Off', 'body_text' => 'Shop our biggest sale of the season.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'discount', 'theme' => 'spring', 'offer' => '40off'],
            ],
            [
                'ad_id' => 102, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Brand Hook 03 — Gen Z Audience',
                'campaign_name' => 'Brand Awareness — Broad — WW',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 14,
                'ad_spend' => 1820.00, 'ad_impressions' => 98000, 'ad_clicks' => 3100,
                'ctr' => 3.16, 'cpc' => 0.59, 'real_roas' => 4.20, 'platform_cpa' => 18.20,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => 3.80, 'rank_curr' => 2, 'rank_prev' => 3, 'momentum_dir' => 'up',
                'composite_score' => 71.5, 'triage_bucket' => 'winners',
                'headline' => 'Your daily ritual, upgraded.', 'body_text' => 'Join 50,000+ happy customers.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'social_proof', 'theme' => 'brand', 'offer' => 'none'],
            ],
            [
                'ad_id' => 103, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Retargeting — Product View 3x — DPA',
                'campaign_name' => 'BFCM Remnant — DPA — Cart Abandoners — EU',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 7,
                'ad_spend' => 960.00, 'ad_impressions' => 44000, 'ad_clicks' => 1980,
                'ctr' => 4.50, 'cpc' => 0.48, 'real_roas' => 5.20, 'platform_cpa' => 14.77,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => 4.90, 'rank_curr' => 3, 'rank_prev' => 4, 'momentum_dir' => 'up',
                'composite_score' => 82.0, 'triage_bucket' => 'winners',
                'headline' => 'Still thinking about it?', 'body_text' => 'Your cart is waiting.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'urgency', 'theme' => 'retargeting', 'offer' => 'cart'],
            ],
            [
                'ad_id' => 104, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Offer — 20% Off First Order — LAL 2%',
                'campaign_name' => 'Offer — 20% Off — LAL 1% — FR',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 18,
                'ad_spend' => 2100.00, 'ad_impressions' => 220000, 'ad_clicks' => 4600,
                'ctr' => 2.09, 'cpc' => 0.46, 'real_roas' => 3.10, 'platform_cpa' => 28.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => 2.80, 'rank_curr' => 4, 'rank_prev' => 5, 'momentum_dir' => 'up',
                'composite_score' => 63.5, 'triage_bucket' => 'winners',
                'headline' => '20% off your first order', 'body_text' => 'Limited time — grab yours now.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'discount', 'theme' => 'acquisition', 'offer' => '20off'],
            ],
            [
                'ad_id' => 105, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Skincare Bundle — Static — US',
                'campaign_name' => 'Skincare Bundle — Offer — LAL 2% — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 30,
                'ad_spend' => 3300.00, 'ad_impressions' => 285000, 'ad_clicks' => 6270,
                'ctr' => 2.20, 'cpc' => 0.53, 'real_roas' => 3.00, 'platform_cpa' => 33.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 3.10, 'rank_curr' => 5, 'rank_prev' => 4, 'momentum_dir' => 'down',
                'composite_score' => 61.0, 'triage_bucket' => 'winners',
                'headline' => 'The bundle everyone\'s talking about', 'body_text' => 'Full skincare routine for $49.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'bundle', 'theme' => 'product', 'offer' => 'bundle49'],
            ],
            [
                'ad_id' => 106, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Email List LAL — 2% — Testimonial Creative',
                'campaign_name' => 'Email List LAL — 2% — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 12,
                'ad_spend' => 980.00, 'ad_impressions' => 96000, 'ad_clicks' => 2040,
                'ctr' => 2.13, 'cpc' => 0.48, 'real_roas' => 2.80, 'platform_cpa' => 24.75,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 2.90, 'rank_curr' => 6, 'rank_prev' => 5, 'momentum_dir' => 'down',
                'composite_score' => 55.0, 'triage_bucket' => 'iteration',
                'headline' => '"Changed my life" — Sarah M.', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'testimonial', 'theme' => 'social_proof', 'offer' => 'none'],
            ],
            [
                'ad_id' => 107, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Competitor Conquest — Comparison Static',
                'campaign_name' => 'Competitor Conquest — Interests — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 9,
                'ad_spend' => 1250.00, 'ad_impressions' => 150000, 'ad_clicks' => 2700,
                'ctr' => 1.80, 'cpc' => 0.47, 'real_roas' => 1.40, 'platform_cpa' => 52.80,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 1.60, 'rank_curr' => 7, 'rank_prev' => 6, 'momentum_dir' => 'down',
                'composite_score' => 28.0, 'triage_bucket' => 'candidates',
                'headline' => 'Better than [Brand X] — here\'s why', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'comparison', 'theme' => 'conquest', 'offer' => 'none'],
            ],
            [
                'ad_id' => 108, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Mobile Story — 18-24 — Static',
                'campaign_name' => 'Mobile-Only — Story — 18-24 — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 6,
                'ad_spend' => 420.00, 'ad_impressions' => 45000, 'ad_clicks' => 1125,
                'ctr' => 2.50, 'cpc' => 0.37, 'real_roas' => 1.70, 'platform_cpa' => 42.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 1.65, 'rank_curr' => 8, 'rank_prev' => 8, 'momentum_dir' => 'stable',
                'composite_score' => 38.5, 'triage_bucket' => 'iteration',
                'headline' => 'Swipe up for 20% off', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'swipe_up', 'theme' => 'mobile', 'offer' => '20off'],
            ],
            [
                'ad_id' => 109, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Flash Sale — High-Intent Visitors',
                'campaign_name' => 'Flash Sale — Retargeting — 7d — AU',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 3,
                'ad_spend' => 50.00, 'ad_impressions' => 4000, 'ad_clicks' => 200,
                'ctr' => 5.00, 'cpc' => 0.25, 'real_roas' => 6.20, 'platform_cpa' => 5.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => null, 'rank_curr' => 9, 'rank_prev' => null, 'momentum_dir' => 'new',
                'composite_score' => 90.0, 'triage_bucket' => 'winners',
                'headline' => '48h flash sale — 30% off everything', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'urgency', 'theme' => 'flash_sale', 'offer' => '30off'],
            ],
            [
                'ad_id' => 110, 'platform' => 'facebook', 'format' => 'image',
                'ad_name' => 'Cold Traffic — Broad — Static Fallback',
                'campaign_name' => 'Brand Awareness — Broad — WW',
                'status' => 'paused', 'effective_status' => 'paused',
                'days_running' => 45,
                'ad_spend' => 4800.00, 'ad_impressions' => 820000, 'ad_clicks' => 9800,
                'ctr' => 1.20, 'cpc' => 0.49, 'real_roas' => 0.50, 'platform_cpa' => 96.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'loser',
                'prior_roas' => 0.60, 'rank_curr' => 10, 'rank_prev' => 9, 'momentum_dir' => 'down',
                'composite_score' => 9.0, 'triage_bucket' => 'candidates',
                'headline' => 'Discover the best skincare routine', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'discovery', 'theme' => 'brand', 'offer' => 'none'],
            ],

            // ── FB Video (5) ───────────────────────────────────────────────────
            [
                'ad_id' => 201, 'platform' => 'facebook', 'format' => 'video',
                'ad_name' => 'Spring Sale 2026 — Hero Video',
                'campaign_name' => 'Spring Sale 2026 — Prospecting — LAL 1% — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 21,
                'ad_spend' => 5000.00, 'ad_impressions' => 520000, 'ad_clicks' => 9400,
                'ctr' => 1.81, 'cpc' => 0.53, 'real_roas' => 3.50, 'platform_cpa' => 29.50,
                'thumbstop_pct' => 28.4, 'hold_rate_pct' => 42.1, 'hook_rate_pct' => 31.2,
                'motion_score' => 76, 'motion_verdict' => 'winner',
                'prior_roas' => 3.20, 'rank_curr' => 1, 'rank_prev' => 2, 'momentum_dir' => 'up',
                'composite_score' => 70.0, 'triage_bucket' => 'winners',
                'headline' => 'Spring is here — shop the drop', 'body_text' => 'Refresh your wardrobe for the new season.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'seasonal', 'theme' => 'product_reveal', 'offer' => '40off'],
            ],
            [
                'ad_id' => 202, 'platform' => 'facebook', 'format' => 'video',
                'ad_name' => 'UGC Testimonial — Real Customer — 30s',
                'campaign_name' => 'UGC Test — Static vs Video — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 11,
                'ad_spend' => 720.00, 'ad_impressions' => 68000, 'ad_clicks' => 1700,
                'ctr' => 2.50, 'cpc' => 0.42, 'real_roas' => 2.60, 'platform_cpa' => 36.00,
                'thumbstop_pct' => 34.8, 'hold_rate_pct' => 55.3, 'hook_rate_pct' => 38.1,
                'motion_score' => 65, 'motion_verdict' => 'winner',
                'prior_roas' => 2.30, 'rank_curr' => 2, 'rank_prev' => 4, 'momentum_dir' => 'up',
                'composite_score' => 58.0, 'triage_bucket' => 'iteration',
                'headline' => '"I use it every day" — real customer', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'ugc', 'theme' => 'testimonial', 'offer' => 'none'],
            ],
            [
                'ad_id' => 203, 'platform' => 'facebook', 'format' => 'video',
                'ad_name' => 'Q1 Video — Prospecting — Hook A',
                'campaign_name' => 'Q1 Video — Prospecting — Interest — UK',
                'status' => 'paused', 'effective_status' => 'paused',
                'days_running' => 60,
                'ad_spend' => 3120.00, 'ad_impressions' => 480000, 'ad_clicks' => 4800,
                'ctr' => 1.00, 'cpc' => 0.65, 'real_roas' => 1.25, 'platform_cpa' => 78.00,
                'thumbstop_pct' => 18.2, 'hold_rate_pct' => 28.4, 'hook_rate_pct' => 20.5,
                'motion_score' => 32, 'motion_verdict' => 'loser',
                'prior_roas' => 1.40, 'rank_curr' => 3, 'rank_prev' => 2, 'momentum_dir' => 'down',
                'composite_score' => 24.0, 'triage_bucket' => 'candidates',
                'headline' => 'Ready for a change?', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'question', 'theme' => 'awareness', 'offer' => 'none'],
            ],
            [
                'ad_id' => 204, 'platform' => 'facebook', 'format' => 'video',
                'ad_name' => 'Flash Sale — 30s Video — AU',
                'campaign_name' => 'Flash Sale — Retargeting — 7d — AU',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 3,
                'ad_spend' => 680.00, 'ad_impressions' => 31000, 'ad_clicks' => 1550,
                'ctr' => 5.00, 'cpc' => 0.44, 'real_roas' => 6.20, 'platform_cpa' => 10.46,
                'thumbstop_pct' => 41.6, 'hold_rate_pct' => 62.0, 'hook_rate_pct' => 44.8,
                'motion_score' => 88, 'motion_verdict' => 'winner',
                'prior_roas' => null, 'rank_curr' => 4, 'rank_prev' => null, 'momentum_dir' => 'new',
                'composite_score' => 92.0, 'triage_bucket' => 'winners',
                'headline' => '48h only — 30% off', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'countdown', 'theme' => 'flash_sale', 'offer' => '30off'],
            ],
            [
                'ad_id' => 205, 'platform' => 'facebook', 'format' => 'video',
                'ad_name' => 'Brand Story — Founder Interview — 60s',
                'campaign_name' => 'Brand Awareness — Broad — WW',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 28,
                'ad_spend' => 1900.00, 'ad_impressions' => 390000, 'ad_clicks' => 2730,
                'ctr' => 0.70, 'cpc' => 0.70, 'real_roas' => 0.85, 'platform_cpa' => 95.00,
                'thumbstop_pct' => 22.1, 'hold_rate_pct' => 38.7, 'hook_rate_pct' => 25.0,
                'motion_score' => 28, 'motion_verdict' => 'loser',
                'prior_roas' => 0.90, 'rank_curr' => 5, 'rank_prev' => 4, 'momentum_dir' => 'down',
                'composite_score' => 14.5, 'triage_bucket' => 'candidates',
                'headline' => 'How we started — our story', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'brand_story', 'theme' => 'awareness', 'offer' => 'none'],
            ],

            // ── Google Search (3) ──────────────────────────────────────────────
            [
                'ad_id' => 301, 'platform' => 'google', 'format' => 'image',
                'ad_name' => 'Brand Search — Exact — RSA Variant A',
                'campaign_name' => 'Brand Search — Exact — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 90,
                'ad_spend' => 1260.00, 'ad_impressions' => 42000, 'ad_clicks' => 14700,
                'ctr' => 35.00, 'cpc' => 0.086, 'real_roas' => 7.10, 'platform_cpa' => 9.69,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'winner',
                'prior_roas' => 6.90, 'rank_curr' => 1, 'rank_prev' => 1, 'momentum_dir' => 'stable',
                'composite_score' => 95.0, 'triage_bucket' => 'winners',
                'headline' => '[Brand] — Official Store | Free Shipping', 'body_text' => 'Shop the full range. Fast shipping.',
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'brand', 'theme' => 'search', 'offer' => 'free_shipping'],
            ],
            [
                'ad_id' => 302, 'platform' => 'google', 'format' => 'image',
                'ad_name' => 'Non-Brand Search — Category Page RSA',
                'campaign_name' => 'Non-Brand Search — Broad — US + EU',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 45,
                'ad_spend' => 1920.00, 'ad_impressions' => 105000, 'ad_clicks' => 9450,
                'ctr' => 9.00, 'cpc' => 0.203, 'real_roas' => 2.85, 'platform_cpa' => 34.91,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 2.70, 'rank_curr' => 2, 'rank_prev' => 3, 'momentum_dir' => 'up',
                'composite_score' => 60.0, 'triage_bucket' => 'winners',
                'headline' => 'Best Skincare Products 2026 | Compare & Save', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'comparison', 'theme' => 'non_brand_search', 'offer' => 'none'],
            ],
            [
                'ad_id' => 303, 'platform' => 'google', 'format' => 'image',
                'ad_name' => 'Dynamic Search Ads — Category Pages',
                'campaign_name' => 'Dynamic Search Ads — Category Pages — US',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 30,
                'ad_spend' => 1100.00, 'ad_impressions' => 65000, 'ad_clicks' => 5200,
                'ctr' => 8.00, 'cpc' => 0.212, 'real_roas' => 3.60, 'platform_cpa' => 27.50,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 3.40, 'rank_curr' => 3, 'rank_prev' => 4, 'momentum_dir' => 'up',
                'composite_score' => 72.0, 'triage_bucket' => 'winners',
                'headline' => null, 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'dynamic', 'theme' => 'dsa', 'offer' => 'none'],
            ],

            // ── Google Display (2) ─────────────────────────────────────────────
            [
                'ad_id' => 401, 'platform' => 'google', 'format' => 'image',
                'ad_name' => 'Display Retargeting — 14d — Banner 728x90',
                'campaign_name' => 'Display Retargeting — 14d — WW',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 20,
                'ad_spend' => 410.00, 'ad_impressions' => 550000, 'ad_clicks' => 1100,
                'ctr' => 0.20, 'cpc' => 0.373, 'real_roas' => 1.80, 'platform_cpa' => 54.67,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => null,
                'prior_roas' => 1.90, 'rank_curr' => 1, 'rank_prev' => 1, 'momentum_dir' => 'stable',
                'composite_score' => 36.0, 'triage_bucket' => 'iteration',
                'headline' => 'Come back — we miss you', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'retargeting', 'theme' => 'display', 'offer' => 'none'],
            ],
            [
                'ad_id' => 402, 'platform' => 'google', 'format' => 'image',
                'ad_name' => 'Display Retargeting — 14d — Responsive Display',
                'campaign_name' => 'Display Retargeting — 14d — WW',
                'status' => 'active', 'effective_status' => 'active',
                'days_running' => 20,
                'ad_spend' => 410.00, 'ad_impressions' => 550000, 'ad_clicks' => 1100,
                'ctr' => 0.20, 'cpc' => 0.373, 'real_roas' => 0.40, 'platform_cpa' => 120.00,
                'thumbstop_pct' => null, 'hold_rate_pct' => null, 'hook_rate_pct' => null,
                'motion_score' => null, 'motion_verdict' => 'loser',
                'prior_roas' => 0.55, 'rank_curr' => 2, 'rank_prev' => 1, 'momentum_dir' => 'down',
                'composite_score' => 7.0, 'triage_bucket' => 'candidates',
                'headline' => 'Complete your purchase today', 'body_text' => null,
                'thumbnail_url' => null, 'ad_url' => null,
                'tags' => ['hook' => 'cta', 'theme' => 'display', 'offer' => 'none'],
            ],
        ];

        // Add target_roas to all rows so the frontend can compute gradeclass
        foreach ($raw as &$card) {
            $card['target_roas'] = $roasTarget;
        }
        unset($card);

        return $raw;
    }

    /**
     * Mock Klaviyo top performers — 5 flows + 5 campaigns by attributed revenue.
     * Used for the Klaviyo section below the ad gallery.
     *
     * @return array{flows: list<array<string, mixed>>, campaigns: list<array<string, mixed>>}
     */
    private function mockKlaviyoPerformers(): array
    {
        return [
            'flows' => [
                ['id' => 'kf_1', 'name' => 'Welcome Series — Email 1',        'revenue' => 7840.00, 'orders' => 98,  'revenue_per_email' => 0.42, 'recipients' => 18666],
                ['id' => 'kf_2', 'name' => 'Abandoned Cart — 1h Reminder',    'revenue' => 5120.00, 'orders' => 64,  'revenue_per_email' => 1.28, 'recipients' => 4000],
                ['id' => 'kf_3', 'name' => 'Browse Abandonment — 24h',        'revenue' => 3200.00, 'orders' => 40,  'revenue_per_email' => 0.64, 'recipients' => 5000],
                ['id' => 'kf_4', 'name' => 'Post-Purchase — Cross-sell D+7',  'revenue' => 2640.00, 'orders' => 33,  'revenue_per_email' => 0.22, 'recipients' => 12000],
                ['id' => 'kf_5', 'name' => 'Win-Back — 90d Lapsed',           'revenue' => 1280.00, 'orders' => 16,  'revenue_per_email' => 0.71, 'recipients' => 1800],
            ],
            'campaigns' => [
                ['id' => 'kc_1', 'name' => 'Spring Sale 2026 — Announce',     'revenue' => 8000.00, 'orders' => 100, 'revenue_per_email' => 1.00, 'recipients' => 8000],
                ['id' => 'kc_2', 'name' => 'Flash Sale — 48h — Segment A',    'revenue' => 4800.00, 'orders' => 60,  'revenue_per_email' => 1.60, 'recipients' => 3000],
                ['id' => 'kc_3', 'name' => 'Skincare Bundle Launch',          'revenue' => 3360.00, 'orders' => 42,  'revenue_per_email' => 0.84, 'recipients' => 4000],
                ['id' => 'kc_4', 'name' => 'VIP Exclusive — Early Access',    'revenue' => 2400.00, 'orders' => 30,  'revenue_per_email' => 2.40, 'recipients' => 1000],
                ['id' => 'kc_5', 'name' => 'Product Restock — Sold-Out Alert','revenue' => 200.00,  'orders' => 3,   'revenue_per_email' => 0.10, 'recipients' => 2000],
            ],
        ];
    }
}
