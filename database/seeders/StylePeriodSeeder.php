<?php

namespace Database\Seeders;

use App\Imports\StylePeriodsImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class StylePeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new StylePeriodsImport, storage_path('seeds/styleperiods_from_vcaa.csv'));
    }
}
