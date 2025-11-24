<?php

namespace Database\Seeders;

use App\Imports\SubjectsImport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new SubjectsImport, storage_path('seeds/tags_to_subject.csv'));
    }
}
