<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PercursoStop extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'percurso_id',
        'type',
        'title',
        'order',
        'latitude',
        'longitude',
        'image_id',
    ];

    protected function casts(): array
    {
        return [
            'id'          => 'string',
            'percurso_id' => 'string',
            'type'        => 'string',
            'title'       => 'string',
            'order'       => 'integer',
            'latitude'    => 'float',
            'longitude'   => 'float',
            'image_id'    => 'string',
        ];
    }

    public function percurso(): BelongsTo
    {
        return $this->belongsTo(Percurso::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(VRACImage::class, 'image_id');
    }
}
