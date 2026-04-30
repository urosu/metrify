<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates shopify_daily_sessions — daily session counts pulled from the Shopify
 * Analytics API (ShopifyQL endpoint, with REST /reports.json fallback).
 *
 * Unique on (store_id, date, source) so multiple source breakdowns can coexist.
 * NULL source = store-total aggregate row (what SnapshotBuilderService reads by default).
 *
 * Written by: SyncShopifyAnalyticsJob (nightly)
 * Read by:    SnapshotBuilderService::buildDaily (sessions_source = 'shopify')
 *
 * Re-auth note: the read_analytics scope is required to populate this table.
 * Stores connected before this migration was added may not have that scope
 * and will receive empty rows until the merchant re-authenticates.
 *
 * @see docs/planning/schema.md §1.5 (daily_snapshots.sessions)
 * @see app/Jobs/SyncShopifyAnalyticsJob.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_daily_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');
            // Total sessions (visits) from Shopify Analytics.
            $table->integer('visits');
            // Unique visitors reported by Shopify for the same period.
            $table->integer('visitors')->nullable();
            // Traffic source label as returned by Shopify Analytics (e.g. 'direct', 'social').
            // NULL = store-total aggregate row used by SnapshotBuilderService.
            $table->string('source', 64)->nullable();
            // When this row was last synced from Shopify API.
            $table->timestamp('synced_at');
            $table->timestamps();

            // One row per (store, date, source). source=NULL is the all-traffic aggregate.
            $table->unique(['store_id', 'date', 'source'], 'uq_shopify_sessions_store_date_src');
            $table->index(['workspace_id', 'date']);
            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_daily_sessions');
    }
};
