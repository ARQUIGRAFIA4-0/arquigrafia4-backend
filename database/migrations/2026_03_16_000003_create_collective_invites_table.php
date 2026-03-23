<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collective_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collective_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->string('token', 64)->unique();
            $table->boolean('is_single_use')->default(true);
            $table->integer('uses')->default(0);
            $table->integer('max_uses')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collective_invites');
    }
};
