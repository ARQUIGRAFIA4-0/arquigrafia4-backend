<?php

namespace App\Models;

use App\Models\VRACore\VRACImage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Location extends Model
{
    use HasUuids
    ;

    protected $fillable = [
        'id',
        'latitude',
        'longitude',
        'altitude',
        'coordinates',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'altitude' => 'decimal:8',
            // 'coordinates',
            'label' => 'string',
        ];
    }

    // relationships
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(VRACImage::class, 'image_location', 'location_id', 'image_id');
    }

    /**
     * Export all locations as a GeoJSON FeatureCollection and save to storage.
     *
     * @param string $disk Storage disk to use (default: 'public')
     * @param string $path Path inside the disk where the file will be written
    * @return string Absolute path to the written GeoJSON file on the filesystem
     */
    public static function exportGeoJson(string $disk = 'public', string $path = 'locations/locations.geojson'): string
    {
        $locations = self::all();

        $features = [];

        foreach ($locations as $loc) {
            // skip if no coordinates
            if ($loc->latitude === null || $loc->longitude === null) {
                continue;
            }

            // Model casts latitude/longitude to decimal; simply cast to float here.
            $lat = (float) $loc->latitude;
            $lng = (float) $loc->longitude;

            $properties = [
                'id' => $loc->id,
                'label' => $loc->label,
            ];

            // include other non-null attributes
            if ($loc->altitude !== null) {
                $properties['altitude'] = $loc->altitude;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$lng, $lat],
                ],
                'properties' => $properties,
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $json = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Storage::disk($disk)->put($path, $json);

        // Return an absolute filesystem path to the saved file. If you need a public URL,
        // call Storage::disk($disk)->url($path) from your controller or consumer where the
        // disk is known to support url().
        return Storage::disk($disk)->path($path);
    }
}
