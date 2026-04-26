<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACSubject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collective extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'email',
        'foundation_date',
        'location',
        'avatar_path',
        'description',
        'socials',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'foundation_date' => 'date',
            'location' => 'string',
            'avatar_path' => 'string',
            'description' => 'string',
            'socials' => 'array',
            'legacy_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Collective $collective) {
            if ($collective->avatar_path) {
                Storage::disk('public')->delete($collective->avatar_path);
            }
        });
    }

    // relationships

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->wherePivot('role', 'admin');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VRACImage::class);
    }

    /**
     * Get all join requests for this collective.
     * This includes pending, approved, and rejected requests.
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(CollectiveJoinRequest::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(VRACSubject::class, 'collective_subject', 'collective_id', 'subject_id');
    }

    // helpers

    public function isAdmin(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}
