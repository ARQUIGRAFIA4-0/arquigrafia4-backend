<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collective_subject', function (Blueprint $table) {
            $table->uuid('collective_id')->index();
            $table->uuid('subject_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collective_subject');
    }
};
