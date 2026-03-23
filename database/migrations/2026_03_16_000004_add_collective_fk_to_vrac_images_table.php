<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->foreign('collective_id')
                  ->references('id')->on('collectives')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->dropForeign(['collective_id']);
        });
    }
};
