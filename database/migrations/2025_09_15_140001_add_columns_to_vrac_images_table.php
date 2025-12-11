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
            $table->bigInteger('legacy_id')->nullable()->after('id');
            $table->uuid('collective_id')->nullable()->index()->after('id');
            $table->uuid('user_id')->index()->after('id');
            $table->timestamp('processed_at')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'collective_id',
                'legacy_id',
                'processed_at',
            ]);
        });
    }
};
