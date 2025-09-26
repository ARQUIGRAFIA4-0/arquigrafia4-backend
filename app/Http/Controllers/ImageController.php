<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Jobs\TileImage;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACTitle;
use Jcupitt\Vips\Image as VipsImage;

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
        $image->legacy_id = $request->input('legacy_id');
        $image->ref_id = $request->input('ref_id');
        $image->source = $request->input('source');
        $image->save();

        $path = $request->file('image')->store(
            $image->basePath(), 'public'
        );

        $title = new VRACTitle();
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();
        $image->titles()->sync($title->id);

        $description = new VRACDescription();
        $description->text = $request->input('description');
        $description->save();
        $image->descriptions()->sync($description->id);

        $right = VRACRight::createWithConditions(
            $request->input('title'),
            $request->input('commercial'),
            $request->input('editable')
        );
        $image->rights()->sync($right->id);

        $this->createDerivative($image->basePath(), 200);
        $this->createDerivative($image->basePath(), 1024);
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
        $image->legacy_id = $request->input('legacy_id');
        $image->ref_id = $request->input('ref_id');
        $image->source = $request->input('source');
        $image->save();

        $title = $image->titles->first();
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();
        $image->titles()->sync($title->id);

        $description = $image->descriptions->first();
        $description->text = $request->input('description');
        $description->save();
        $image->descriptions()->sync($description->id);

        // $right = VRACRight::createWithConditions(
        //     $request->input('title'),
        //     $request->input('commercial'),
        //     $request->input('editable')
        // );
        // $image->rights()->sync($right->id);

        return new ImageResource($image);
    }

    public function destroy(VRACImage $image)
    {
        $image->delete();
        
        return new ImageResource($image);
    }

    private function createDerivative(string $file_path, int $size = 200)
    {
        $thumbnail = VipsImage::thumbnail($file_path . "/full/max/0/default.jpg", $size, ['height' => $size]);
        $thumbnail->writeToFile($file_path . "/full/$size,/0/default.jpg");
    }
}
