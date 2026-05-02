<?php

namespace App\Integrations\Contracts\Category;

use App\Integrations\Contracts\Base\Connectable;
use App\Integrations\Contracts\Base\Syncable;
use App\Models\CustomerSegment;
use App\Models\EmailAccount;
use Carbon\CarbonImmutable;

/**
 * Contract for email/SMS marketing platforms (Klaviyo, future Omnisend, Mailchimp).
 *
 * Covers the full email marketing data lifecycle: structure sync (campaigns + flows),
 * performance stats, and audience push (send Nexstage segments back to the platform).
 *
 * To add Omnisend: implement this interface, map their campaign/automation
 * structure to our EmailCampaign/EmailFlow models, and register in the container.
 */
interface EmailPlatformConnector extends Connectable, Syncable
{
    /**
     * Sync campaign structure.
     *
     * Upserts EmailCampaign records (name, channel, status, sent_at, subject_line).
     * Does not fetch performance stats -- that's syncCampaignStats().
     */
    public function syncCampaigns(EmailAccount $account): void;

    /**
     * Sync flow/automation structure.
     *
     * Upserts EmailFlow records (name, status, trigger_type).
     * Klaviyo calls these "flows", Omnisend calls them "automations".
     */
    public function syncFlows(EmailAccount $account): void;

    /**
     * Sync campaign performance stats for the given date range.
     *
     * Updates EmailCampaign records with: recipients, delivered, opens,
     * clicks, bounced, unsubscribes, conversions, revenue.
     *
     * Be mindful of reporting API rate limits (Klaviyo: 225 calls/day).
     */
    public function syncCampaignStats(
        EmailAccount $account,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void;

    /**
     * Sync flow performance stats for the given date range.
     *
     * Updates EmailFlow records with: total_revenue, total_conversions,
     * and per-message breakdowns where available.
     */
    public function syncFlowStats(
        EmailAccount $account,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void;

    /**
     * Push a customer segment to the platform as a list/segment.
     *
     * Creates a list on the platform, adds matching customer profiles,
     * and returns the remote list/segment ID for tracking.
     *
     * Used for retargeting audiences and email campaign targeting.
     *
     * @return string  The platform's list/segment ID (stored in CustomerSegment.sync_destination_id).
     */
    public function pushSegment(EmailAccount $account, CustomerSegment $segment): string;
}
