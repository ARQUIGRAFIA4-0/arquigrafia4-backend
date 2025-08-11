<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    protected $primaryKey = 'email';

    public $timestamps = false;
    
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'string',
            'token' => 'string',
            'created_at' => 'datetime',
        ];
    }
}
