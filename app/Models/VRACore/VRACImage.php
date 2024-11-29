<?php

namespace App\Models\VRACore;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VRACImage extends Model
{
    use SoftDeletes, HasUuids;

    protected $connection = 'vrac_images';

    protected $fillable = [
        'id',
        'ref_id',
        'source',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'ref_id' => 'string',
            'source' => 'string',
            'deleted_at' => 'timestamp',
        ];
    }

    // relationships

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(VRACAgent::class);
    }

    public function culturalContexts(): BelongsToMany
    {
        return $this->belongsToMany(VRACCulturalContext::class, 'cultural_context_image', 'image_id', 'cultural_context_id');
    }

    public function dates(): BelongsToMany
    {
        return $this->belongsToMany(VRACDate::class, 'date_image', 'image_id', 'date_id');
    }

    public function descriptions(): BelongsToMany
    {
        return $this->belongsToMany(VRACDescription::class, 'description_image', 'image_id', 'description_id');
    }

    // para o nosso caso, title será no singular pois a image sempre só terá 1 title, e vice-versa
    public function title(): BelongsToMany
    {
        return $this->belongsToMany(VRACTitle::class, 'image_title', 'image_id', 'title_id')->take(1);
    }

    public function techniques(): BelongsToMany
    {
        return $this->belongsToMany(VRACTechnique::class, 'image_technique', 'image_id', 'technique_id');
    }

    public function workTypes(): BelongsToMany
    {
        return $this->belongsToMany(VRACWorkType::class, 'image_work_type', 'image_id', 'work_type_id');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(VRACMaterial::class, 'image_material', 'image_id', 'material_id');
    }
}
