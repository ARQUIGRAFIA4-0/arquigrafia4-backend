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
        Schema::create('vrac_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('culture_id')->nullable();
            $table->string('attribution', 100)->nullable();
            $table->uuid('contributor_name_id')->index()->nullable();
            $table->uuid('role_id')->index()->nullable();
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_agents');
    }
};
