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

/*
CC BY livre desde q creditem original https://creativecommons.org/licenses/by/4.0 Atribuição
CC BY-SA livre desde q creditem original e mudanças sigam mesma licença https://creativecommons.org/licenses/by-sa/4.0 Atribuição-CompartilhaIgual
CC BY-ND distribuível desde q creditem original e sem alterações https://creativecommons.org/licenses/by-nd/4.0 Atribuição-SemDerivações
CC BY-NC NÃO comercial e editável, NÃO precisa seguir mesma licença https://creativecommons.org/licenses/by-nc/4.0 Atribuição-NãoComercial
CC BY-NC-SA NÃO comercial e editável, precisa seguir mesma licença https://creativecommons.org/licenses/by-nc-sa/4.0 Atribuição-NãoComercial-CompartilhaIgual
CC BY-NC-ND NÃO comercial e  NÃO editável https://creativecommons.org/licenses/by-nc-nd/4.0 Atribuição-SemDerivações-SemDerivados
*/
}
