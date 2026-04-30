<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates fx_rates table — DB-cached FX rates.
 *
 * Never call Frankfurter at query time — always read from this table via FxRateService.
 * UpdateFxRatesJob is the sole Frankfurter caller.
 *
 * @see docs/planning/schema.md §1.7 fx_rates
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base_currency', 3)->default('EUR');
            $table->char('target_currency', 3);
            $table->decimal('rate', 16, 8);
            $table->date('date');
            $table->timestamp('created_at')->nullable();

            $table->unique(['base_currency', 'target_currency', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};
