<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collective_join_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collective_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['collective_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collective_join_requests');
    }
};
