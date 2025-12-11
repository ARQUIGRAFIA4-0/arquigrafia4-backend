<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class VRACRight extends Model
{
    use HasUuids;

    protected $table = 'vrac_rights';

    protected $fillable = [
        'id',
        'text',
        'type',
        'href',
        'rights_holder',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'text' => 'string',
            'type' => 'string',
            'href' => 'string',
            'rights_holder' => 'string',
        ];
    }

    // relationships

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_right', 'right_id', 'image_id');
    }

    // static methods

    // outdated
    public static function createWithConditions($ownerName, string $commercial = 'no', string $editable = 'no')
    {
        $rightString = 'by';
        if ($commercial == 'no') {
            $rightString .= '-nc';
        }
        if ($editable == 'no') {
            $rightString .= '-nd';
        }

        $right = VRACRight::create([
            'text' => 'CC ' . Str::upper($rightString),
            'type' => 'copyrighted',
            'href' => 'https://creativecommons.org/licenses/' . $rightString . '/4.0',
            'rights_holder' => $ownerName,
        ]);

        return $right;
    }

}
