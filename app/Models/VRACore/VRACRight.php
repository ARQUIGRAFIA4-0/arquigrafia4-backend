<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACRight extends Model
{
    use HasUuids;

    protected $connection = 'vrac_rights';

    protected $fillable = [
        'id',
        'text',
        'type',
        'href',
        'rights_holder',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'text' => 'string',
            'type' => 'string',
            'href' => 'string',
            'rights_holder' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_right', 'right_id', 'image_id');
    }
}
