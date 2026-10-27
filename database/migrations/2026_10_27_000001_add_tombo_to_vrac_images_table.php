<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            // Número de tombo (inventário) vindo do legado. Não é único: várias fotos do mesmo objeto o compartilham.
            $table->string('tombo', 64)->nullable()->after('ref_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vrac_images', function (Blueprint $table) {
            $table->dropIndex(['tombo']);
            $table->dropColumn('tombo');
        });
    }
};
