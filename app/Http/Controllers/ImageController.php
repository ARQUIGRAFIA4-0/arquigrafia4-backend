<?php

namespace App\Http\Controllers;

use App\Http\Requests\Image\StoreImageRequest;
use App\Http\Requests\Image\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Jobs\TileImage;
use App\Models\Location;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACTitle;
use Jcupitt\Vips\Image as VipsImage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function index()
    {
        $images = VRACImage::all();

        return ImageResource::collection($images);
    }

    public function store(StoreImageRequest $request)
    {
        $image = new VRACImage();

        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->save();

        $path = $request->file('image')->storeAs(
            dirname($image->originalPath()), 'default.jpg', 'public'
        );

        $title = new VRACTitle();
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();
        $image->titles()->sync($title->id);

        $right = new VRACRight();
        $right->text = Str::upper($request->input('right_text'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/' . Str::lower($request->input('right_text')) . '/4.0';
        $right->rights_holder = $request->input('owner_name');
        $right->save();
        $image->rights()->sync($right->id);

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $image->agents()->sync($agentPhotographer->id);

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

        $this->createDerivative($image, 200);
        $this->createDerivative($image, 1024);
        TileImage::dispatch($image);

        return new ImageResource($image);
    }

    public function show(VRACImage $image)
    {
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

        $right = $image->rights()->first();
        $right->text = Str::upper($request->input('right_text'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/' . Str::lower($request->input('right_text')) . '/4.0';
        $right->rights_holder = $request->input('owner_name');
        $right->save();

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $image->agents()->sync($agentPhotographer->id);

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

        return new ImageResource($image);
    }

    public function destroy(VRACImage $image)
    {
        $image->delete();
        
        return new ImageResource($image);
    }

    private function createDerivative(VRACImage $image, int $size = 200)
    {
        $thumbnail = VipsImage::thumbnail($image->originalPath(), $size, ['height' => $size]);
        $destination = $image->basePath() . "/full/$size,/0/default.jpg";
        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        $thumbnail->writeToFile($destination);
    }
}
