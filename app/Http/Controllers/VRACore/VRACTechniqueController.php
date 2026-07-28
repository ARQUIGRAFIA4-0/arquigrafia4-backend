<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACTechnique;
use Illuminate\Http\Request;

/**
 * @group VRACore - Técnicas
 * @unauthenticated
 */
class VRACTechniqueController extends Controller
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

        $query = VRACTechnique::when($search, fn($q) => $q->where('label', 'like', "%{$search}%"))
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
        $technique = new VRACTechnique;

        $technique->label = $request->input('label');
        $technique->vocab = 'Arquigrafia';
        $technique->save();

        return response()->json([
            'technique' => $technique,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $technique = VRACTechnique::find($id);

        return response()->json([
            'technique' => $technique,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $technique = VRACTechnique::find($id);

        $technique->label = $request->input('label');
        $technique->save();

        return response()->json([
            'technique' => $technique,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $technique = VRACTechnique::find($id);

        $technique->delete();

        return response()->json([
            'technique' => $technique,
        ]);
    }
}
