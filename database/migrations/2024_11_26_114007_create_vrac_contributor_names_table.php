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
        Schema::create('vrac_contributor_names', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->json('variant_names')->nullable();
            $table->string('type');
            $table->string('vocab')->nullable();
            $table->string('ref_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_contributor_names');
    }
};
