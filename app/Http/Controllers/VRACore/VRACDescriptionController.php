<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACDescription;
use Illuminate\Http\Request;

/**
 * @group VRACore - Descrições
 * @unauthenticated
 */
class VRACDescriptionController extends Controller
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
            return response()->json(['data' => VRACDescription::all()]);
        }

        return VRACDescription::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $description = new VRACDescription;

        $description->text = $request->input('text');
        $description->source = $request->input('source');
        $description->save();

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $description = VRACDescription::find($id);

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $description = VRACDescription::find($id);

        $description->text = $request->input('text');
        $description->source = $request->input('source');
        $description->save();

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $description = VRACDescription::find($id);

        $description->delete();

        return response()->json([
            'description' => $description,
        ]);
    }
}
