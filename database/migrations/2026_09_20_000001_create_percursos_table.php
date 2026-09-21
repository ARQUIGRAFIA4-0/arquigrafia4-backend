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
        Schema::create('percursos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('album_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->integer('position')->default(0);
            $table->string('title')->nullable();

            $table->boolean('is_street')->default(false);
            $table->decimal('distance_meters', 10, 1)->nullable();
            $table->decimal('duration_seconds', 10, 1)->nullable();
            $table->json('route_coordinates')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percursos');
    }
};
