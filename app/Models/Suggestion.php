<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base class shared by ImageSuggestion and WorkSuggestion.
 *
 * Both carry the same suggestion lifecycle: a JSON payload of proposed
 * changes that starts as "pending" and is later reviewed (accepted,
 * partially accepted or rejected) by an authorized user.
 */
abstract class Suggestion extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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
