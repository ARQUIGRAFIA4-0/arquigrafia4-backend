<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountVerificationToken extends Model
{
    protected $table = 'account_verification_tokens';

    protected $primaryKey = 'email';

    protected $fillable = [
        'email',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'string',
            'token' => 'string',
        ];
    }
}
