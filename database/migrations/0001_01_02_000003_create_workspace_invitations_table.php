<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates workspace_invitations table for pending email invites with token.
 *
 * @see docs/planning/schema.md §1.1 workspace_invitations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('role', 16);
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('workspace_id');
        });

        DB::statement("ALTER TABLE workspace_invitations ADD CONSTRAINT check_workspace_invitations_role CHECK (role IN ('admin','member'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
