<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('reportable');
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason', [
                'copyright',
                'inappropriate',
                'spam',
                'harassment',
                'misinformation',
                'other',
            ]);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])
                ->default('pending');
            $table->timestamps();

            $table->unique(['reporter_id', 'reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
