<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasUuids
    ;

    protected $fillable = [
        'id',
        'latitude',
        'longitude',
        'altitude',
        'coordinates',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'altitude' => 'decimal:8',
            // 'coordinates',
            'label' => 'string',
        ];
    }

    // relationships
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_location', 'location_id', 'image_id');
    }
}
