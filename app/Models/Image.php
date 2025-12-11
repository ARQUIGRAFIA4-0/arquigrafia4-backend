<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasUuids,
        SoftDeletes
    ;

    protected $fillable = [
        'id',
        'file_path',
        'user_id',
        'collective_id',
        'draft',
        'legacy_id',
        'ref_id',
        'source',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'file_path' => 'string',
            'user_id' => 'string',
            'collective_id' => 'string',
            'draft' => 'boolean',
            'legacy_id' => 'integer',
            'ref_id' => 'string',
            'source' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    // relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
