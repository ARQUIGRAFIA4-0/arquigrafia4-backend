<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\VRACore\VRACImage;
use App\Models\User;

class Album extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'title' => 'string',
            'description' => 'string',
            'is_private' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): BelongsToMany
{
    return $this->belongsToMany(
        VRACImage::class,
        'album_image',
        'album_id',
        'image_id'
    )->using(AlbumImage::class) // 🔥 AQUÍ está la magia
     ->withPivot('position')
     ->withTimestamps();
}
}