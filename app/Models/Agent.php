<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'culture',
        'attribution',
        'contributor_name_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'culture' => 'string', // size 100
            'attribution' => 'string', // size 100
            'contributor_name_id' => 'string',
            'deleted_at' => 'timestamp',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class);
    }
}
