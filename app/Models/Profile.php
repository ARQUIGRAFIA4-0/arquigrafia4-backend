<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'gender',
        'birthdate',
        'scholarity',
        'socials',
        'configurations',
        'bio',
        'race',
        'profession',
        'address',
    ];

    /**
     * Get the attributes that should be cast.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'gender' => 'string',
            'birthdate' => 'date',
            'scholarity' => 'string',
            'socials' => 'array',
            'configurations' => 'array',
            'bio' => 'string',
            'race' => 'string',
            'profession' => 'string',
            'address' => 'string',
        ];
    }

    // relationships
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
