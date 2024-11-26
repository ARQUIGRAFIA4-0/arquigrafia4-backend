<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDate extends Model
{
    use HasUuids;

    protected $connection = 'vrac_agent_dates';

    public $typeEnum = ['life', 'activity', 'other'];

    protected $fillable = [
        'id',
        'agent_id',
        'type',
        'earliest_date',
        'latest_date',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'agent_id' => 'string',
            'type' => 'string',
            'earliest_date' => 'date',
            'latest_date' => 'date',
        ];
    }

    // relationships

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
