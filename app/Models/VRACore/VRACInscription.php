<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACInscription extends Model
{
    use HasUuids;

    protected $table = 'vrac_inscriptions';

    protected $fillable = [
        'id',
        'label',
        'type',
        'position',
        'author',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'type' => 'string',
            'position' => 'string',
            'author' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_inscription', 'inscription_id', 'image_id');
    }
}
