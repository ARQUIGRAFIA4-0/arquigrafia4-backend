<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('percurso_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('percurso_id')
                  ->constrained('percursos')
                  ->cascadeOnDelete();

            $table->string('type', 20);
            $table->string('title')->nullable();
            $table->integer('order');

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            $table->foreignUuid('image_id')
                  ->nullable()
                  ->constrained('vrac_images')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percurso_stops');
    }
};
