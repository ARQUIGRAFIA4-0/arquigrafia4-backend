<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use Illuminate\Support\Facades\Storage;
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

        // $description = new VRACDescription();
        // $description->text = $request->input('description');
        // $description->save();
        // $image->descriptions()->sync($description->id);

        $right = VRACRight::createWithConditions(
            $request->input('title'),
            $request->input('commercial'),
            $request->input('editable')
        );
        $image->rights()->sync($right->id);


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
