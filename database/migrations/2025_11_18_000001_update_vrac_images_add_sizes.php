<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            // remove old path columns if they exist, then add sizes JSON column
            if (Schema::hasColumn('vrac_images', 'thumb_path')) {
                $table->dropColumn('thumb_path');
            }
            if (Schema::hasColumn('vrac_images', 'medium_path')) {
                $table->dropColumn('medium_path');
            }

            // json column to store sizes: original, mid, thumb -> {width,height}
            if (! Schema::hasColumn('vrac_images', 'sizes')) {
                $table->json('sizes')->nullable()->after('source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            if (Schema::hasColumn('vrac_images', 'sizes')) {
                $table->dropColumn('sizes');
            }

            if (! Schema::hasColumn('vrac_images', 'thumb_path')) {
                $table->string('thumb_path')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('vrac_images', 'medium_path')) {
                $table->string('medium_path')->nullable()->after('thumb_path');
            }
        });
    }
};
