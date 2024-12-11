<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VRACAgent extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'vrac_agents';

    protected $fillable = [
        'id',
        'culture_id',
        'attribution',
        'contributor_name_id',
        'role_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'culture_id' => 'string',
            'attribution' => 'string', // size 100
            'contributor_name_id' => 'string',
            'role_id' => 'string',
            'deleted_at' => 'date',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(VRACAgentDate::class);
    }

    public function contributorName(): BelongsTo
    {
        return $this->belongsTo(VRACContributorName::class, 'contributor_name_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(VRACAgentRole::class, 'role_id', 'id');
    }

    public function culture(): BelongsTo
    {
        return $this->belongsTo(VRACCulturalContext::class, 'culture_id', 'id');
    }
}
