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
        Schema::create('vrac_measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('value');
            $table->string('type');
            $table->string('unit');
            $table->string('extent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_measurements');
    }
};
