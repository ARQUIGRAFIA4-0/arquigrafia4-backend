<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VRACLocationName extends Model
{
    use HasUuids;

    protected $table = 'vrac_location_names';

    protected $fillable = [
        'id',
        'label',
        'type',
        'vocab',
        'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'label' => 'string',
            'type' => 'string',
            'vocab' => 'string',
            'ref_id' => 'string',
        ];
    }

    // relationships

    public function locations(): HasMany
    {
        return $this->hasMany(VRACLocation::class, 'location_name_id', 'id');
    }
}
