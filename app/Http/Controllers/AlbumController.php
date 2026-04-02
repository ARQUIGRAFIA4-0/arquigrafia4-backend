<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index()
    {
       return Album::with(['images' => function ($q) {
    $q->orderBy('pivot_position');
}])->get();
    }

 public function store(Request $request)
{
    $data = $request->validate([
        'title' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'is_private' => 'boolean'
    ]);

    $data['user_id'] = auth()->id();

    $album = Album::create($data);

    return response()->json($album, 201);
}

    public function show($id)
    {
        return Album::with(['images' => function ($q) {
    $q->orderBy('pivot_position');
}])->findOrFail($id);
    }

    public function update(Request $request, $id)
{
    $album = Album::findOrFail($id);

    $data = $request->validate([
        'title' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'is_private' => 'boolean'
    ]);

    $album->update($data);

    return response()->json($album, 200);
}

    public function destroy($id)
{
    $album = Album::findOrFail($id);
    $album->delete(); // activa deleted_at

    return response()->json([
        'message' => 'Álbum eliminado (soft delete)'
    ], 200);
}
 public function addImage(Request $request, $albumId)
{
    $data = $request->validate([
        'images' => 'required|array|min:1',
        'images.*.image_id' => 'required|uuid|exists:vrac_images,id',
    ]);

    $album = Album::findOrFail($albumId);

    // 🔥 traer todas las imágenes existentes UNA sola vez
    $existingImages = $album->images()->pluck('image_id')->toArray();

    // 🔥 calcular posición inicial UNA sola vez
    $maxPosition = $album->images()->max('position');
    $currentPosition = is_null($maxPosition) ? 0 : $maxPosition + 1;

    foreach ($data['images'] as $item) {

        // evitar duplicados SIN queries extra
        if (in_array($item['image_id'], $existingImages)) {
            continue;
        }

        $album->images()->attach($item['image_id'], [
            'position' => $currentPosition
        ]);

        $currentPosition++;
    }

    return response()->json(
        $album->load(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }]),
        201
    );
}
public function removeImages(Request $request, $albumId)
{
    $data = $request->validate([
        'image_ids' => 'required|array|min:1',
        'image_ids.*' => 'required|uuid|exists:vrac_images,id',
    ]);

    $album = Album::where('user_id', auth()->id())->findOrFail($albumId);

    $existingImageIds = $album->images()
        ->wherePivotIn('image_id', $data['image_ids'])
        ->pluck('vrac_images.id')
        ->toArray();

    if (empty($existingImageIds)) {
        return response()->json([
            'message' => 'Ninguna de las imágenes está en el álbum'
        ], 404);
    }

    $album->images()->detach($existingImageIds);

    return response()->json([
        'message' => 'Imágenes eliminadas del álbum',
        'removed_image_ids' => $existingImageIds,
    ], 200);
}
public function getByUser($userId)
{
    return Album::with(['images' => function ($q) {
        $q->orderBy('pivot_position');
    }])
    ->where('user_id', $userId)
    ->get();
}
public function syncImages(Request $request, $albumId)
{
    $data = $request->validate([
        'images' => 'required|array',
        'images.*.image_id' => 'required|uuid|exists:vrac_images,id',
        'images.*.position' => 'required|integer|min:1'
    ]);

    $album = Album::findOrFail($albumId);

    //Construir array para sync
    $syncData = collect($data['images'])->mapWithKeys(function ($item) {
        return [
            $item['image_id'] => ['position' => $item['position']]
        ];
    })->toArray();

    // 🔥 Sync mágico
    $album->images()->sync($syncData);

    return response()->json(
        $album->load(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }]),
        200
    );
}

}
