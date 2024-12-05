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
        Schema::create('vrac_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('text');
            $table->string('type')->default('other');
            $table->string('href');
            $table->string('rights_holder');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrac_rights');
    }
};
