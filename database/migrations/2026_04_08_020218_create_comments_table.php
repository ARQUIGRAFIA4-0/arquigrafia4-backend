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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('image_id')
                ->constrained('vrac_images')
                ->cascadeOnDelete();

            // Self-referential: nullable pois comentários raiz não têm pai
            $table->foreignUuid('parent_id')
                ->nullable()
                ->constrained('comments')  // aponta para a própria tabela
                ->nullOnDelete();          // se pai deletado, filho vira raiz

            $table->text('content');

            $table->timestamps();       // created_at + updated_at
            $table->softDeletes();      // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
