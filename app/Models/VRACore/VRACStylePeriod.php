<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACStylePeriod extends Model
{
    use HasUuids;

    protected $table = 'vrac_style_periods';

    protected $fillable = [
        'id',
        'label',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_style_period', 'style_period_id', 'image_id');
    }
}
