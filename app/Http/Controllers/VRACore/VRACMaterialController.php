<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACMaterial;
use Illuminate\Http\Request;

/**
 * @group VRACore - Materiais
 * @unauthenticated
 */
class VRACMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @queryParam per_page integer Items per page. Use -1 for all records. Default: 15.
     * @queryParam search string Filter by label (case and accent insensitive). Example: concreto
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $search  = $request->string('search')->trim();

        $query = VRACMaterial::when($search, fn($q) => $q->where('label', 'like', "%{$search}%"))
            ->orderBy('label');

        if ($perPage === -1) {
            return response()->json(['data' => $query->get()]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @authenticated
     */
    public function store(Request $request)
    {
        $material = new VRACMaterial;

        $material->label = $request->input('label');
        $material->type  = $request->input('type');
        $material->vocab = 'Arquigrafia';
        $material->save();

        return response()->json([
            'material' => $material,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $material = VRACMaterial::find($id);

        return response()->json([
            'material' => $material,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $material = VRACMaterial::find($id);

        $material->label = $request->input('label');
        $material->type  = $request->input('type');
        $material->save();

        return response()->json([
            'material' => $material,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $material = VRACMaterial::find($id);

        $material->delete();

        return response()->json([
            'material' => $material,
        ]);
    }
}
