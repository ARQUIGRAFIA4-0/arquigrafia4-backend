<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Percurso extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'album_id',
        'position',
        'title',
        'is_street',
        'distance_meters',
        'duration_seconds',
        'route_coordinates',
    ];

    protected function casts(): array
    {
        return [
            'id'                => 'string',
            'album_id'          => 'string',
            'position'          => 'integer',
            'title'             => 'string',
            'is_street'         => 'boolean',
            'distance_meters'   => 'float',
            'duration_seconds'  => 'float',
            'route_coordinates' => 'array',
            'deleted_at'        => 'datetime',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(PercursoStop::class)->orderBy('order');
    }
}
