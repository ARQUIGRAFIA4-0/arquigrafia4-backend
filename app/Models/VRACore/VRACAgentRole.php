<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VRACAgentRole extends Model
{
    use HasUuids;

    protected $connection = 'vrac_agent_roles';

    protected $fillable = [
        'id',
        'role',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'role' => 'string',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships

    public function agents(): HasMany
    {
        return $this->hasMany(VRACAgent::class);
    }

}
