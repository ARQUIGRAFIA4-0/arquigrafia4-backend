<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACStateEdition extends Model
{
    use HasUuids;

    protected $table = 'vrac_state_editions';

    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
        'num',
        'count',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'description' => 'string',
            'type' => 'string',
            'num' => 'integer',
            'count' => 'integer',
            'source' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_state_edition', 'state_edition_id', 'image_id');
    }
}
