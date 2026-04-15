<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('collective_invites');
    }

    public function down(): void
    {
        // Intentionally empty — restoring the invites table is not supported.
        // The old migration 2026_03_16_000003 handles fresh installs.
    }
};
