<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binomials', function (Blueprint $table) {
            $table->id();
            $table->string('word_left');
            $table->string('word_right');
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('binomials')->insert([
            ['word_left' => 'Aberta',      'word_right' => 'Fechada',      'order' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word_left' => 'Interna',     'word_right' => 'Externa',      'order' => 2, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word_left' => 'Complexa',    'word_right' => 'Simples',      'order' => 3, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word_left' => 'Simétrica',   'word_right' => 'Assimétrica',  'order' => 4, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word_left' => 'Translúcida', 'word_right' => 'Opaca',        'order' => 5, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['word_left' => 'Horizontal',  'word_right' => 'Vertical',     'order' => 6, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('binomials');
    }
};
