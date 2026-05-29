<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinomialEvaluation extends Model
{
    protected $fillable = [
        'image_id',
        'user_id',
        'binomial_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'image_id'    => 'string',
            'user_id'     => 'string',
            'binomial_id' => 'integer',
            'value'       => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function image(): BelongsTo
    {
        return $this->belongsTo(VRACImage::class, 'image_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function binomial(): BelongsTo
    {
        return $this->belongsTo(Binomial::class);
    }
}
