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
        Schema::create('vrac_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index();
            $table->date('earliest_date')->nullable();
            $table->boolean('circa_earliest_date')->default(false);
            $table->date('latest_date')->nullable();
            $table->boolean('circa_latest_date')->default(false);
            $table->string('source')->nullable();
            $table->string('href')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_dates');
    }
};
