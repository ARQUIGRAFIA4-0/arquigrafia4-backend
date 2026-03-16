<?php

namespace Database\Seeders;

use App\Imports\WorkTypesImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class WorkTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new WorkTypesImport, storage_path('seeds/worktypes_from_vcaa.csv'));
    }
}
