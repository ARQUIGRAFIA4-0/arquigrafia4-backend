<?php

namespace Database\Seeders;

use App\Imports\LegacyUsersImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class LegacyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new LegacyUsersImport, storage_path('seeds/legacy_users.xlsx'));
    }
}
