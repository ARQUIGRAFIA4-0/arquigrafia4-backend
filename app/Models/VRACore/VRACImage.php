<?php

namespace App\Models\VRACore;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VRACImage extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'vrac_images';

    protected $fillable = [
        'id',
        'user_id',
        'sizes',
        'collective_id',
        'legacy_id',
        'ref_id',
        'source',
        'processed_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'sizes' => 'array',
            'collective_id' => 'string',
            'legacy_id' => 'integer',
            'ref_id' => 'string',
            'source' => 'string',
            'processed_at' => 'timestamp',
            'deleted_at' => 'timestamp',
        ];
    }

    // methods
    protected string $baseDir = 'images/iiif';
    public function path(string $type = 'base', string $mode = 'relative', array $options = []): ?string
    {
        // Build the relative path first
        switch ($type) {
            case 'base':
                $relative = "{$this->baseDir}/{$this->id}";
                break;

            case 'original':
                $relative = "{$this->baseDir}/{$this->id}/full/max/0/default.jpg";
                break;

            case 'thumb':
                $width = $options['width'] ?? $this->sizes["thumb"]["width"];
                $height = $options['height'] ?? $this->sizes["thumb"]["height"];
                $relative = "{$this->baseDir}/{$this->id}/full/{$width},{$height}/0/default.jpg";
                break;

            case 'mid': 
                $width = $options['width'] ?? $this->sizes["mid"]["width"];
                $height = $options['height'] ?? $this->sizes["mid"]["height"];
                $relative = "{$this->baseDir}/{$this->id}/full/{$width},{$height}/0/default.jpg";
                break;

            case 'info':
                $relative = "{$this->baseDir}/{$this->id}/info.json";
                break;

            default:
                return null;
        }

        // Transform depending on mode
        return match ($mode) {
            'relative' => $relative,
            'absolute' => storage_path("app/public/{$relative}"),
            'url' => asset(str_replace('images/', '', $relative)), // iiif/... instead of images/iiif
            default => $relative,
        };
    }

    // List of all relations
    public const RELATIONS = [
        'agents',
        'culturalContexts',
        'dates',
        'descriptions',
        'titles',
        'techniques',
        'workTypes',
        'materials',
        'stylePeriods',
        'measurements',
        'stateEditions',
        'sources',
        'rights',
        'inscriptions',
        'subjects',
        'locations'
    ];

    // relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(VRACAgent::class, 'agent_image', 'image_id', 'agent_id');
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

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(VRACTitle::class, 'image_title', 'image_id', 'title_id');
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
        return $this->belongsToMany(Location::class, 'image_location', 'image_id', 'location_id');
    }
}
