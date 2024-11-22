<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

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

    /**
     * The roles that belong to the user.
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class);
    }
}
