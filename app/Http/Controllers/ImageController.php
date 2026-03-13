<?php

namespace App\Http\Controllers;

use App\Http\Requests\Image\SearchImageRequest;
use App\Http\Requests\Image\StoreImageRequest;
use App\Http\Requests\Image\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Services\ImageSearchService;
use Illuminate\Support\Facades\Storage;
use App\Jobs\TileImage;
use App\Models\Location;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACTitle;
use Illuminate\Http\Request;
use Jcupitt\Vips\Image as VipsImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    private int $pageSize = 50;

    public function index(SearchImageRequest $request, ImageSearchService $searchService)
    {
        $query = VRACImage::query();

        $searchService->apply($query, $request->validated());

        $images = $query->with([
            'subjects:id,term',
            'dates:id,type,earliest_date,latest_date,circa_earliest_date,circa_latest_date',
            'titles:id,label,type',
        ])->inRandomOrder()->paginate($request->integer('per_page', $this->pageSize));

        return ImageResource::collection($images);
    }

    public function store(StoreImageRequest $request)
    {
        $image = new VRACImage();
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->save();

        // load original into vips from uploaded buffer and capture original dimensions
        $uploaded = VipsImage::newFromBuffer($request->file('image')->getContent(), '', ['access' => 'sequential']);
        $origWidth = $uploaded->width ?? null;
        $origHeight = $uploaded->height ?? null;
        $converted = $uploaded->writeToBuffer('.jpg');
        Storage::disk('public')->put($image->path('original'), $converted);

        $title = new VRACTitle();
        $title->label = $request->input('title');   
        $title->type = 'other';
        $title->save();
        $image->titles()->sync($title->id);

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $agentPhotographer->load('contributorName');
        $image->agents()->sync($agentPhotographer->id);

        $right = new VRACRight();
        $right->text = Str::upper($request->input('license'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/' . Str::lower($request->input('license')) . '/4.0';
        $right->rights_holder = $agentPhotographer->contributorName->name;
        $right->save();
        $image->rights()->sync($right->id);

        $image->subjects()->sync($request->input('subjects'));

        if ($request->filled('description')) {
            $description = new VRACDescription();
            $description->text = $request->input('description');
            $description->save();
            $image->descriptions()->sync($description->id);
        }

        if ($request->filled('latitude') || $request->filled('longitude')) {
            $location = new Location();
            $location->latitude = $request->input('latitude');
            $location->longitude = $request->input('longitude');
            $location->label = $request->input('location_label');
            // $location->coordinates = '-23.580948, -46.637004';
            $location->save();
            $image->locations()->sync($location->id);
        }

        if ($request->filled('earliest_date')) {
            $date = new VRACDate();
            $date->type = 'creation';
            $date->earliest_date = $request->input('earliest_date');
            $date->circa_earliest_date = $request->input('circa');
            $date->latest_date = $request->input('latest_date');
            $date->circa_latest_date = $request->input('circa');
            $date->save();
            $image->dates()->sync($date->id);
        }

        // Create derivatives and capture their sizes
        $thumbInfo = $this->createDerivative($image, 300);
        $midInfo = $this->createDerivative($image, 1024);
        // Store sizes as JSON structure: original, mid, thumb
        $image->sizes = [
            'original' => [
                'width' => $origWidth,
                'height' => $origHeight,
            ],
            'mid' => [
                'width' => $midInfo['width'] ?? null,
                'height' => $midInfo['height'] ?? null,
            ],
            'thumb' => [
                'width' => $thumbInfo['width'] ?? null,
                'height' => $thumbInfo['height'] ?? null,
            ],
        ];
        $image->save();

        TileImage::dispatch($image);

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }

    public function show(VRACImage $image)
    {
        $image->load([
                'agents.contributorName',
                'user',
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
            ]);
        return new ImageResource($image);
    }

    public function update(UpdateImageRequest $request, VRACImage $image)
    {
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->save();

        // $path = $request->file('image')->storeAs(
        //     dirname($image->originalPath()), 'default.jpg', 'public'
        // );

        $title = $image->titles()->first();
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $agentPhotographer->load('contributorName');
        $image->agents()->sync($agentPhotographer->id);

        $right = $image->rights()->first();
        $right->text = Str::upper($request->input('license'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/' . Str::lower($request->input('license')) . '/4.0';
        $right->rights_holder = $agentPhotographer->contributorName->name;
        $right->save();

        $image->subjects()->sync($request->input('subjects'));

        if ($request->filled('description')) {
            $description = $image->descriptions()->first();
            $description->text = $request->input('description');
            $description->save();
        }

        if ($request->filled('latitude') || $request->filled('longitude')) {
            $location = $image->locations()->first();
            $location->latitude = $request->input('latitude');
            $location->longitude = $request->input('longitude');
            $location->label = $request->input('location_label');
            // $location->coordinates = '-23.580948, -46.637004';
            $location->save();
        }

        if ($request->filled('earliest_date')) {
            $date = $image->dates()->first();
            $date->type = 'creation';
            $date->earliest_date = $request->input('earliest_date');
            $date->circa_earliest_date = $request->input('circa');
            $date->latest_date = $request->input('latest_date');
            $date->circa_latest_date = $request->input('circa');
            $date->save();
        }

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }

    public function destroy(Request $request, VRACImage $image)
    {
        if ($request->user()->id !== $image->user_id) {
            abort(403);
        }

        Storage::disk('public')->deleteDirectory($image->path('base'));

        $image->delete();

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }

    public function downloadFull(Request $request, string $id)
    {
        $image = VRACImage::find($id);

        $path = $image->path('original', 'absolute');

        return response()->download($path, 'imagem-' . $id);
    }

    private function createDerivative(VRACImage $image, int $size = 300): array
    {
        // Create a thumbnail with vips and write to the appropriate storage path.
        $thumbnail = VipsImage::thumbnail($image->path('original', 'absolute'), $size);
        $width = $thumbnail->width;
        $height = $thumbnail->height;

        // relative IIIF-style path for the derivative (storage relative under images/iiif/{id})
        $relPath = $image->path('thumb', 'relative', ['width' => $width, 'height' => $height]);

        $destination = $image->path('thumb', 'absolute', ['width' => $width, 'height' => $height]);
        if (! file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        $thumbnail->writeToFile($destination);

        return [
            'rel_path' => $relPath,
            'width' => $width,
            'height' => $height,
        ];
    }
}
