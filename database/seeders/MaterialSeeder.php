<?php

namespace Database\Seeders;

use App\Imports\MaterialsImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new MaterialsImport, storage_path('seeds/materials_from_vcaa.csv'));
    }
}
