<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_style_period', function (Blueprint $table) {
            $table->uuid('work_id')->index();
            $table->uuid('style_period_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_style_period');
    }
};
