<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vrac_titles', fn (Blueprint $t) => $t->fullText('label'));
        Schema::table('vrac_contributor_names', fn (Blueprint $t) => $t->fullText('name'));
        Schema::table('vrac_subjects', fn (Blueprint $t) => $t->fullText('term'));
        Schema::table('vrac_descriptions', fn (Blueprint $t) => $t->fullText('text'));
    }

    public function down(): void
    {
        Schema::table('vrac_titles', fn (Blueprint $t) => $t->dropFullText(['label']));
        Schema::table('vrac_contributor_names', fn (Blueprint $t) => $t->dropFullText(['name']));
        Schema::table('vrac_subjects', fn (Blueprint $t) => $t->dropFullText(['term']));
        Schema::table('vrac_descriptions', fn (Blueprint $t) => $t->dropFullText(['text']));
    }
};
