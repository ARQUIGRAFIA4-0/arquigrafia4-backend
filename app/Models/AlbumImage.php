<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AlbumImage extends Pivot
{
    protected $table = 'album_image';

    protected $fillable = [
        'album_id',
        'image_id',
        'position'
    ];

    protected $casts = [
        'position' => 'integer',
    ];
}