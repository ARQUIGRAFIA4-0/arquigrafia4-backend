<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('image_suggestions')) {
            return;
        }

        Schema::create('image_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('image_id');
            $table->uuid('user_id');

            $table->string('status')->default('pending');

            $table->json('payload');

            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_suggestions');
    }
};