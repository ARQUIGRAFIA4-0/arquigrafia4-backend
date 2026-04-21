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
     * @queryParam per_page integer The number of items per page. Use -1 to fetch all records without pagination. Default is 15.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        if ($perPage === -1) {
            return response()->json(['data' => VRACTechnique::all()]);
        }

        return VRACTechnique::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $technique = new VRACTechnique;

        $technique->label = $request->input('label');
        $technique->vocab = $request->input('vocab');
        $technique->ref_id = $request->input('ref_id');
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
        $technique->vocab = $request->input('vocab');
        $technique->ref_id = $request->input('ref_id');
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
