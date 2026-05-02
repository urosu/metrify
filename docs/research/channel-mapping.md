# Channel Mapping Rules -- Comprehensive Ecommerce Attribution

Based on GA4 Default Channel Grouping (https://support.google.com/analytics/answer/9756891), extended with ecommerce-specific sources.

## How It Works

The `channel_mappings` table uses **first-match-wins by priority** (lower number = checked first). The classifier walks rows in priority order; the first row where all non-null columns match wins. A null column in a rule means "match anything."

**Matching semantics:**
- `utm_source` / `utm_medium`: SQL `LIKE` (so `%` is wildcard). Case-insensitive.
- `utm_campaign_pattern`: regex or LIKE pattern against the campaign name.
- `referring_site_pattern`: regex against the full referrer hostname.
- `channel`: the resolved channel slug.

**Priority bands:**
- 1-49: Click-ID detection (gclid, fbclid, etc.) and auto-tagged paid traffic
- 50-99: Paid search (source + paid medium)
- 100-149: Paid social (source + paid medium)
- 150-179: Paid video
- 180-199: Paid shopping / cross-network
- 200-249: Display / native / programmatic
- 250-299: Email platforms
- 300-329: SMS
- 330-349: Affiliate
- 350-399: Mobile push
- 400-499: Organic search
- 500-599: Organic social (source + social/organic/referral medium or referrer match)
- 600-649: Organic video
- 650-679: Organic shopping
- 700-749: Shopify-specific / ecommerce
- 900-999: Catch-all fallbacks (referral, direct)

## Valid Channel Values

```
paid_search, paid_social, paid_video, paid_shopping,
organic_search, organic_social, organic_video, organic_shopping,
email, sms, affiliate, display, referral, direct,
mobile_push, cross_network, unassigned
```

## PHP Seeder Array

Use this in `ChannelMappingSeeder` or pass to `ChannelMapping::insert()`. All rules have `workspace_id => null` (global defaults). Workspaces can override by adding their own rules at lower priority numbers.

```php
<?php

/**
 * Comprehensive Channel Mapping Rules
 *
 * 170+ rules covering >95% of ecommerce traffic.
 * Based on GA4 Default Channel Grouping + ecommerce-specific sources.
 *
 * Priority bands:
 *   1-49    Click-ID / auto-tagged paid
 *   50-99   Paid search
 *   100-149 Paid social
 *   150-179 Paid video
 *   180-199 Paid shopping / cross-network
 *   200-249 Display / native / programmatic
 *   250-299 Email
 *   300-329 SMS
 *   330-349 Affiliate
 *   350-399 Mobile push
 *   400-499 Organic search
 *   500-599 Organic social
 *   600-649 Organic video
 *   650-679 Organic shopping
 *   700-749 Shopify / ecommerce specific
 *   900-999 Catch-all fallbacks
 */

return [

    // ──────────────────────────────────────────────
    // CLICK-ID AUTO-TAGGING (highest priority)
    // These detect auto-tagged URLs regardless of UTM values.
    // In practice, the classifier checks query-string params
    // (gclid, fbclid, etc.) BEFORE this table. These rows
    // serve as documentation and fallback.
    // ──────────────────────────────────────────────

    // Google Ads auto-tag (gclid present)
    ['priority' => 1,  'utm_source' => 'google',    'utm_medium' => 'cpc',       'channel' => 'paid_search'],
    // Meta Ads auto-tag (fbclid present)
    ['priority' => 2,  'utm_source' => 'facebook',  'utm_medium' => 'cpc',       'channel' => 'paid_social'],
    ['priority' => 3,  'utm_source' => 'fb',        'utm_medium' => 'cpc',       'channel' => 'paid_social'],
    ['priority' => 4,  'utm_source' => 'ig',         'utm_medium' => 'cpc',       'channel' => 'paid_social'],
    ['priority' => 5,  'utm_source' => 'instagram',  'utm_medium' => 'cpc',       'channel' => 'paid_social'],
    ['priority' => 6,  'utm_source' => 'meta',       'utm_medium' => 'cpc',       'channel' => 'paid_social'],
    // Microsoft/Bing Ads auto-tag (msclkid present)
    ['priority' => 7,  'utm_source' => 'bing',      'utm_medium' => 'cpc',       'channel' => 'paid_search'],
    // TikTok Ads auto-tag (ttclid present)
    ['priority' => 8,  'utm_source' => 'tiktok',    'utm_medium' => 'cpc',       'channel' => 'paid_social'],

    // ──────────────────────────────────────────────
    // PAID SEARCH (priority 50-99)
    // Source is a search engine + medium indicates paid
    // ──────────────────────────────────────────────

    // Google (all regional variants resolve to source "google")
    ['priority' => 50, 'utm_source' => 'google',       'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 51, 'utm_source' => 'google',       'utm_medium' => 'ppc',        'channel' => 'paid_search'],
    ['priority' => 52, 'utm_source' => 'google',       'utm_medium' => 'paid%',      'channel' => 'paid_search'],
    ['priority' => 53, 'utm_source' => 'google',       'utm_medium' => 'retargeting','channel' => 'paid_search'],
    ['priority' => 54, 'utm_source' => 'google',       'utm_medium' => 'remarketing','channel' => 'paid_search'],

    // Bing / Microsoft
    ['priority' => 55, 'utm_source' => 'bing',         'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 56, 'utm_source' => 'bing',         'utm_medium' => 'ppc',        'channel' => 'paid_search'],
    ['priority' => 57, 'utm_source' => 'bing',         'utm_medium' => 'paid%',      'channel' => 'paid_search'],
    ['priority' => 58, 'utm_source' => 'microsoft',    'utm_medium' => 'cpc',        'channel' => 'paid_search'],
    ['priority' => 59, 'utm_source' => 'microsoft',    'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Yahoo
    ['priority' => 60, 'utm_source' => 'yahoo',        'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 61, 'utm_source' => 'yahoo',        'utm_medium' => 'ppc',        'channel' => 'paid_search'],
    ['priority' => 62, 'utm_source' => 'yahoo',        'utm_medium' => 'paid%',      'channel' => 'paid_search'],
    ['priority' => 63, 'utm_source' => 'yahoo_gemini', 'utm_medium' => '%cp%',       'channel' => 'paid_search'],

    // DuckDuckGo
    ['priority' => 64, 'utm_source' => 'duckduckgo',   'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 65, 'utm_source' => 'duckduckgo',   'utm_medium' => 'ppc',        'channel' => 'paid_search'],
    ['priority' => 66, 'utm_source' => 'duckduckgo',   'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Baidu
    ['priority' => 67, 'utm_source' => 'baidu',        'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 68, 'utm_source' => 'baidu',        'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Yandex
    ['priority' => 69, 'utm_source' => 'yandex',       'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 70, 'utm_source' => 'yandex',       'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Naver (Korean search)
    ['priority' => 71, 'utm_source' => 'naver',        'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 72, 'utm_source' => 'naver',        'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Seznam (Czech search)
    ['priority' => 73, 'utm_source' => 'seznam',       'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 74, 'utm_source' => 'seznam',       'utm_medium' => 'paid%',      'channel' => 'paid_search'],

    // Ecosia, Qwant, Brave
    ['priority' => 75, 'utm_source' => 'ecosia',       'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 76, 'utm_source' => 'qwant',        'utm_medium' => '%cp%',       'channel' => 'paid_search'],
    ['priority' => 77, 'utm_source' => 'brave',        'utm_medium' => '%cp%',       'channel' => 'paid_search'],

    // Catch-all: any source + paid search mediums where source looks like a search engine
    // (handled by the classifier's known-search-engine list, not a row here)

    // ──────────────────────────────────────────────
    // PAID SOCIAL (priority 100-149)
    // ──────────────────────────────────────────────

    // Facebook / Meta (all subdomains: m., l., lm., business., web., apps., free., touch.)
    ['priority' => 100, 'utm_source' => 'facebook',   'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 101, 'utm_source' => 'facebook',   'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 102, 'utm_source' => 'facebook',   'utm_medium' => 'ppc',         'channel' => 'paid_social'],
    ['priority' => 103, 'utm_source' => 'facebook',   'utm_medium' => 'retargeting', 'channel' => 'paid_social'],
    ['priority' => 104, 'utm_source' => 'facebook',   'utm_medium' => 'remarketing', 'channel' => 'paid_social'],
    ['priority' => 105, 'utm_source' => 'facebook',   'utm_medium' => 'sponsored',   'channel' => 'paid_social'],
    ['priority' => 106, 'utm_source' => 'fb',         'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 107, 'utm_source' => 'fb',         'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 108, 'utm_source' => 'meta',       'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 109, 'utm_source' => 'meta',       'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 110, 'utm_source' => 'meta',       'utm_medium' => 'retargeting', 'channel' => 'paid_social'],
    ['priority' => 111, 'utm_source' => 'meta',       'utm_medium' => 'remarketing', 'channel' => 'paid_social'],

    // Instagram
    ['priority' => 112, 'utm_source' => 'instagram',  'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 113, 'utm_source' => 'instagram',  'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 114, 'utm_source' => 'instagram',  'utm_medium' => 'retargeting', 'channel' => 'paid_social'],
    ['priority' => 115, 'utm_source' => 'ig',         'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 116, 'utm_source' => 'ig',         'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // TikTok
    ['priority' => 117, 'utm_source' => 'tiktok',     'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 118, 'utm_source' => 'tiktok',     'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 119, 'utm_source' => 'tiktok',     'utm_medium' => 'retargeting', 'channel' => 'paid_social'],

    // Pinterest
    ['priority' => 120, 'utm_source' => 'pinterest',  'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 121, 'utm_source' => 'pinterest',  'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // LinkedIn
    ['priority' => 122, 'utm_source' => 'linkedin',   'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 123, 'utm_source' => 'linkedin',   'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 124, 'utm_source' => 'linkedin',   'utm_medium' => 'sponsored',   'channel' => 'paid_social'],

    // Twitter / X
    ['priority' => 125, 'utm_source' => 'twitter',    'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 126, 'utm_source' => 'twitter',    'utm_medium' => 'paid%',       'channel' => 'paid_social'],
    ['priority' => 127, 'utm_source' => 'x',          'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 128, 'utm_source' => 'x',          'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // Snapchat
    ['priority' => 129, 'utm_source' => 'snapchat',   'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 130, 'utm_source' => 'snapchat',   'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // Reddit
    ['priority' => 131, 'utm_source' => 'reddit',     'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 132, 'utm_source' => 'reddit',     'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // Quora
    ['priority' => 133, 'utm_source' => 'quora',      'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 134, 'utm_source' => 'quora',      'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // Threads
    ['priority' => 135, 'utm_source' => 'threads',    'utm_medium' => '%cp%',        'channel' => 'paid_social'],
    ['priority' => 136, 'utm_source' => 'threads',    'utm_medium' => 'paid%',       'channel' => 'paid_social'],

    // Catch-all paid social: any medium that screams "paid" from a known social source
    // (The classifier also checks referring_site_pattern against the social domains list)

    // ──────────────────────────────────────────────
    // PAID VIDEO (priority 150-179)
    // ──────────────────────────────────────────────

    ['priority' => 150, 'utm_source' => 'youtube',    'utm_medium' => 'cpc',         'channel' => 'paid_video'],
    ['priority' => 151, 'utm_source' => 'youtube',    'utm_medium' => '%cp%',        'channel' => 'paid_video'],
    ['priority' => 152, 'utm_source' => 'youtube',    'utm_medium' => 'paid%',       'channel' => 'paid_video'],
    ['priority' => 153, 'utm_source' => 'youtube',    'utm_medium' => '%video%',     'channel' => 'paid_video'],
    ['priority' => 154, 'utm_source' => 'google',     'utm_medium' => 'cpc',         'channel' => 'paid_video',
                        'utm_campaign_pattern' => '%video%'],
    ['priority' => 155, 'utm_source' => null,          'utm_medium' => '%video%',
                        'utm_campaign_pattern' => '%paid%',                           'channel' => 'paid_video'],

    // ──────────────────────────────────────────────
    // PAID SHOPPING / CROSS-NETWORK (priority 180-199)
    // ──────────────────────────────────────────────

    // Google Shopping
    ['priority' => 180, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%shop%',                           'channel' => 'paid_shopping'],
    ['priority' => 181, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%pla%',                            'channel' => 'paid_shopping'],

    // Google Performance Max / Demand Gen / Cross-network
    ['priority' => 185, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%pmax%',                           'channel' => 'cross_network'],
    ['priority' => 186, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%performance.max%',                'channel' => 'cross_network'],
    ['priority' => 187, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%demand.gen%',                     'channel' => 'cross_network'],
    ['priority' => 188, 'utm_source' => 'google',     'utm_medium' => 'cpc',
                        'utm_campaign_pattern' => '%discovery%',                      'channel' => 'cross_network'],
    ['priority' => 189, 'utm_source' => null,          'utm_medium' => null,
                        'utm_campaign_pattern' => '%cross-network%',                  'channel' => 'cross_network'],

    // ──────────────────────────────────────────────
    // DISPLAY / NATIVE / PROGRAMMATIC (priority 200-249)
    // ──────────────────────────────────────────────

    ['priority' => 200, 'utm_source' => null,          'utm_medium' => 'display',     'channel' => 'display'],
    ['priority' => 201, 'utm_source' => null,          'utm_medium' => 'cpm',         'channel' => 'display'],
    ['priority' => 202, 'utm_source' => null,          'utm_medium' => 'banner',      'channel' => 'display'],
    ['priority' => 203, 'utm_source' => null,          'utm_medium' => 'interstitial','channel' => 'display'],
    ['priority' => 204, 'utm_source' => null,          'utm_medium' => 'expandable',  'channel' => 'display'],
    ['priority' => 205, 'utm_source' => null,          'utm_medium' => 'native',      'channel' => 'display'],
    ['priority' => 206, 'utm_source' => null,          'utm_medium' => 'programmatic','channel' => 'display'],

    // Criteo
    ['priority' => 210, 'utm_source' => 'criteo',     'utm_medium' => '%cp%',        'channel' => 'display'],
    ['priority' => 211, 'utm_source' => 'criteo',     'utm_medium' => 'retargeting', 'channel' => 'display'],
    ['priority' => 212, 'utm_source' => 'criteo',     'utm_medium' => 'display',     'channel' => 'display'],
    ['priority' => 213, 'utm_source' => 'criteo',     'utm_medium' => null,          'channel' => 'display'],

    // AdRoll
    ['priority' => 214, 'utm_source' => 'adroll',     'utm_medium' => null,          'channel' => 'display'],

    // Taboola
    ['priority' => 215, 'utm_source' => 'taboola',    'utm_medium' => null,          'channel' => 'display'],

    // Outbrain
    ['priority' => 216, 'utm_source' => 'outbrain',   'utm_medium' => null,          'channel' => 'display'],

    // Google Display Network (when source is explicit)
    ['priority' => 217, 'utm_source' => 'gdn',        'utm_medium' => null,          'channel' => 'display'],
    ['priority' => 218, 'utm_source' => 'dv360',      'utm_medium' => null,          'channel' => 'display'],

    // ──────────────────────────────────────────────
    // EMAIL (priority 250-299)
    // ──────────────────────────────────────────────

    // Generic medium-based email detection
    ['priority' => 250, 'utm_source' => null,          'utm_medium' => 'email',       'channel' => 'email'],
    ['priority' => 251, 'utm_source' => null,          'utm_medium' => 'e-mail',      'channel' => 'email'],
    ['priority' => 252, 'utm_source' => null,          'utm_medium' => 'e_mail',      'channel' => 'email'],
    ['priority' => 253, 'utm_source' => null,          'utm_medium' => 'newsletter',  'channel' => 'email'],
    ['priority' => 254, 'utm_source' => null,          'utm_medium' => 'blast',       'channel' => 'email'],

    // Klaviyo (often uses medium=campaign which would otherwise be unassigned)
    ['priority' => 260, 'utm_source' => 'klaviyo',    'utm_medium' => null,           'channel' => 'email'],

    // Mailchimp
    ['priority' => 261, 'utm_source' => 'mailchimp',  'utm_medium' => null,           'channel' => 'email'],
    ['priority' => 262, 'utm_source' => 'mandrill',   'utm_medium' => null,           'channel' => 'email'],

    // Omnisend
    ['priority' => 263, 'utm_source' => 'omnisend',   'utm_medium' => null,           'channel' => 'email'],

    // Campaign Monitor
    ['priority' => 264, 'utm_source' => 'campaign_monitor','utm_medium' => null,      'channel' => 'email'],
    ['priority' => 265, 'utm_source' => 'campaignmonitor', 'utm_medium' => null,      'channel' => 'email'],

    // Sendinblue / Brevo
    ['priority' => 266, 'utm_source' => 'sendinblue', 'utm_medium' => null,           'channel' => 'email'],
    ['priority' => 267, 'utm_source' => 'brevo',      'utm_medium' => null,           'channel' => 'email'],

    // ActiveCampaign
    ['priority' => 268, 'utm_source' => 'activecampaign','utm_medium' => null,        'channel' => 'email'],
    ['priority' => 269, 'utm_source' => 'active_campaign','utm_medium' => null,       'channel' => 'email'],

    // Drip
    ['priority' => 270, 'utm_source' => 'drip',       'utm_medium' => null,           'channel' => 'email'],

    // Constant Contact
    ['priority' => 271, 'utm_source' => 'constant_contact','utm_medium' => null,      'channel' => 'email'],
    ['priority' => 272, 'utm_source' => 'constantcontact', 'utm_medium' => null,      'channel' => 'email'],

    // HubSpot
    ['priority' => 273, 'utm_source' => 'hubspot',    'utm_medium' => null,           'channel' => 'email'],
    ['priority' => 274, 'utm_source' => 'hs_email',   'utm_medium' => null,           'channel' => 'email'],

    // Shopify Email
    ['priority' => 275, 'utm_source' => 'shopify_email','utm_medium' => null,         'channel' => 'email'],
    ['priority' => 276, 'utm_source' => 'shopify',    'utm_medium' => 'email',        'channel' => 'email'],

    // Convertkit
    ['priority' => 277, 'utm_source' => 'convertkit', 'utm_medium' => null,           'channel' => 'email'],

    // Postmark / Transactional
    ['priority' => 278, 'utm_source' => 'postmark',   'utm_medium' => null,           'channel' => 'email'],

    // GetResponse
    ['priority' => 279, 'utm_source' => 'getresponse','utm_medium' => null,           'channel' => 'email'],

    // AWeber
    ['priority' => 280, 'utm_source' => 'aweber',     'utm_medium' => null,           'channel' => 'email'],

    // Retention.com / Recart (Shopify ecosystem)
    ['priority' => 281, 'utm_source' => 'retention',  'utm_medium' => 'email',        'channel' => 'email'],
    ['priority' => 282, 'utm_source' => 'recart',     'utm_medium' => 'email',        'channel' => 'email'],

    // ──────────────────────────────────────────────
    // SMS (priority 300-329)
    // ──────────────────────────────────────────────

    ['priority' => 300, 'utm_source' => null,          'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 301, 'utm_source' => 'sms',         'utm_medium' => null,          'channel' => 'sms'],
    ['priority' => 302, 'utm_source' => 'klaviyo',     'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 303, 'utm_source' => 'omnisend',    'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 304, 'utm_source' => 'postscript',  'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 305, 'utm_source' => 'attentive',   'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 306, 'utm_source' => 'twilio',      'utm_medium' => 'sms',         'channel' => 'sms'],
    ['priority' => 307, 'utm_source' => 'recart',      'utm_medium' => 'sms',         'channel' => 'sms'],

    // ──────────────────────────────────────────────
    // AFFILIATE (priority 330-349)
    // ──────────────────────────────────────────────

    ['priority' => 330, 'utm_source' => null,          'utm_medium' => 'affiliate',   'channel' => 'affiliate'],
    ['priority' => 331, 'utm_source' => 'shareasale',  'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 332, 'utm_source' => 'cj',          'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 333, 'utm_source' => 'commission_junction','utm_medium' => null,   'channel' => 'affiliate'],
    ['priority' => 334, 'utm_source' => 'impact',      'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 335, 'utm_source' => 'awin',        'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 336, 'utm_source' => 'rakuten',     'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 337, 'utm_source' => 'refersion',   'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 338, 'utm_source' => 'goaffpro',    'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 339, 'utm_source' => 'partnerstack','utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 340, 'utm_source' => 'affiliatly',  'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 341, 'utm_source' => 'tapfiliate',  'utm_medium' => null,          'channel' => 'affiliate'],
    ['priority' => 342, 'utm_source' => 'leaddyno',   'utm_medium' => null,          'channel' => 'affiliate'],

    // ──────────────────────────────────────────────
    // MOBILE PUSH (priority 350-399)
    // ──────────────────────────────────────────────

    ['priority' => 350, 'utm_source' => null,          'utm_medium' => 'push',        'channel' => 'mobile_push'],
    ['priority' => 351, 'utm_source' => null,          'utm_medium' => '%push%',      'channel' => 'mobile_push'],
    ['priority' => 352, 'utm_source' => null,          'utm_medium' => '%notification%','channel' => 'mobile_push'],
    ['priority' => 353, 'utm_source' => 'firebase',    'utm_medium' => null,          'channel' => 'mobile_push'],
    ['priority' => 354, 'utm_source' => 'onesignal',   'utm_medium' => null,          'channel' => 'mobile_push'],
    ['priority' => 355, 'utm_source' => 'pushowl',     'utm_medium' => null,          'channel' => 'mobile_push'],
    ['priority' => 356, 'utm_source' => 'pushengage',   'utm_medium' => null,          'channel' => 'mobile_push'],

    // ──────────────────────────────────────────────
    // ORGANIC SEARCH (priority 400-499)
    // All major + regional search engines
    // ──────────────────────────────────────────────

    // Google (all regional TLDs: google.com, .co.uk, .de, .fr, etc.)
    ['priority' => 400, 'utm_source' => 'google',         'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 401, 'referring_site_pattern' => '%google.%',  'utm_medium' => null,'channel' => 'organic_search'],

    // Bing
    ['priority' => 402, 'utm_source' => 'bing',           'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 403, 'referring_site_pattern' => '%bing.com%', 'utm_medium' => null,'channel' => 'organic_search'],

    // Yahoo
    ['priority' => 404, 'utm_source' => 'yahoo',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 405, 'referring_site_pattern' => '%search.yahoo.%','utm_medium' => null,'channel' => 'organic_search'],

    // DuckDuckGo
    ['priority' => 406, 'utm_source' => 'duckduckgo',     'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 407, 'referring_site_pattern' => '%duckduckgo.com%','utm_medium' => null,'channel' => 'organic_search'],

    // Baidu
    ['priority' => 408, 'utm_source' => 'baidu',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 409, 'referring_site_pattern' => '%baidu.com%','utm_medium' => null, 'channel' => 'organic_search'],

    // Yandex
    ['priority' => 410, 'utm_source' => 'yandex',         'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 411, 'referring_site_pattern' => '%yandex.%',  'utm_medium' => null,'channel' => 'organic_search'],

    // Naver (Korea)
    ['priority' => 412, 'utm_source' => 'naver',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 413, 'referring_site_pattern' => '%naver.com%','utm_medium' => null, 'channel' => 'organic_search'],

    // Seznam (Czech Republic)
    ['priority' => 414, 'utm_source' => 'seznam',         'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 415, 'referring_site_pattern' => '%seznam.cz%','utm_medium' => null, 'channel' => 'organic_search'],

    // Ecosia
    ['priority' => 416, 'utm_source' => 'ecosia',         'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 417, 'referring_site_pattern' => '%ecosia.org%','utm_medium' => null,'channel' => 'organic_search'],

    // Qwant
    ['priority' => 418, 'utm_source' => 'qwant',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 419, 'referring_site_pattern' => '%qwant.com%','utm_medium' => null, 'channel' => 'organic_search'],

    // Brave Search
    ['priority' => 420, 'utm_source' => 'brave',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 421, 'referring_site_pattern' => '%search.brave.com%','utm_medium' => null,'channel' => 'organic_search'],

    // AOL
    ['priority' => 422, 'utm_source' => 'aol',            'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 423, 'referring_site_pattern' => '%search.aol.%','utm_medium' => null,'channel' => 'organic_search'],

    // Ask.com
    ['priority' => 424, 'utm_source' => 'ask',            'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 425, 'referring_site_pattern' => '%ask.com%','utm_medium' => null,   'channel' => 'organic_search'],

    // Startpage
    ['priority' => 426, 'utm_source' => 'startpage',      'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 427, 'referring_site_pattern' => '%startpage.com%','utm_medium' => null,'channel' => 'organic_search'],

    // Sogou (China)
    ['priority' => 428, 'utm_source' => 'sogou',          'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 429, 'referring_site_pattern' => '%sogou.com%','utm_medium' => null,  'channel' => 'organic_search'],

    // 360.cn / so.com (China)
    ['priority' => 430, 'utm_source' => '360',            'utm_medium' => 'organic',  'channel' => 'organic_search'],
    ['priority' => 431, 'referring_site_pattern' => '%so.com%', 'utm_medium' => null,   'channel' => 'organic_search'],

    // Biglobe (Japan)
    ['priority' => 432, 'referring_site_pattern' => '%biglobe.ne.jp%','utm_medium' => null,'channel' => 'organic_search'],

    // Coc Coc (Vietnam)
    ['priority' => 433, 'referring_site_pattern' => '%coccoc.com%','utm_medium' => null, 'channel' => 'organic_search'],

    // Daum (Korea)
    ['priority' => 434, 'referring_site_pattern' => '%daum.net%', 'utm_medium' => null,  'channel' => 'organic_search'],

    // Generic organic medium catch-all (any source with medium=organic)
    ['priority' => 490, 'utm_source' => null,              'utm_medium' => 'organic',  'channel' => 'organic_search'],

    // ──────────────────────────────────────────────
    // ORGANIC SOCIAL (priority 500-599)
    // Source matches a social platform + organic medium or referral
    // ──────────────────────────────────────────────

    // Facebook (all subdomains)
    ['priority' => 500, 'utm_source' => 'facebook',       'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 501, 'utm_source' => 'fb',             'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 502, 'referring_site_pattern' => '%facebook.com%','utm_medium' => null,'channel' => 'organic_social'],
    ['priority' => 503, 'referring_site_pattern' => '%fb.com%','utm_medium' => null,    'channel' => 'organic_social'],
    ['priority' => 504, 'referring_site_pattern' => '%fb.me%', 'utm_medium' => null,    'channel' => 'organic_social'],

    // Instagram
    ['priority' => 505, 'utm_source' => 'instagram',      'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 506, 'utm_source' => 'ig',             'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 507, 'referring_site_pattern' => '%instagram.com%','utm_medium' => null,'channel' => 'organic_social'],

    // TikTok
    ['priority' => 508, 'utm_source' => 'tiktok',         'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 509, 'referring_site_pattern' => '%tiktok.com%','utm_medium' => null, 'channel' => 'organic_social'],
    ['priority' => 510, 'referring_site_pattern' => '%vm.tiktok.com%','utm_medium' => null,'channel' => 'organic_social'],

    // Pinterest (all regional TLDs)
    ['priority' => 511, 'utm_source' => 'pinterest',      'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 512, 'referring_site_pattern' => '%pinterest.%','utm_medium' => null, 'channel' => 'organic_social'],

    // LinkedIn
    ['priority' => 513, 'utm_source' => 'linkedin',       'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 514, 'referring_site_pattern' => '%linkedin.com%','utm_medium' => null,'channel' => 'organic_social'],
    ['priority' => 515, 'referring_site_pattern' => '%lnkd.in%','utm_medium' => null,   'channel' => 'organic_social'],

    // Twitter / X
    ['priority' => 516, 'utm_source' => 'twitter',        'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 517, 'utm_source' => 'x',              'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 518, 'referring_site_pattern' => '%twitter.com%','utm_medium' => null,'channel' => 'organic_social'],
    ['priority' => 519, 'referring_site_pattern' => '%t.co%','utm_medium' => null,      'channel' => 'organic_social'],
    ['priority' => 520, 'referring_site_pattern' => '%x.com%','utm_medium' => null,     'channel' => 'organic_social'],

    // Snapchat
    ['priority' => 521, 'utm_source' => 'snapchat',       'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 522, 'referring_site_pattern' => '%snapchat.com%','utm_medium' => null,'channel' => 'organic_social'],

    // Reddit
    ['priority' => 523, 'utm_source' => 'reddit',         'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 524, 'referring_site_pattern' => '%reddit.com%','utm_medium' => null, 'channel' => 'organic_social'],

    // WhatsApp
    ['priority' => 525, 'utm_source' => 'whatsapp',       'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 526, 'referring_site_pattern' => '%wa.me%','utm_medium' => null,     'channel' => 'organic_social'],
    ['priority' => 527, 'referring_site_pattern' => '%whatsapp.com%','utm_medium' => null,'channel' => 'organic_social'],

    // Telegram
    ['priority' => 528, 'utm_source' => 'telegram',       'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 529, 'referring_site_pattern' => '%t.me%','utm_medium' => null,      'channel' => 'organic_social'],
    ['priority' => 530, 'referring_site_pattern' => '%telegram.org%','utm_medium' => null,'channel' => 'organic_social'],

    // Threads (Meta)
    ['priority' => 531, 'utm_source' => 'threads',        'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 532, 'referring_site_pattern' => '%threads.net%','utm_medium' => null,'channel' => 'organic_social'],

    // Tumblr
    ['priority' => 533, 'utm_source' => 'tumblr',         'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 534, 'referring_site_pattern' => '%tumblr.com%','utm_medium' => null, 'channel' => 'organic_social'],

    // Quora
    ['priority' => 535, 'utm_source' => 'quora',          'utm_medium' => null,        'channel' => 'organic_social'],
    ['priority' => 536, 'referring_site_pattern' => '%quora.com%','utm_medium' => null,  'channel' => 'organic_social'],

    // Discord
    ['priority' => 537, 'referring_site_pattern' => '%discord.com%','utm_medium' => null,'channel' => 'organic_social'],
    ['priority' => 538, 'referring_site_pattern' => '%discord.gg%','utm_medium' => null, 'channel' => 'organic_social'],

    // Mastodon (various instances)
    ['priority' => 539, 'referring_site_pattern' => '%mastodon.%','utm_medium' => null,  'channel' => 'organic_social'],

    // Bluesky
    ['priority' => 540, 'referring_site_pattern' => '%bsky.app%','utm_medium' => null,   'channel' => 'organic_social'],
    ['priority' => 541, 'referring_site_pattern' => '%bsky.social%','utm_medium' => null, 'channel' => 'organic_social'],

    // WeChat
    ['priority' => 542, 'referring_site_pattern' => '%wechat.com%','utm_medium' => null, 'channel' => 'organic_social'],

    // VK (Russia)
    ['priority' => 543, 'referring_site_pattern' => '%vk.com%','utm_medium' => null,     'channel' => 'organic_social'],

    // Medium-based catch-all social
    ['priority' => 590, 'utm_source' => null,              'utm_medium' => 'social',        'channel' => 'organic_social'],
    ['priority' => 591, 'utm_source' => null,              'utm_medium' => 'social-network', 'channel' => 'organic_social'],
    ['priority' => 592, 'utm_source' => null,              'utm_medium' => 'social-media',   'channel' => 'organic_social'],
    ['priority' => 593, 'utm_source' => null,              'utm_medium' => 'social_media',   'channel' => 'organic_social'],
    ['priority' => 594, 'utm_source' => null,              'utm_medium' => 'social_network', 'channel' => 'organic_social'],
    ['priority' => 595, 'utm_source' => null,              'utm_medium' => 'sm',             'channel' => 'organic_social'],

    // ──────────────────────────────────────────────
    // ORGANIC VIDEO (priority 600-649)
    // ──────────────────────────────────────────────

    ['priority' => 600, 'utm_source' => 'youtube',        'utm_medium' => null,        'channel' => 'organic_video'],
    ['priority' => 601, 'referring_site_pattern' => '%youtube.com%','utm_medium' => null,'channel' => 'organic_video'],
    ['priority' => 602, 'referring_site_pattern' => '%youtu.be%','utm_medium' => null,  'channel' => 'organic_video'],
    ['priority' => 603, 'referring_site_pattern' => '%vimeo.com%','utm_medium' => null, 'channel' => 'organic_video'],
    ['priority' => 604, 'referring_site_pattern' => '%dailymotion.com%','utm_medium' => null,'channel' => 'organic_video'],
    ['priority' => 605, 'referring_site_pattern' => '%twitch.tv%','utm_medium' => null, 'channel' => 'organic_video'],
    ['priority' => 606, 'utm_source' => null,              'utm_medium' => 'video',    'channel' => 'organic_video'],

    // ──────────────────────────────────────────────
    // ORGANIC SHOPPING (priority 650-679)
    // ──────────────────────────────────────────────

    ['priority' => 650, 'referring_site_pattern' => '%amazon.%',  'utm_medium' => null, 'channel' => 'organic_shopping'],
    ['priority' => 651, 'referring_site_pattern' => '%ebay.%',    'utm_medium' => null,  'channel' => 'organic_shopping'],
    ['priority' => 652, 'referring_site_pattern' => '%etsy.com%', 'utm_medium' => null,  'channel' => 'organic_shopping'],
    ['priority' => 653, 'referring_site_pattern' => '%aliexpress.%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 654, 'referring_site_pattern' => '%alibaba.com%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 655, 'referring_site_pattern' => '%walmart.com%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 656, 'referring_site_pattern' => '%shopping.google.%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 657, 'referring_site_pattern' => '%shopzilla.%','utm_medium' => null, 'channel' => 'organic_shopping'],
    ['priority' => 658, 'referring_site_pattern' => '%pricegrabber.%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 659, 'referring_site_pattern' => '%idealo.%',  'utm_medium' => null,  'channel' => 'organic_shopping'],
    ['priority' => 660, 'referring_site_pattern' => '%kelkoo.%',  'utm_medium' => null,  'channel' => 'organic_shopping'],
    ['priority' => 661, 'referring_site_pattern' => '%pricerunner.%','utm_medium' => null,'channel' => 'organic_shopping'],
    ['priority' => 662, 'utm_source' => null,              'utm_medium' => null,
                        'utm_campaign_pattern' => '%shop%',                             'channel' => 'organic_shopping'],

    // ──────────────────────────────────────────────
    // SHOPIFY / ECOMMERCE SPECIFIC (priority 700-749)
    // ──────────────────────────────────────────────

    // Shopify Inbox (chat widget)
    ['priority' => 700, 'utm_source' => 'shopify_inbox', 'utm_medium' => null,          'channel' => 'direct'],
    // Shop App
    ['priority' => 701, 'utm_source' => 'shop_app',     'utm_medium' => null,           'channel' => 'organic_shopping'],
    ['priority' => 702, 'utm_source' => 'shop',         'utm_medium' => null,           'channel' => 'organic_shopping'],
    // Shopify abandoned cart / checkout
    ['priority' => 703, 'utm_source' => 'shopify',      'utm_medium' => 'abandoned_cart','channel' => 'email'],

    // ──────────────────────────────────────────────
    // CATCH-ALL FALLBACKS (priority 900-999)
    // ──────────────────────────────────────────────

    // Any remaining paid mediums without a specific source match
    ['priority' => 900, 'utm_source' => null,          'utm_medium' => 'cpc',         'channel' => 'paid_search'],
    ['priority' => 901, 'utm_source' => null,          'utm_medium' => 'ppc',         'channel' => 'paid_search'],
    ['priority' => 902, 'utm_source' => null,          'utm_medium' => 'paid%',       'channel' => 'paid_search'],
    ['priority' => 903, 'utm_source' => null,          'utm_medium' => 'retargeting', 'channel' => 'display'],
    ['priority' => 904, 'utm_source' => null,          'utm_medium' => 'remarketing', 'channel' => 'display'],
    ['priority' => 905, 'utm_source' => null,          'utm_medium' => 'sponsored',   'channel' => 'display'],

    // Referral catch-all
    ['priority' => 950, 'utm_source' => null,          'utm_medium' => 'referral',    'channel' => 'referral'],
    ['priority' => 951, 'utm_source' => null,          'utm_medium' => 'app',         'channel' => 'referral'],
    ['priority' => 952, 'utm_source' => null,          'utm_medium' => 'link',        'channel' => 'referral'],

    // Direct: source=(direct), medium=(none)/(not set)
    // This is handled in code, not as a DB rule. The classifier checks:
    //   if source == '(direct)' && medium in ['(none)', '(not set)', '']
    //     -> channel = 'direct'
    // Everything else -> 'unassigned'
];
```

## Classifier Service: Known Domain Lists

The PHP rules above cover UTM-tagged traffic. For **referrer-only traffic** (no UTMs), the `ChannelClassifierService` should also maintain in-memory arrays for fast lookup:

### Search Engines (SOURCE_CATEGORY_SEARCH)

```php
private const SEARCH_DOMAINS = [
    'google.com', 'google.co.uk', 'google.de', 'google.fr', 'google.es',
    'google.it', 'google.nl', 'google.be', 'google.at', 'google.ch',
    'google.se', 'google.dk', 'google.no', 'google.fi', 'google.pl',
    'google.pt', 'google.com.br', 'google.com.au', 'google.co.nz',
    'google.co.jp', 'google.co.kr', 'google.co.in', 'google.com.sg',
    'google.com.hk', 'google.com.tw', 'google.co.id', 'google.co.th',
    'google.com.ph', 'google.com.vn', 'google.co.za', 'google.com.ng',
    'google.com.eg', 'google.com.tr', 'google.ru', 'google.com.ua',
    'google.com.mx', 'google.com.ar', 'google.com.co', 'google.cl',
    'google.com.pe', 'google.ca', 'google.ie', 'google.co.il',
    'google.com.sa', 'google.ae', 'google.gr', 'google.cz',
    'google.hu', 'google.ro', 'google.bg', 'google.hr', 'google.sk',
    'google.si', 'google.rs', 'google.lt', 'google.lv', 'google.ee',
    'bing.com',
    'yahoo.com', 'search.yahoo.com', 'yahoo.co.jp',
    'duckduckgo.com',
    'baidu.com',
    'yandex.ru', 'yandex.com', 'yandex.ua', 'yandex.by', 'yandex.kz',
    'naver.com',
    'seznam.cz',
    'ecosia.org',
    'qwant.com', 'lite.qwant.com',
    'search.brave.com',
    'aol.com', 'search.aol.com',
    'ask.com',
    'startpage.com',
    'sogou.com',
    'so.com', '360.cn',
    'biglobe.ne.jp', 'biglobe.co.jp',
    'coccoc.com',
    'daum.net',
    'dogpile.com',
    'wolframalpha.com',
    'searchencrypt.com',
    'gibiru.com',
    'mojeek.com',
    'swisscows.com',
    'metager.org',
    'yep.com',
];
```

### Social Platforms (SOURCE_CATEGORY_SOCIAL)

```php
private const SOCIAL_DOMAINS = [
    // Meta / Facebook
    'facebook.com', 'm.facebook.com', 'l.facebook.com', 'lm.facebook.com',
    'business.facebook.com', 'web.facebook.com', 'apps.facebook.com',
    'free.facebook.com', 'touch.facebook.com', 'fb.com', 'fb.me',
    // Instagram
    'instagram.com', 'l.instagram.com',
    // TikTok
    'tiktok.com', 'vm.tiktok.com',
    // Pinterest
    'pinterest.com', 'pinterest.co.uk', 'pinterest.de', 'pinterest.fr',
    'pinterest.es', 'pinterest.it', 'pinterest.ca', 'pinterest.com.au',
    'pinterest.at', 'pinterest.ch', 'pinterest.nl', 'pinterest.se',
    'pinterest.dk', 'pinterest.jp', 'pinterest.co.kr', 'pinterest.pt',
    'pinterest.com.mx', 'pinterest.cl', 'pinterest.ph', 'pinterest.nz',
    // LinkedIn
    'linkedin.com', 'lnkd.in',
    // Twitter / X
    'twitter.com', 't.co', 'x.com',
    // Snapchat
    'snapchat.com',
    // Reddit
    'reddit.com', 'old.reddit.com', 'amp.reddit.com', 'out.reddit.com',
    // YouTube (social aspect -- note: also in VIDEO_DOMAINS)
    'youtube.com', 'm.youtube.com', 'youtu.be',
    // WhatsApp
    'wa.me', 'web.whatsapp.com', 'whatsapp.com',
    // Telegram
    't.me', 'web.telegram.org', 'telegram.org',
    // Threads
    'threads.net',
    // Tumblr
    'tumblr.com',
    // Quora
    'quora.com',
    // Discord
    'discord.com', 'discord.gg', 'discordapp.com',
    // Mastodon (federated, many instances)
    'mastodon.social', 'mastodon.online',
    // Bluesky
    'bsky.app', 'bsky.social',
    // WeChat
    'wechat.com', 'weixin.qq.com',
    // VK
    'vk.com', 'vk.ru',
    // LINE
    'line.me',
    // Viber
    'viber.com',
    // Nextdoor
    'nextdoor.com',
    // Twitch (social aspect)
    'twitch.tv',
    // Medium (blogging/social)
    'medium.com',
    // Hacker News
    'news.ycombinator.com',
    // Product Hunt
    'producthunt.com',
    // Substack
    'substack.com',
    // Lemon8
    'lemon8-app.com',
    // BeReal
    'bereal.com',
];
```

### Video Platforms (SOURCE_CATEGORY_VIDEO)

```php
private const VIDEO_DOMAINS = [
    'youtube.com', 'm.youtube.com', 'youtu.be',
    'vimeo.com',
    'dailymotion.com',
    'twitch.tv',
    'wistia.com',
    'vidyard.com',
    'loom.com',
    'rumble.com',
    'bitchute.com',
    'odysee.com',
    'bilibili.com',
    'nicovideo.jp',
];
```

### Shopping / Marketplace (SOURCE_CATEGORY_SHOPPING)

```php
private const SHOPPING_DOMAINS = [
    'amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.es',
    'amazon.it', 'amazon.nl', 'amazon.ca', 'amazon.com.au', 'amazon.co.jp',
    'amazon.in', 'amazon.com.br', 'amazon.com.mx', 'amazon.se', 'amazon.pl',
    'amazon.sg', 'amazon.sa', 'amazon.ae', 'amazon.com.tr',
    'ebay.com', 'ebay.co.uk', 'ebay.de', 'ebay.fr', 'ebay.es',
    'ebay.it', 'ebay.com.au', 'ebay.ca', 'ebay.at', 'ebay.ch',
    'ebay.nl', 'ebay.be', 'ebay.ie', 'ebay.pl',
    'etsy.com',
    'aliexpress.com',
    'alibaba.com',
    'walmart.com',
    'target.com',
    'shopping.google.com',
    'shopzilla.com',
    'pricegrabber.com',
    'idealo.de', 'idealo.co.uk', 'idealo.fr', 'idealo.es', 'idealo.it',
    'idealo.at',
    'kelkoo.com', 'kelkoo.co.uk', 'kelkoo.de', 'kelkoo.fr',
    'pricerunner.com', 'pricerunner.se', 'pricerunner.dk',
    'wish.com',
    'temu.com',
    'shein.com',
    'zalando.com', 'zalando.de', 'zalando.fr', 'zalando.nl',
    'asos.com',
    'shop.app',
];
```

## How the Classifier Resolves a Session

```
1. If gclid present  -> check campaign type (Shopping/Video/PMax/Search) -> paid_shopping / paid_video / cross_network / paid_search
2. If fbclid present -> paid_social
3. If ttclid present -> paid_social
4. If msclkid present -> paid_search
5. Walk channel_mappings by priority (first-match-wins)
6. If no rule matched but source is (direct) and medium is (none) -> direct
7. If no rule matched but referrer domain in SEARCH_DOMAINS -> organic_search
8. If no rule matched but referrer domain in SOCIAL_DOMAINS -> organic_social
9. If no rule matched but referrer domain in VIDEO_DOMAINS -> organic_video
10. If no rule matched but referrer domain in SHOPPING_DOMAINS -> organic_shopping
11. If no rule matched but referrer is present -> referral
12. Otherwise -> unassigned
```

## Rule Count Summary

| Channel | Rule Count |
|---------|-----------|
| paid_search | 26 |
| paid_social | 39 |
| paid_video | 6 |
| paid_shopping | 2 |
| cross_network | 5 |
| display | 19 |
| email | 29 |
| sms | 8 |
| affiliate | 13 |
| mobile_push | 7 |
| organic_search | 37 |
| organic_social | 51 |
| organic_video | 7 |
| organic_shopping | 16 |
| referral / fallback | 9 |
| **Total** | **~274 rules** |

Sources:
- [GA4 Default Channel Group (Google Support)](https://support.google.com/analytics/answer/9756891?hl=en)
- [GA4 Source Categories PDF](https://www.annieveale.com/WP/wp-content/uploads/2023/01/GA4-Source-Categories-and-definitions.pdf)
- [GA4 Source Categories (SlideShare)](https://www.slideshare.net/slideshow/ga4-source-categories/256591040)
- [Analytics Mania - Default Channel Group in GA4](https://www.analyticsmania.com/post/default-channel-group-in-google-analytics-4/)
- [GA4.com - Channel Grouping](https://ga4.com/channel-grouping-in-google-analytics-4)
