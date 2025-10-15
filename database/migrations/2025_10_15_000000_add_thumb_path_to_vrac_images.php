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
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('source')->index();
            $table->string('medium_path')->nullable()->after('source')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->dropIndex(['thumb_path']);
            $table->dropColumn('thumb_path');
            $table->dropIndex(['medium_path']);
            $table->dropColumn('medium_path');
        });
    }
};
