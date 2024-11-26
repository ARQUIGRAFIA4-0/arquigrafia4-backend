<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VRACContributorName extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'vrac_contributor_names';

    public $typeEnum = ['personal', 'corporate', 'family', 'other'];

    protected $fillable = [
        'id',
        'name',
        'variant_names',
        'type',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'variant_names' => 'json',
            'type' => 'string',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

}
