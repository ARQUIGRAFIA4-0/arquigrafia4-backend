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
    Schema::create('album_image', function (Blueprint $table) {
        $table->uuid('album_id');
        $table->uuid('image_id');

        $table->integer('position')->default(0);

        $table->timestamps();

        // clave primaria compuesta (evita duplicados)
        $table->primary(['album_id', 'image_id']);

        // relaciones
        $table->foreign('album_id')
              ->references('id')
              ->on('albums')
              ->cascadeOnDelete();

        $table->foreign('image_id')
              ->references('id')
              ->on('vrac_images')
              ->cascadeOnDelete();

        // índices (rendimiento)
        $table->index('album_id');
        $table->index('image_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('album_image');
    }
};
