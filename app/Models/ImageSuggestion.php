<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\VRACore\VRACImage;
use App\Models\User;

class ImageSuggestion extends Model
{
    use SoftDeletes;

    protected $table = 'image_suggestions';

    protected $keyType = 'string';
    public $incrementing = false;

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

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function image()
    {
        return $this->belongsTo(VRACImage::class, 'image_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}