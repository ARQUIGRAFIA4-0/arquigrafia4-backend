<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collective\DeleteCollectiveRequest;
use App\Http\Requests\Collective\StoreCollectiveRequest;
use App\Http\Requests\Collective\UpdateCollectiveRequest;
use App\Http\Resources\CollectiveResource;
use App\Models\Collective;
use Illuminate\Support\Facades\Storage;
use Jcupitt\Vips\Image;

/**
 * @group Coletivos
 *
 * Criação e gerenciamento de coletivos.
 */
class CollectiveController extends Controller
{
    /**
     * @unauthenticated
     */
    public function index()
    {
        return CollectiveResource::collection(Collective::withCount('members')->paginate());
    }

    public function store(StoreCollectiveRequest $request)
    {
        $collective = new Collective();
        $collective->name = $request->input('name');
        $collective->email = $request->input('email');
        $collective->foundation_date = $request->input('foundation_date');
        $collective->location = $request->input('location');
        $collective->description = $request->input('description');
        $collective->socials = $request->input('socials');

        $collective->save();

        if ($request->hasFile('avatar')) {
            $filename = 'avatars/collectives/' . $collective->id . '.webp';
            $destPath = Storage::disk('public')->path($filename);
            $uploadedFile = $request->file('avatar');

            $image = Image::newFromBuffer(
                $uploadedFile->getContent(),
                '',
                ['access' => 'sequential']
            );

            $width  = $image->width;
            $height = $image->height;
            $minSide = min($width, $height);

            $image = $image->crop(
                (int) (($width  - $minSide) / 2),
                (int) (($height - $minSide) / 2),
                $minSide,
                $minSide
            );

            $image = $image->thumbnail_image(400, [
                'height' => 400,
                'size' => 'force',
            ]);

            $image->writeToFile($destPath, [
                'Q'     => 82,
                'strip' => true,
            ]);

            $collective->avatar_path = $filename;
            $collective->save();
        }

        if ($request->filled('subjects')) {
            $collective->subjects()->sync($request->input('subjects'));
        }

        // Creator becomes admin
        $collective->members()->attach($request->user()->id, ['role' => 'admin']);

        $collective->load('members', 'subjects');

        return new CollectiveResource($collective);
    }

    /**
     * @unauthenticated
     */
    public function show(Collective $collective)
    {
        $collective->load('members', 'subjects');
        $collective->loadCount('members');

        return new CollectiveResource($collective);
    }

    public function update(UpdateCollectiveRequest $request, Collective $collective)
    {
        if ($request->filled('name')) {
            $collective->name = $request->input('name');
        }
        if ($request->has('email')) {
            $collective->email = $request->input('email');
        }
        if ($request->has('foundation_date')) {
            $collective->foundation_date = $request->input('foundation_date');
        }
        if ($request->has('location')) {
            $collective->location = $request->input('location');
        }
        if ($request->has('description')) {
            $collective->description = $request->input('description');
        }
        if ($request->has('socials')) {
            $collective->socials = $request->input('socials');
        }

        if ($request->hasFile('avatar')) {
            if ($collective->avatar_path) {
                Storage::disk('public')->delete($collective->avatar_path);
            }

            $filename = 'avatars/collectives/' . $collective->id . '.webp';
            $destPath = Storage::disk('public')->path($filename);
            $uploadedFile = $request->file('avatar');

            $image = Image::newFromBuffer(
                $uploadedFile->getContent(),
                '',
                ['access' => 'sequential']
            );

            $width  = $image->width;
            $height = $image->height;
            $minSide = min($width, $height);

            $image = $image->crop(
                (int) (($width  - $minSide) / 2),
                (int) (($height - $minSide) / 2),
                $minSide,
                $minSide
            );

            $image = $image->thumbnail_image(400, [
                'height' => 400,
                'size' => 'force',
            ]);

            $image->writeToFile($destPath, [
                'Q'     => 82,
                'strip' => true,
            ]);

            $collective->avatar_path = $filename;
        }

        $collective->save();

        if ($request->has('subjects')) {
            $collective->subjects()->sync($request->input('subjects') ?? []);
        }

        $collective->load('subjects');

        return new CollectiveResource($collective);
    }

    public function destroy(DeleteCollectiveRequest $request, Collective $collective)
    {
        $collective->delete();

        return new CollectiveResource($collective);
    }
}
