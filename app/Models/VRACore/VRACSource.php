<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACSource extends Model
{
    use HasUuids;

    protected $connection = 'vrac_sources';

    protected $fillable = [
        'id',
        'name',
        'name_type',
        'ref_id',
        'ref_type',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'name_type' => 'string',
            'ref_id' => 'string',
            'ref_type' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_source', 'source_id', 'image_id');
    }
}
