<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CollectiveInvite extends Model
{
    use HasUuids;

    protected $fillable = [
        'collective_id',
        'created_by',
        'token',
        'is_single_use',
        'uses',
        'max_uses',
        'revoked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'collective_id' => 'string',
            'created_by' => 'string',
            'is_single_use' => 'boolean',
            'uses' => 'integer',
            'max_uses' => 'integer',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // relationships

    public function collective(): BelongsTo
    {
        return $this->belongsTo(Collective::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // helpers

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->is_single_use && $this->uses >= 1) {
            return false;
        }

        if (!$this->is_single_use && $this->max_uses !== null && $this->uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function redeem(): void
    {
        DB::transaction(function () {
            $this->lockForUpdate();
            $this->increment('uses');
        });
    }
}
