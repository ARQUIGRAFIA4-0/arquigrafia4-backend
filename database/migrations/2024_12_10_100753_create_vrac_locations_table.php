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
        Schema::create('vrac_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('location_name_id')->index();
            $table->string('type')->nullable()->default('repository');
            $table->string('ref_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_locations');
    }
};
