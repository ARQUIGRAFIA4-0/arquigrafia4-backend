<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_suggestions')) {
            return;
        }

        Schema::create('work_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('work_id');
            $table->uuid('user_id');

            $table->string('status')->default('pending');

            $table->json('payload');

            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('work_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_suggestions');
    }
};
