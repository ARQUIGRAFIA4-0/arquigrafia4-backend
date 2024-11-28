<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACCulturalContext extends Model
{
    use HasUuids;

    protected $connection = 'vrac_cultural_contexts';

    protected $fillable = [
        'id',
        'context',
        'variant_contexts',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'context' => 'string',
            'variant_contexts' => 'array',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'cultural_context_image', 'cultural_context_id', 'image_id');
    }
}
