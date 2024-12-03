<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACMeasurement extends Model
{
    use HasUuids;

    protected $connection = 'vrac_measurements';

    protected $fillable = [
        'id',
        'value',
        'type',
        'unit',
        'extent',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'value' => 'integer',
            'type' => 'string',
            'unit' => 'string',
            'extent' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_measurement', 'measurement_id', 'image_id');
    }
}
