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
        'collective_id',
        'title',
        'description',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'string',
            'user_id'      => 'string',
            'collective_id'=> 'string',
            'title'        => 'string',
            'description'  => 'string',
            'is_private'   => 'boolean',
            'deleted_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collective(): BelongsTo
    {
        return $this->belongsTo(Collective::class);
    }

    public function isOwnedByUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isOwnedByCollective(): bool
    {
        return !is_null($this->collective_id);
    }

    public function userCanManage(User $user): bool
    {
        if ($this->isOwnedByCollective()) {
            return $this->collective->isMember($user);
        }

        return $this->isOwnedByUser($user);
    }

    public function images(): BelongsToMany
{
    return $this->belongsToMany(
        VRACImage::class,
        'album_image',
        'album_id',
        'image_id'
    )->using(AlbumImage::class)
     ->withPivot('position')
     ->withTimestamps();
}
}