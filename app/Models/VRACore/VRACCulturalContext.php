<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VRACCulturalContext extends Model
{
    use HasUuids;

    protected $connection = 'vrac_cultural_contexts';

    protected $fillable = [
        'id',
        'label',
        'variant_labels',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'variant_labels' => 'array',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'cultural_context_image', 'cultural_context_id', 'image_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(VRACAgent::class, 'culture_id', 'id');
    }
}
