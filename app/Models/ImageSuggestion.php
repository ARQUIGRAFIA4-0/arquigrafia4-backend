<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;

class ImageSuggestion extends Suggestion
{
    protected $table = 'image_suggestions';

    protected $fillable = [
        'id',
        'image_id',
        'user_id',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    public function image()
    {
        return $this->belongsTo(VRACImage::class, 'image_id');
    }
}
