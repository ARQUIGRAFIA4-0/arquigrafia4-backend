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
        Schema::table('profiles', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('scholarity');
            $table->string('race', 20)->nullable()->after('bio');
            $table->string('profession', 50)->nullable()->after('race');
            $table->string('address')->nullable()->after('profession');

            $table->dropColumn([
                'phone',
                'website',
                'country',
                'state',
                'city',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('country', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('city', 50)->nullable();

            $table->dropColumn([
                'bio',
                'race',
                'profession',
                'address',
            ]);
        });
    }
};
