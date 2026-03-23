<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collectives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('email')->nullable();
            $table->date('foundation_date')->nullable();
            $table->string('location')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('description')->nullable();
            $table->json('socials')->nullable();
            $table->bigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collectives');
    }
};
