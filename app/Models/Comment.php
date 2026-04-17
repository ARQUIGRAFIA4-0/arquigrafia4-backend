<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Comment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'image_id',
        'parent_id',
        'content',
        'is_deleted',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'id'        => 'string',
            'user_id'   => 'string',
            'image_id'  => 'string',
            'parent_id' => 'string',
            'is_deleted' => 'boolean',
            'edited_at'  => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    // Comentário pai (self-referential)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Respostas diretas (1 nível)
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_likes');
    }

    // Carrega replies recursivamente (cuidado com profundidade muito grande)
    // public function repliesRecursive(): HasMany
    // {
    //     return $this->hasMany(Comment::class, 'parent_id')
    //         ->with('repliesRecursive.user'); // recursão
    // }
}
