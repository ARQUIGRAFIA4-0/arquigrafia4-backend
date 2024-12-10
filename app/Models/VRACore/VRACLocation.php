<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACLocation extends Model
{
    use HasUuids;

    protected $connection = 'vrac_locations';

    protected $fillable = [
        'id',
        'location_name_id',
        'type',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'location_name_id' => 'string',
            'type' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships
    
    public function name(): BelongsTo
    {
        return $this->belongsTo(VRACLocationName::class, 'id', 'location_name_id');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'location_image', 'location_id', 'image_id');
    }
}
