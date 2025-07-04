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
        'phone',
        'scholarity',
        'website',
        'socials',
        'configurations',
        'country',
        'state',
        'city',
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
            'phone' => 'string',
            'scholarity' => 'string',
            'website' => 'string',
            'socials' => 'array',
            'configurations' => 'array',
            'country' => 'string',
            'state' => 'string',
            'city' => 'string',
        ];
    }

    // relationships
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
