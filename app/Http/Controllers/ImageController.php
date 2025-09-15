<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Jobs\TileImage;
use Jcupitt\Vips;

class ImageController extends Controller
{
    public function index()
    {
        $images = Image::all();

        return ImageResource::collection($images);
    }

    public function store(StoreImageRequest $request)
    {
        $image = new Image();

        $image->file_path = $request->input('file_path'); // storage/app/public/images/iiif/{uuid}
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->draft = $request->input('draft');
        $image->legacy_id = $request->input('legacy_id');
        $image->ref_id = $request->input('ref_id');
        $image->source = $request->input('source');

        $image->save();

        $this->createDerivative($image->file_path, 200);
        $this->createDerivative($image->file_path, 1024);
        TileImage::dispatch($image);

        return new ImageResource($image);
    }

    public function show(Image $image)
    {
        return new ImageResource($image);
    }

    public function update(UpdateImageRequest $request, Image $image)
    {
        $image->file_path = $request->input('file_path');
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->draft = $request->input('draft');
        $image->legacy_id = $request->input('legacy_id');
        $image->ref_id = $request->input('ref_id');
        $image->source = $request->input('source');

        $image->save();

        return new ImageResource($image);
    }

    public function destroy(Image $image)
    {
        $image->delete();
        
        return new ImageResource($image);
    }

    public function createDerivative(string $file_path, int $size = 200)
    {
        $thumbnail = Vips\Image::thumbnail($file_path . "/full/max/0/default.jpg", $size, ['height' => $size]);
        $thumbnail->writeToFile($file_path . "/full/$size,/0/default.jpg");
    }
}
