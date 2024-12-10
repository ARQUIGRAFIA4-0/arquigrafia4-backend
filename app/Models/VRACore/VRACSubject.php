<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACSubject extends Model
{
    use HasUuids;

    protected $table = 'vrac_subjects';

    protected $fillable = [
        'id',
        'term',
        'type',
        'vocab',
        'ref_id',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'term' => 'string',
            'type' => 'string',
            'vocab' => 'string',
            'ref_id' => 'string',
            'source' => 'string',
        ];
    }

    // relationships
    
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_subject', 'subject_id', 'image_id');
    }
}
