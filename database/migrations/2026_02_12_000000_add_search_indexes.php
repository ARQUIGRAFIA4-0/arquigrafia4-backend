<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vrac_titles', function (Blueprint $table) {
            $table->index('label');
        });

        Schema::table('vrac_subjects', function (Blueprint $table) {
            $table->index('term');
        });

        Schema::table('vrac_dates', function (Blueprint $table) {
            $table->index('earliest_date');
            $table->index('latest_date');
        });

        Schema::table('agent_image', function (Blueprint $table) {
            $table->index('agent_id');
            $table->index('image_id');
        });
    }

    public function down(): void
    {
        Schema::table('vrac_titles', function (Blueprint $table) {
            $table->dropIndex(['label']);
        });

        Schema::table('vrac_subjects', function (Blueprint $table) {
            $table->dropIndex(['term']);
        });

        Schema::table('vrac_dates', function (Blueprint $table) {
            $table->dropIndex(['earliest_date']);
            $table->dropIndex(['latest_date']);
        });

        Schema::table('agent_image', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['image_id']);
        });
    }
};
