<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Binomial extends Model
{
    protected $fillable = [
        'word_left',
        'word_right',
        'order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'order'  => 'integer',
            'active' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function evaluations(): HasMany
    {
        return $this->hasMany(BinomialEvaluation::class);
    }
}
