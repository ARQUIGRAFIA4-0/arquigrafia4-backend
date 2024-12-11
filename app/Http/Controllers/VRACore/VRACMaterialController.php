<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACMaterial;
use Illuminate\Http\Request;

class VRACMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materials = VRACMaterial::all();

        return response()->json([
            'materials' => $materials,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $material = new VRACMaterial();

        $material->label = $request->input('label');
        $material->type = $request->input('type');
        $material->vocab = $request->input('vocab');
        $material->ref_id = $request->input('ref_id');
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
        $material->type = $request->input('type');
        $material->vocab = $request->input('vocab');
        $material->ref_id = $request->input('ref_id');
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
