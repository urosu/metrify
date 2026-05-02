<?php

namespace App\Integrations;

/**
 * Registry of all supported integration providers.
 *
 * Adding a new integration:
 * 1. Add a case here (e.g., case Pinterest = 'pinterest')
 * 2. Implement the appropriate category contract (e.g., AdPlatformConnector)
 * 3. Bind the implementation in AppServiceProvider
 * 4. Add a Schedule entry in console/kernel for the sync job
 */
enum IntegrationProvider: string
{
    // Stores
    case Shopify = 'shopify';
    case WooCommerce = 'woocommerce';

    // Ad Platforms
    case Meta = 'meta';
    case Google = 'google';
    case TikTok = 'tiktok';
    // case Pinterest = 'pinterest';   // future
    // case Snapchat = 'snapchat';     // future

    // Analytics
    case GA4 = 'ga4';
    case GSC = 'gsc';

    // Email/SMS
    case Klaviyo = 'klaviyo';
    // case Omnisend = 'omnisend';     // future

    /**
     * The integration category for grouping in the UI.
     *
     * @return 'store'|'ads'|'analytics'|'email'
     */
    public function category(): string
    {
        return match ($this) {
            self::Shopify, self::WooCommerce => 'store',
            self::Meta, self::Google, self::TikTok => 'ads',
            self::GA4, self::GSC => 'analytics',
            self::Klaviyo => 'email',
        };
    }

    /**
     * Human-readable display name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Shopify => 'Shopify',
            self::WooCommerce => 'WooCommerce',
            self::Meta => 'Meta Ads',
            self::Google => 'Google Ads',
            self::TikTok => 'TikTok Ads',
            self::GA4 => 'Google Analytics 4',
            self::GSC => 'Google Search Console',
            self::Klaviyo => 'Klaviyo',
        };
    }

    /**
     * Whether this provider uses OAuth (vs API key auth).
     */
    public function usesOAuth(): bool
    {
        return match ($this) {
            self::WooCommerce => false, // consumer key + secret
            default => true,
        };
    }

    /**
     * The contract interface FQCN that implementations must satisfy.
     *
     * @return class-string
     */
    public function contractClass(): string
    {
        return match ($this) {
            self::Shopify => Contracts\Platform\ShopifyConnector::class,
            self::WooCommerce => Contracts\Platform\WooCommerceConnector::class,
            self::Meta => Contracts\Platform\MetaAdsConnector::class,
            self::Google => Contracts\Platform\GoogleAdsConnector::class,
            self::TikTok => Contracts\Category\AdPlatformConnector::class,
            self::GA4 => Contracts\Category\AnalyticsConnector::class,
            self::GSC => Contracts\Category\AnalyticsConnector::class,
            self::Klaviyo => Contracts\Platform\KlaviyoConnector::class,
        };
    }
}
