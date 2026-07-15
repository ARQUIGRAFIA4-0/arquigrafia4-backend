<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->string('dominant_color', 7)->nullable()->after('sizes');
        });
    }

    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->dropColumn('dominant_color');
        });
    }
};
