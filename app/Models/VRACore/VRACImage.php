<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VRACImage extends Model
{
    use SoftDeletes, HasUuids;

    protected $connection = 'vrac_images';

    protected $fillable = [
        'id',
        'ref_id',
        'source',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'ref_id' => 'string',
            'source' => 'string',
            'deleted_at' => 'timestamp',
        ];
    }

    // relationships

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(VRACAgent::class);
    }
}
