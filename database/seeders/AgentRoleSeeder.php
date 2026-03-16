<?php

namespace Database\Seeders;

use App\Imports\AgentRolesImport;
use App\Models\VRACore\VRACAgentRole;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class AgentRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Excel::import(new AgentRolesImport, storage_path('seeds/agentroles_from_vcaa.csv'));

        // VRACAgentRole::create([
        //     'label' => 'fotógrafo',
        //     'vocab' => 'ARQUIGRAFIA',
        // ]);
        // VRACAgentRole::create([
        //     'label' => 'arquiteto',
        //     'vocab' => 'ARQUIGRAFIA',
        // ]);
        // VRACAgentRole::create([
        //     'label' => 'engenheiro civil',
        //     'vocab' => 'ARQUIGRAFIA',
        // ]);
    }
}
