<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates pixel_order_correlations — the output table for EventOrderCorrelator.
 *
 * One row per order, updated in place as the correlator re-runs with fresher data.
 * The unique key is order_id (one best-match pixel session per order).
 *
 * columns:
 *  - order_id        FK → orders.id — the matched order
 *  - workspace_id    for partition filtering; redundant with order but avoids joins
 *  - pixel_event_id  FK → pixel_events.id — the trigger event (purchase/begin_checkout)
 *  - session_id      denormalised from pixel_events.session_id for easy joining
 *  - method          'session_id' | 'ip_proximity' — confidence signal for the match
 *
 * Future: once clicks_modeled column is added to daily_snapshots, a nightly
 * recompute job should aggregate correlations here and write back to snapshots.
 *
 * @see app/Services/PixelEvents/EventOrderCorrelator.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pixel_order_correlations', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('workspace_id')->index();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();

            // One correlation per order.
            $table->unsignedBigInteger('order_id')->unique();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->unsignedBigInteger('pixel_event_id')->nullable();
            $table->foreign('pixel_event_id')->references('id')->on('pixel_events')->nullOnDelete();

            $table->string('session_id', 64)->nullable();

            // 'session_id' = high-confidence direct match; 'ip_proximity' = heuristic
            $table->string('method', 32)->default('session_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixel_order_correlations');
    }
};
