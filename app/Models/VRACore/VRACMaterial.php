<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACMaterial extends Model
{
    use HasUuids;

    protected $table = 'vrac_materials';

    protected $fillable = [
        'id',
        'label',
        'type',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'type' => 'array',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_material', 'material_id', 'image_id');
    }
}
