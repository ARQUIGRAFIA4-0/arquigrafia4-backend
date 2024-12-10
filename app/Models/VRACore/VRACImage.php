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

    public function stylePeriods(): BelongsToMany
    {
        return $this->belongsToMany(VRACStylePeriod::class, 'image_style_period', 'image_id', 'style_period_id');
    }

    public function measurements(): BelongsToMany
    {
        return $this->belongsToMany(VRACMeasurement::class, 'image_measurement', 'image_id', 'measurement_id');
    }

    public function stateEditions(): BelongsToMany
    {
        return $this->belongsToMany(VRACStateEdition::class, 'image_state_edition', 'image_id', 'state_edition_id');
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(VRACSource::class, 'image_source', 'image_id', 'source_id');
    }

    public function rights(): BelongsToMany
    {
        return $this->belongsToMany(VRACRight::class, 'image_right', 'image_id', 'right_id');
    }

    public function inscriptions(): BelongsToMany
    {
        return $this->belongsToMany(VRACInscription::class, 'image_inscription', 'image_id', 'inscription_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(VRACSubject::class, 'image_subject', 'image_id', 'subject_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(VRACLocation::class, 'location_image', 'image_id', 'location_id');
    }
}
