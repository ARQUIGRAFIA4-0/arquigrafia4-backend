<?php

namespace App\Models\VRACore;

use App\Models\Location;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\VRACore\VRACCulturalContext;
use App\Models\VRACore\VRACWorkType;
use App\Models\VRACore\VRACSubject;

class VRACWork extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'vrac_works';

    protected $fillable = [
        'id',
        'location_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'location_id' => 'string',
            'deleted_at' => 'timestamp',
        ];
    }

    public const RELATIONS = [
        'titles',
        'agents',
        'dates',
        'materials',
        'techniques',
        'stylePeriods',
        'culturalContexts',
        'workTypes',
        'subjects',
        'images',
        'location',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_work', 'work_id', 'image_id');
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(VRACTitle::class, 'work_title', 'work_id', 'title_id');
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(VRACAgent::class, 'work_agent', 'work_id', 'agent_id');
    }

    public function dates(): BelongsToMany
    {
        return $this->belongsToMany(VRACDate::class, 'work_date', 'work_id', 'date_id');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(VRACMaterial::class, 'work_material', 'work_id', 'material_id');
    }

    public function techniques(): BelongsToMany
    {
        return $this->belongsToMany(VRACTechnique::class, 'work_technique', 'work_id', 'technique_id');
    }

    public function stylePeriods(): BelongsToMany
    {
        return $this->belongsToMany(VRACStylePeriod::class, 'work_style_period', 'work_id', 'style_period_id');
    }

    public function culturalContexts(): BelongsToMany
    {
        return $this->belongsToMany(VRACCulturalContext::class, 'work_cultural_context', 'work_id', 'cultural_context_id');
    }

    public function workTypes(): BelongsToMany
    {
        return $this->belongsToMany(VRACWorkType::class, 'work_work_type', 'work_id', 'work_type_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(VRACSubject::class, 'work_subject', 'work_id', 'subject_id');
    }
}
