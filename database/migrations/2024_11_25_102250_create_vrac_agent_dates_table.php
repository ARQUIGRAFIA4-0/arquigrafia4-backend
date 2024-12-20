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
        Schema::create('vrac_agent_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id')->index();
            $table->string('type');
            $table->time('earliest_date')->nullable();
            $table->time('latest_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_agent_dates');
    }
};
