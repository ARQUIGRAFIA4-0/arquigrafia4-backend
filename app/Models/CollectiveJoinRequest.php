<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CollectiveJoinRequest
 *
 * Represents a user's request to join a collective. This model tracks the status of join
 * requests, allowing admins to approve or reject membership requests.
 *
 * @property string $id UUID primary key
 * @property string $collective_id UUID of the collective
 * @property string $user_id UUID of the user making the request
 * @property string $status Status of the request: 'pending', 'approved', or 'rejected'
 * @property \Carbon\Carbon $created_at Timestamp when the request was created
 * @property \Carbon\Carbon $updated_at Timestamp when the request was last updated
 */
class CollectiveJoinRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'collective_id',
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id'             => 'string',
            'collective_id'  => 'string',
            'user_id'        => 'string',
            'status'         => 'string',
        ];
    }

    /**
     * Get the collective that was requested to join.
     */
    public function collective(): BelongsTo
    {
        return $this->belongsTo(Collective::class);
    }

    /**
     * Get the user who made the join request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
