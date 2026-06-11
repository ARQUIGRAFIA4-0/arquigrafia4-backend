<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Collective;
use App\Models\VRACore\VRACSubject;
use Illuminate\Http\Request;

/**
 * @group Álbuns
 *
 * Criação, visualização e gerenciamento de álbuns de imagens.
 */
class AlbumController extends Controller
{
    /**
     * @unauthenticated
     */
    public function index()
    {
        return Album::with(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }])->paginate();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'collective_id' => 'nullable|uuid|exists:collectives,id',
            'title'         => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'is_private'    => 'boolean',
        ]);

        if (!empty($data['collective_id'])) {
            $collective = Collective::findOrFail($data['collective_id']);
            if (!$collective->isMember($request->user())) {
                return response()->json([
                    'message' => 'No eres miembro de este colectivo.',
                ], 403);
            }
        } else {
            $data['user_id'] = $request->user()->id;
        }

        $album = Album::create($data);

        return response()->json([
            'message' => 'Álbum creado correctamente',
            'album'   => $album,
        ], 201);
    }

    /**
     * @unauthenticated
     */
    public function show(Request $request, $id)
    {
        $album = Album::with(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }])->findOrFail($id);

        if ($album->is_private) {
            $user = $request->user();
            $canSee = $album->isOwnedByCollective()
                ? ($user && $album->collective->isMember($user))
                : ($user && $album->isOwnedByUser($user));
            if (!$canSee) abort(403);
        }

        return $album;
    }

    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);

        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para editar este álbum.'], 403);
        }

        $data = $request->validate([
            'title'       => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'is_private'  => 'sometimes|boolean',
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'No se enviaron datos para actualizar'], 422);
        }

        $album->update($data);

        return response()->json([
            'message' => 'Álbum actualizado correctamente',
            'album'   => $album,
        ], 200);
    }

    public function destroy($id)
    {
        $album = Album::findOrFail($id);

        if (!$album->userCanManage(request()->user())) {
            return response()->json(['message' => 'No tienes permiso para eliminar este álbum.'], 403);
        }

        $album->delete();

        return response()->json(['message' => 'Álbum eliminado (soft delete)'], 200);
    }
    public function addImage(Request $request, $albumId)
    {
        // Validar input
        $data = $request->validate([
            'images' => 'required|array|min:1',
            'images.*.image_id' => 'required|uuid|exists:vrac_images,id',
        ]);

        $album = Album::findOrFail($albumId);

        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para agregar imágenes a este álbum.'], 403);
        }

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

        $album = Album::findOrFail($albumId);

        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para eliminar imágenes de este álbum.'], 403);
        }
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
    /**
     * @unauthenticated
     */
    public function getByUser($userId)
    {
        return Album::with(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }])
            ->where('user_id', $userId)
            ->get();
    }
    /**
     * @unauthenticated
     */
    public function getByCollective(Request $request, $collectiveId)
    {
        $collective = Collective::findOrFail($collectiveId);
        $user = $request->user();

        $query = Album::where('collective_id', $collectiveId);
        if (!$user || !$collective->isMember($user)) {
            $query->where('is_private', false);
        }

        return $query->with(['images' => function ($q) {
            $q->orderBy('pivot_position');
        }])->get();
    }

    /**
     * Tags de um álbum
     *
     * Retorna todas as tags únicas das imagens do álbum.
     *
     * @group Álbuns
     * @unauthenticated
     */
    public function tags(Album $album)
    {
        $tags = VRACSubject::select('vrac_subjects.id', 'vrac_subjects.term', 'vrac_subjects.type')
            ->join('image_subject', 'vrac_subjects.id', '=', 'image_subject.subject_id')
            ->join('album_image', 'image_subject.image_id', '=', 'album_image.image_id')
            ->where('album_image.album_id', $album->id)
            ->distinct()
            ->orderBy('vrac_subjects.term')
            ->get();

        return response()->json([
            'album_id' => $album->id,
            'tags'     => $tags,
        ]);
    }

    public function syncImages(Request $request, $albumId)
    {
        $data = $request->validate([
            'images' => 'required|array',
            'images.*.image_id' => 'required|uuid|exists:vrac_images,id',
            'images.*.position' => 'required|integer|min:1'
        ]);

        $album = Album::findOrFail($albumId);

        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para modificar las imágenes de este álbum.'], 403);
        }

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
