<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VRACDate extends Model
{
    use HasUuids;

    protected $table = 'vrac_dates';

    protected $fillable = [
        'id',
        'type',
        'earliest_date',
        'circa_earliest_date',
        'latest_date',
        'circa_latest_date',
        'source',
        'href',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => 'string',
            'earliest_date' => 'date',
            'circa_earliest_date' => 'boolean',
            'latest_date' => 'date',
            'circa_latest_date' => 'boolean',
            'source' => 'string',
            'href' => 'string',
        ];
    }

    // relationships

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'date_image', 'date_id', 'image_id');
    }

    public function formattedDateRange(): string
    {
        $earliest = $this->earliest_date ? $this->earliest_date->format('Y') : '';
        $latest = $this->latest_date ? $this->latest_date->format('Y') : '';

        if ($this->circa_earliest_date && $this->circa_latest_date) {
            return "c.$earliest-c.$latest";
        } elseif ($this->circa_earliest_date) {
            return "c.$earliest-$latest";
        } elseif ($this->circa_latest_date) {
            return "$earliest-c.$latest";
        } else {
            return "$earliest-$latest";
        }
    }
}
