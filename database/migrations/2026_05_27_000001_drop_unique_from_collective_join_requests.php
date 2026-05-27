<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collective_join_requests', function (Blueprint $table) {
            $table->dropForeign(['collective_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['collective_id', 'user_id']);
            $table->foreign('collective_id')->references('id')->on('collectives')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('collective_join_requests', function (Blueprint $table) {
            $table->dropForeign(['collective_id']);
            $table->dropForeign(['user_id']);
            $table->unique(['collective_id', 'user_id']);
            $table->foreign('collective_id')->references('id')->on('collectives')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
