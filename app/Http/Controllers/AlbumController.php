<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index()
    {
        return Album::with('images')->get();
    }

    public function store(Request $request)
    {
        $album = Album::create($request->all());
        return response()->json($album, 201);
    }

    public function show($id)
    {
        return Album::with('images')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);
        $album->update($request->all());

        return $album;
    }

    public function destroy($id)
    {
        Album::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
    public function addImage(Request $request, $albumId)
{
    $album = Album::findOrFail($albumId);

    $album->images()->attach($request->image_id, [
        'position' => $request->position ?? 0
    ]);

    return response()->json(['message' => 'Image added']);
}
}
