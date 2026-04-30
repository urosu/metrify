<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates cost configuration tables: store_cost_settings, shipping_rules,
 * transaction_fee_rules, tax_rules, opex_allocations, platform_fee_rules.
 *
 * Promotes cost settings from stores.cost_settings JSONB into real tables.
 *
 * @see docs/planning/schema.md §1.6
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_cost_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // NULL = workspace-wide default.
            $table->bigInteger('store_id')->nullable();
            // CHECK constraint below.
            $table->string('shipping_mode', 24);
            $table->decimal('shipping_flat_rate_native', 14, 2)->nullable();
            $table->decimal('shipping_per_order_native', 14, 2)->nullable();
            $table->char('default_currency', 3);
            // 0-100 — drives amber StatusDot in Settings nav.
            $table->smallInteger('completeness_score')->default(0);
            $table->timestamp('last_recalculated_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();

            $table->unique(['workspace_id', 'store_id']);
        });

        DB::statement("ALTER TABLE store_cost_settings ADD CONSTRAINT check_scs_shipping_mode CHECK (shipping_mode IN ('flat_rate','per_order','weight_tiered','none'))");

        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->bigInteger('store_id')->nullable();
            $table->integer('min_weight_grams');
            $table->integer('max_weight_grams');
            // NULL = any destination.
            $table->char('destination_country', 2)->nullable();
            $table->decimal('cost_native', 14, 2);
            $table->char('currency', 3);
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();

            $table->index(['workspace_id', 'store_id', 'min_weight_grams']);
        });

        Schema::create('transaction_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->bigInteger('store_id')->nullable();
            // CHECK constraint below.
            $table->string('processor', 32);
            // Basis points: 290 = 2.9%.
            $table->smallInteger('percentage_bps');
            $table->decimal('fixed_fee_native', 10, 4);
            $table->char('currency', 3);
            // NULL = applies to all payment methods.
            $table->string('applies_to_payment_method', 64)->nullable();
            // For the "Seeded — verify against your contract" chip.
            $table->boolean('is_seeded')->default(false);
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();

            $table->index(['workspace_id', 'store_id', 'processor']);
        });

        DB::statement("ALTER TABLE transaction_fee_rules ADD CONSTRAINT check_tfr_processor CHECK (processor IN ('shopify_payments','stripe','paypal','mollie','klarna','manual'))");

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            // NULL = global seed row.
            $table->bigInteger('workspace_id')->nullable();
            $table->char('country_code', 2);
            $table->smallInteger('standard_rate_bps');
            $table->smallInteger('reduced_rate_bps')->nullable();
            $table->boolean('is_included_in_price')->default(true);
            $table->smallInteger('digital_goods_override_bps')->nullable();
            $table->boolean('is_seeded')->default(false);
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });

        // Partial unique indexes — handles nullable workspace_id correctly.
        DB::statement("CREATE UNIQUE INDEX tax_rules_workspace_country_unique ON tax_rules (workspace_id, country_code) WHERE workspace_id IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX tax_rules_global_country_unique ON tax_rules (country_code) WHERE workspace_id IS NULL");

        Schema::create('opex_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('category', 64);
            $table->decimal('monthly_cost_native', 14, 2);
            $table->char('currency', 3);
            // CHECK constraint below.
            $table->string('allocation_mode', 16);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'effective_from', 'effective_to']);
        });

        DB::statement("ALTER TABLE opex_allocations ADD CONSTRAINT check_opex_allocation_mode CHECK (allocation_mode IN ('per_order','per_day'))");

        Schema::create('platform_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->bigInteger('store_id')->nullable();
            $table->string('item_label', 128);
            $table->decimal('monthly_cost_native', 14, 2);
            $table->char('currency', 3);
            // CHECK constraint below.
            $table->string('allocation_mode', 16);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();

            $table->index(['workspace_id', 'store_id']);
        });

        DB::statement("ALTER TABLE platform_fee_rules ADD CONSTRAINT check_pfr_allocation_mode CHECK (allocation_mode IN ('per_order','per_day'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_rules');
        Schema::dropIfExists('opex_allocations');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('transaction_fee_rules');
        Schema::dropIfExists('shipping_rules');
        Schema::dropIfExists('store_cost_settings');
    }
};
