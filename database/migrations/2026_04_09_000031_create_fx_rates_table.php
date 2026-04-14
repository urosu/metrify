<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FX rates stored in DB — never call FX API at query time.
        // Four-case conversion logic: see PLANNING.md "FX Rate conversion".
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base_currency', 3)->default('EUR');
            $table->char('target_currency', 3);
            $table->decimal('rate', 16, 8);
            $table->date('date');
            $table->timestamp('created_at');

            $table->unique(['base_currency', 'target_currency', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};
