<?php

namespace Database\Seeders;

use App\Models\VRACore\VRACAgentRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VRACAgentRole::create([
            'label' => 'fotógrafo',
            'vocab' => 'ARQUIGRAFIA',
        ]);
        VRACAgentRole::create([
            'label' => 'arquiteto',
            'vocab' => 'ARQUIGRAFIA',
        ]);
        VRACAgentRole::create([
            'label' => 'engenheiro civil',
            'vocab' => 'ARQUIGRAFIA',
        ]);
    }
}
