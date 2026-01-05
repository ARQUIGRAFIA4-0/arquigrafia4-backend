<?php

namespace Database\Seeders;

use App\Imports\TechniquesImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class TechniqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new TechniquesImport, storage_path('seeds/techniques_from_vcaa.csv'));
    }
}
