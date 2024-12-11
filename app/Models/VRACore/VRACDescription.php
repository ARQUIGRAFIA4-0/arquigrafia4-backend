<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACDescription extends Model
{
    use HasUuids;
    
    protected $table = 'vrac_descriptions';

    protected $fillable = [
        'id',
        'text',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'text' => 'string',
            'source' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'description_image', 'description_id', 'image_id');
    }
}
