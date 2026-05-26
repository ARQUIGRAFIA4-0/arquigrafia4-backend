<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_material', function (Blueprint $table) {
            $table->uuid('work_id')->index();
            $table->uuid('material_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_material');
    }
};
