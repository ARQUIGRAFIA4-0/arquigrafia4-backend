<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binomial_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('image_id')->constrained('vrac_images')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('binomial_id')->constrained('binomials')->cascadeOnDelete();
            $table->unsignedTinyInteger('value'); // 0 - 100
            $table->timestamps();

            $table->unique(['image_id', 'user_id', 'binomial_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binomial_evaluations');
    }
};
