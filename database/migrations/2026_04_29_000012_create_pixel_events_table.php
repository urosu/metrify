<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the pixel_events table for server-side pixel tracking.
 *
 * Events are captured via the public POST /pixel/{workspace}/event endpoint
 * and can later be correlated with orders for `clicks_modeled` attribution.
 *
 * Design notes:
 *  - event_id is dedup key — composite UNIQUE (workspace_id, event_id) prevents
 *    double-counting the same browser fire + server fire for the same conversion.
 *  - ip_address stored as varchar(45) to hold both IPv4 and IPv6.
 *    TODO privacy: if workspace has a "privacy-strict" flag, truncate the last
 *    octet of IPv4 (or last 80 bits of IPv6) before storing.
 *  - payload JSONB holds platform-specific extras (cart contents, value, currency,
 *    fbclid, gclid, fbp, fbc, etc.) without schema churn.
 *  - No FK constraint on store_id — stores can be deleted; nullable + index is
 *    sufficient for correlation queries.
 *
 * @see app/Http/Controllers/PixelEventController.php
 * @see app/Models/PixelEvent.php
 * @see app/Services/PixelEvents/EventOrderCorrelator.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pixel_events', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('workspace_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->index('workspace_id');

            // Nullable — a workspace may track across multiple stores.
            $table->unsignedBigInteger('store_id')->nullable()->index();

            // Client-generated UUID; used for browser-pixel / server-event dedup.
            $table->string('event_id', 64);

            $table->string('event_type', 32); // page_view | add_to_cart | begin_checkout | purchase | custom

            $table->timestamp('occurred_at')->index();

            // Groups events from the same user session for session reconstruction.
            $table->string('session_id', 64)->nullable();

            $table->string('user_agent', 512)->nullable();

            // varchar(45) covers both IPv4 and IPv6 addresses.
            $table->string('ip_address', 45)->nullable();

            $table->text('url');
            $table->text('referrer')->nullable();

            // UTM parameters — nullable; populated from the query string of the page URL.
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();

            // Arbitrary extra data: cart contents, value, currency, click IDs, etc.
            $table->jsonb('payload')->default('{}');

            $table->timestamps();

            // ── Composite indexes ───────────────────────────────────────────────────

            // Range queries (dashboard time-series, export).
            $table->index(['workspace_id', 'occurred_at']);

            // Session reconstruction for attribution correlator.
            $table->index(['workspace_id', 'session_id']);

            // Dedup — prevents double-counting the same event from browser + server.
            $table->unique(['workspace_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixel_events');
    }
};
