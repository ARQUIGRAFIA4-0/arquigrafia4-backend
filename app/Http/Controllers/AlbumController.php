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
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean'
        ]);

        // Usuario autenticado
        $data['user_id'] = $request->user()->id;

        $album = Album::create($data);

        return response()->json([
            'message' => 'Álbum creado correctamente',
            'album' => $album,
        ], 201);
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
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'is_private' => 'sometimes|boolean'
        ]);
            // evitar updates vacíos
    if (empty($data)) {
        return response()->json([
            'message' => 'No se enviaron datos para actualizar'
        ], 422);
    }
        $album->update($data);

        return response()->json([
            'message' => 'Álbum actualizado correctamente',
            'album' => $album,
        ], 200);
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
        // Validar input
        $data = $request->validate([
            'images' => 'required|array|min:1',
            'images.*.image_id' => 'required|uuid|exists:vrac_images,id',
        ]);

        $album = Album::findOrFail($albumId);

        // IDs ya existentes en el álbum (evita queries dentro del loop)
        $existingImages = $album->images()->pluck('image_id')->toArray();

        // Posición inicial (continuar secuencia)
        $maxPosition = $album->images()->max('position');
        $currentPosition = is_null($maxPosition) ? 1 : $maxPosition + 1;

        $addedImageIds = [];
        $skippedImageIds = [];

        foreach ($data['images'] as $item) {
            $imageId = $item['image_id'];
            if (in_array($imageId, $existingImages)) {
                $skippedImageIds[] = $imageId;
                continue;
            }
            $album->images()->attach($imageId, [
                'position' => $currentPosition
            ]);
            $existingImages[] = $imageId;
            $addedImageIds[] = $imageId;
            $currentPosition++;
        }

        // 201 si se agregó algo, 200 si todo fue omitido
        $status = count($addedImageIds) > 0 ? 201 : 200;

        return response()->json([
            'message' => count($addedImageIds) > 0
                ? 'Imágenes agregadas al álbum'
                : 'No se agregaron imágenes porque ya existían en el álbum',
            'added_image_ids' => $addedImageIds,
            'skipped_image_ids' => $skippedImageIds,

            // Álbum actualizado con orden correcto
            'album' => $album->load(['images' => function ($q) {
                $q->orderBy('pivot_position');
            }]),
        ], $status);
    }
    public function removeImages(Request $request, $albumId)
    {
        $data = $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'required|uuid|exists:vrac_images,id',
        ]);

        //$album = Album::where('user_id', auth()->id())->findOrFail($albumId);
        $album = Album::where('user_id', $request->user()->id)->findOrFail($albumId);
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

        //Sync
        $album->images()->sync($syncData);

        return response()->json(
            $album->load(['images' => function ($q) {
                $q->orderBy('pivot_position');
            }]),
            200
        );
    }
}
