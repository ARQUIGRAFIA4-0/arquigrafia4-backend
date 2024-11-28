<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACDate extends Model
{
    use HasUuids;
    
    protected $connection = 'vrac_dates';

    protected $fillable = [
        'id',
        'type',
        'earliest_date',
        'circa_earliest_date',
        'latest_date',
        'circa_latest_date',
        'source',
        'href',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => 'string',
            'earliest_date' => 'date',
            'circa_earliest_date' => 'boolean',
            'latest_date' => 'date',
            'circa_latest_date' => 'boolean',
            'source' => 'string',
            'href' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'date_image', 'date_id', 'image_id');
    }
}
