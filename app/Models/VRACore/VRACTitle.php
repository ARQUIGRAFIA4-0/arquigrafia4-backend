<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACTitle extends Model
{
    use HasUuids;
    
    protected $table = 'vrac_titles';

    protected $fillable = [
        'id',
        'label',
        'type',
        'pref',
        'source',
        'lang',
        'href',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'type' => 'string',
            'pref' => 'boolean',
            'source' => 'string',
            'lang' => 'string',
            'href' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_title', 'title_id', 'image_id');
    }
}
